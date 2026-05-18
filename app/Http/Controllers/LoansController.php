<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Customer;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\LoanStoreRequest;
use App\Http\Requests\LoanUpdateRequest;
use App\Http\Requests\LoanStatusRequest;
use App\Http\Requests\LoanPaymentRequest;
use OpenApi\Attributes as OA;

class LoansController extends Controller
{
    private function authorizeAction(Request $request, string $permission)
    {
        $user = $request->user();
        if (!$user->hasPermission($permission)) {
            abort(response()->json([
                'success' => false, 
                'message' => "Unauthorized. You do not have permission to {$permission}."
            ], 403));
        }
    }

    #[OA\Get(path: '/loans', summary: 'List loans', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Loan::query()
            ->where('business_id', $user->business_id)
            ->with(['customer', 'collaterals']);

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        
        $loans = $q->orderByDesc('created_at')->paginate($request->query('per_page', 15));
        return response()->json(['success' => true, 'data' => $loans]);
    }

    #[OA\Post(path: '/loans', summary: 'Create loan', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Created'), new OA\Response(response: 422, description: 'Validation error')])]
    public function store(LoanStoreRequest $request, \App\Services\LoanCalculationService $calculator): JsonResponse
    {
        $this->authorizeAction($request, 'loans.create');
        $user = $request->user();
        $data = $request->validated();
        
        // Ensure customer belongs to this business
        Customer::where('business_id', $user->business_id)->findOrFail($data['customer_id']);
        
        $data['business_id']     = $user->business_id;
        $data['loan_officer_id'] = $user->id;
        $data['loan_number']     = 'LN-' . strtoupper(uniqid());
        
        if (!isset($data['branch_id'])) {
            $data['branch_id'] = $user->branch_id;
        }

        // ── Resolve interest rate based on template type ──────────────────────
        if (!empty($data['loan_template_id'])) {
            $template = \App\Models\LoanTemplate::find($data['loan_template_id']);

            if ($template && $template->template_type === 'flat_rate') {
                $ratePeriod = $data['rate_period'] ?? 'month';
                $periodRate = $template->getRateForPeriod($ratePeriod);
                if ($periodRate !== null) {
                    $data['interest_rate'] = $periodRate;
                }
                $data['interest_type'] = 'FLAT';
            } elseif ($template && $template->template_type === 'smart_loan') {
                $data['interest_type'] = 'REDUCING';
                $data['term_unit']     = 'months';
            }
        }

        $loan = Loan::create($data);
        
        // Load the template so the calculator can route to the right schedule logic
        $loan->load('loanTemplate');

        $schedules = $calculator->generateRepaymentSchedule($loan, $data['start_date']);
        foreach ($schedules as $sched) {
            $sched['business_id'] = $loan->business_id;
            $loan->schedules()->create($sched);
        }

        $loan->load(['customer', 'collaterals', 'loanTemplate', 'schedules']);

        return response()->json(['success' => true, 'message' => 'Loan created', 'data' => $loan], 201);
    }

    #[OA\Get(path: '/loans/{id}', summary: 'Get loan details', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')])]
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('business_id', $user->business_id)
            ->with(['customer', 'payments', 'collaterals', 'loanTemplate', 'schedules'])
            ->findOrFail($id);
            
        return response()->json([
            'success' => true,
            'data' => $loan
        ]);
    }

    #[OA\Put(path: '/loans/{id}', summary: 'Update loan', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')])]
    public function update(LoanUpdateRequest $request, $id): JsonResponse
    {
        $this->authorizeAction($request, 'loans.edit');
        $user = $request->user();
        $loan = Loan::where('business_id', $user->business_id)->findOrFail($id);
        $data = $request->validated();
        
        $loan->update($data);

        return response()->json(['success' => true, 'message' => 'Loan updated', 'data' => $loan]);
    }

    #[OA\Post(path: '/loans/{id}/status', summary: 'Change loan status', tags: ['Loans'], security: [['bearerAuth' => []]])]
    public function changeStatus(Request $request, $id, \App\Services\DisbursementService $disbursementService): JsonResponse
    {
        $this->authorizeAction($request, 'loans.approve');
        $user = $request->user();
        $loan = Loan::where('business_id', $user->business_id)->findOrFail($id);
        
        $request->validate([
            'status' => 'required|string|in:PENDING,APPROVED,ACTIVE,PAID,DEFAULTED,CANCELLED,pending,approved,active,paid,defaulted,cancelled'
        ]);

        $newStatus = strtoupper($request->status);

        // Validation: Cannot skip steps
        if ($newStatus === 'ACTIVE' && $loan->status !== 'APPROVED') {
            return response()->json(['success' => false, 'message' => 'Loan must be APPROVED before it can be activated/disbursed'], 400);
        }

        DB::transaction(function () use ($loan, $newStatus, $disbursementService) {
            $loan->update(['status' => $newStatus]);

            // If APPROVED, auto-create a disbursement entry
            if ($newStatus === 'APPROVED') {
                $disbursementService->createDisbursement($loan, [
                    'amount' => $loan->principal_amount,
                    'destination_account' => 'MANUAL',
                    'provider' => 'MANUAL'
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Loan status updated to {$newStatus}",
            'data' => $loan->load('customer')
        ]);
    }

    #[OA\Delete(path: '/loans/{id}', summary: 'Delete loan', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')])]
    public function destroy(Request $request, $id): JsonResponse
    {
        $this->authorizeAction($request, 'loans.delete');
        $user = $request->user();
        $loan = Loan::where('business_id', $user->business_id)->findOrFail($id);
        $loan->delete();
        return response()->json(['success' => true, 'message' => 'Loan deleted']);
    }

    #[OA\Post(path: '/loans/{id}/payments', summary: 'Add a manual payment', tags: ['Loans', 'Payments'], security: [['bearerAuth' => []]])]
    public function addPayment(Request $request, $id, \App\Services\LoanCalculationService $calculator): JsonResponse
    {
        $this->authorizeAction($request, 'loans.edit');
        $user = $request->user();
        $loan = Loan::where('business_id', $user->business_id)->with('loanTemplate')->findOrFail($id);
        
        $data = $request->validate([
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        // Calculate remaining balance
        $totalRepayable = $calculator->calculateTotalRepayment($loan);
        $totalPaidBefore = (float) $loan->payments()->sum('amount_paid');
        $remainingBalance = round($totalRepayable - $totalPaidBefore, 2);

        if ((float)$data['amount_paid'] > ($remainingBalance + 0.01)) {
            return response()->json([
                'success' => false,
                'message' => "Payment amount exceeds the remaining balance. Maximum allowed is " . number_format($remainingBalance, 2),
                'remaining_balance' => $remainingBalance
            ], 422);
        }

        $payment = LoanPayment::create([
            'business_id' => $user->business_id,
            'loan_id' => $loan->id,
            'payment_date' => $data['payment_date'],
            'amount_paid' => $data['amount_paid'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'],
            'notes' => $data['notes'],
            'recorded_by' => $user->id
        ]);

        $totalPaidAfter = (float) $loan->payments()->sum('amount_paid');
        
        // Auto-close loan if the remaining balance is fulfilled (with 1 cent rounding buffer)
        if ($totalPaidAfter >= ($totalRepayable - 0.01)) {
            $loan->update(['status' => 'PAID']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => $payment
        ], 201);
    }
}

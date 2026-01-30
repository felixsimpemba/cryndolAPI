<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Borrower;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\LoanStoreRequest;
use App\Http\Requests\LoanUpdateRequest;
use App\Http\Requests\LoanStatusRequest;
use App\Http\Requests\LoanPaymentRequest;
use App\Mail\LoanApprovedMail;
use App\Mail\PaymentReminderMail;
use App\Mail\PaymentReceivedMail;
use App\Mail\LoanClosedMail;
use OpenApi\Attributes as OA;

class LoansController extends Controller
{
    #[OA\Get(path: '/loans', summary: 'List loans', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Loan::query()
            ->where('user_id', $user->id)
            ->with(['borrower'])
            ->withSum('payments as totalPaid', 'amountPaid');

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        $loans = $q->orderByDesc('id')->paginate($request->query('per_page', 15));
        return response()->json(['success' => true, 'data' => $loans]);
    }

    #[OA\Post(path: '/loans', summary: 'Create loan', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Created'), new OA\Response(response: 422, description: 'Validation error')])]
    public function store(LoanStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        // Ensure borrower belongs to this user
        Borrower::where('user_id', $user->id)->findOrFail($data['borrower_id']);
        $data['user_id'] = $user->id;
        $data['totalPaid'] = $data['totalPaid'] ?? 0;
        $loan = Loan::create($data);
        return response()->json(['success' => true, 'message' => 'Loan created', 'data' => $loan], 201);
    }

    #[OA\Get(path: '/loans/{id}', summary: 'Get loan details', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')])]
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('user_id', $user->id)->with(['borrower', 'payments'])->findOrFail($id);
        // aggregate totals
        $totalPaid = $loan->payments()->sum('amountPaid');
        $totalDue = (float) $loan->principal + ((float) $loan->principal * (float) $loan->interestRate / 100.0);
        $balance = $totalDue - (float) $totalPaid;
        return response()->json([
            'success' => true,
            'data' => [
                'loan' => $loan,
                'aggregates' => [
                    'totalPaid' => (float) $totalPaid,
                    'totalDue' => (float) $totalDue,
                    'balance' => (float) $balance,
                ]
            ]
        ]);
    }

    #[OA\Put(path: '/loans/{id}', summary: 'Update loan', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')])]
    public function update(LoanUpdateRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('user_id', $user->id)->findOrFail($id);
        $data = $request->validated();
        $loan->update($data);
        return response()->json(['success' => true, 'message' => 'Loan updated', 'data' => $loan]);
    }

    #[OA\Delete(path: '/loans/{id}', summary: 'Delete loan', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')])]
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('user_id', $user->id)->findOrFail($id);
        $loan->delete();
        return response()->json(['success' => true, 'message' => 'Loan deleted']);
    }

    #[OA\Post(path: '/loans/{id}/status', summary: 'Change loan status', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Unprocessable Entity')])]
    protected $workflowService;
    protected $repaymentService;

    public function __construct(\App\Services\WorkflowService $workflowService, \App\Services\RepaymentService $repaymentService)
    {
        $this->workflowService = $workflowService;
        $this->repaymentService = $repaymentService;
    }

    public function changeStatus(LoanStatusRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('user_id', $user->id)->with(['borrower', 'user'])->findOrFail($id);
        $data = $request->validated();
        $status = strtolower($data['status']);
        $comments = $request->input('comments');
        $sendEmail = $request->input('sendEmail', false);

        try {
            switch ($status) {
                case 'approved':
                    $this->workflowService->approveApplication($loan, $user, $comments);
                    // Send approval email if requested and borrower has email
                    if ($sendEmail && $loan->borrower && $loan->borrower->email) {
                        Mail::to($loan->borrower->email)->send(new LoanApprovedMail($loan));
                    }
                    break;
                case 'rejected':
                    $this->workflowService->rejectApplication($loan, $user, $comments);
                    break;
                case 'active': // Disbursed
                    $this->workflowService->disburseLoan($loan, $user, $comments);
                    break;
                case 'closed':
                    // Fallback for simple transitions
                    $action = 'update_status';
                    $this->workflowService->transition($loan, $user, $action, $status, $comments);
                    // Send closure email if requested and borrower has email
                    if ($sendEmail && $loan->borrower && $loan->borrower->email) {
                        Mail::to($loan->borrower->email)->send(new LoanClosedMail($loan));
                    }
                    break;
                default:
                    // Fallback for simple transitions like 'cancelled', 'paid', 'defaulted'
                    $action = 'update_status';
                    // transition($loan, $user, $action, $toStatus, $comments)
                    $this->workflowService->transition($loan, $user, $action, $status, $comments);
                    break;
            }
            return response()->json(['success' => true, 'message' => "Loan status updated to $status", 'data' => $loan]);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, $e->getMessage(), 422);
        }
    }

    #[OA\Post(path: '/loans/{id}/payments', summary: 'Record payment for a loan', tags: ['Loan Payments'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Created'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')])]
    public function addPayment(LoanPaymentRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('user_id', $user->id)->with(['borrower', 'user'])->findOrFail($id);
        $data = $request->validated();
        $sendEmail = $request->input('sendEmail', false);

        try {
            $payment = $this->repaymentService->recordPayment($loan, $data);

            // Calculate balance after payment
            $totalPaid = $loan->payments()->sum('amountPaid');
            $totalDue = (float) $loan->principal + ((float) $loan->principal * (float) $loan->interestRate / 100.0);
            $balance = $totalDue - (float) $totalPaid;

            // Send payment confirmation email if requested and borrower has email
            if ($sendEmail && $loan->borrower && $loan->borrower->email) {
                Mail::to($loan->borrower->email)->send(new PaymentReceivedMail(
                    $loan,
                    $data['amountPaid'],
                    $data['paidDate'],
                    $balance
                ));
            }

            return response()->json(['success' => true, 'message' => 'Payment recorded', 'data' => $payment], 201);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, $e->getMessage(), 500);
        }
    }

    /**
     * Send payment reminder email to borrower
     */
    #[OA\Post(path: '/loans/{id}/send-reminder', summary: 'Send payment reminder email', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')])]
    public function sendReminderEmail(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('user_id', $user->id)->with(['borrower', 'user'])->findOrFail($id);

        // Check if borrower has an email
        if (!$loan->borrower || !$loan->borrower->email) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower does not have an email address'
            ], 422);
        }

        // Calculate balance
        $totalPaid = $loan->payments()->sum('amountPaid');
        $totalDue = (float) $loan->principal + ((float) $loan->principal * (float) $loan->interestRate / 100.0);
        $balance = $totalDue - (float) $totalPaid;

        try {
            Mail::to($loan->borrower->email)->send(new PaymentReminderMail($loan, $balance));
            return response()->json(['success' => true, 'message' => 'Reminder email sent successfully']);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Failed to send reminder email', 500);
        }
    }
}

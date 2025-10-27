<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Borrower;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class LoansController extends Controller
{
    #[OA\Get(path: '/loans', summary: 'List loans', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Loan::query()->where('user_id', $user->id)->with(['borrower']);
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        $loans = $q->orderByDesc('id')->paginate($request->query('per_page', 15));
        return response()->json(['success' => true, 'data' => $loans]);
    }

    #[OA\Post(path: '/loans', summary: 'Create loan', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Created'), new OA\Response(response: 422, description: 'Validation error')])]
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'borrower_id' => ['required','integer','exists:borrowers,id'],
            'principal' => ['required','numeric','min:0.01'],
            'interestRate' => ['required','numeric','min:0','max:100'],
            'termMonths' => ['required','integer','min:1'],
            'startDate' => ['required','date'],
            'status' => ['sometimes','string'],
        ]);
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
        $loan = Loan::where('user_id', $user->id)->with(['borrower','payments'])->findOrFail($id);
        // aggregate totals
        $totalPaid = $loan->payments()->sum('amountPaid');
        $totalDue = (float)$loan->principal + ((float)$loan->principal * (float)$loan->interestRate / 100.0);
        $balance = $totalDue - (float)$totalPaid;
        return response()->json(['success' => true, 'data' => [
            'loan' => $loan,
            'aggregates' => [
                'totalPaid' => (float)$totalPaid,
                'totalDue' => (float)$totalDue,
                'balance' => (float)$balance,
            ]
        ]]);
    }

    #[OA\Put(path: '/loans/{id}', summary: 'Update loan', tags: ['Loans'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')])]
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('user_id', $user->id)->findOrFail($id);
        $data = $request->validate([
            'principal' => ['sometimes','numeric','min:0.01'],
            'interestRate' => ['sometimes','numeric','min:0','max:100'],
            'termMonths' => ['sometimes','integer','min:1'],
            'startDate' => ['sometimes','date'],
            'status' => ['sometimes','string'],
        ]);
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
    public function changeStatus(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('user_id', $user->id)->findOrFail($id);
        $data = $request->validate([
            'status' => ['required','string','in:PENDING,APPROVED,ACTIVE,PAID,DEFAULTED,CANCELLED']
        ]);
        return DB::transaction(function () use ($loan, $data) {
            $old = $loan->status;
            $loan->status = $data['status'];
            if ($data['status'] === 'PAID') {
                $totalPaid = $loan->payments()->sum('amountPaid');
                $totalDue = (float)$loan->principal + ((float)$loan->principal * (float)$loan->interestRate / 100.0);
                if ($totalPaid + 0.01 < $totalDue) {
                    return response()->json(['success' => false, 'message' => 'Cannot mark as PAID: outstanding balance remains'], 422);
                }
            }
            $loan->save();
            return response()->json(['success' => true, 'message' => 'Status changed', 'data' => ['old' => $old, 'new' => $loan->status]]);
        });
    }

    #[OA\Post(path: '/loans/{id}/payments', summary: 'Record payment for a loan', tags: ['Loan Payments'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Created'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')])]
    public function addPayment(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $loan = Loan::where('user_id', $user->id)->findOrFail($id);
        $data = $request->validate([
            'paidDate' => ['required','date'],
            'amountPaid' => ['required','numeric','min:0.01'],
            'notes' => ['sometimes','string','max:1000']
        ]);
        $payment = new LoanPayment();
        $payment->loan_id = $loan->id;
        $payment->paidDate = $data['paidDate'];
        $payment->amountPaid = $data['amountPaid'];
        $payment->status = 'PAID';
        $payment->save();
        // Update cached totalPaid
        $loan->totalPaid = $loan->payments()->sum('amountPaid');
        $loan->save();
        return response()->json(['success' => true, 'message' => 'Payment recorded', 'data' => $payment], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Services\DisbursementService;
use Illuminate\Http\Request;

class DisbursementController extends Controller
{
    protected $disbursementService;

    private function authorizeAction(Request $request, string $permission)
    {
        $user = $request->user();
        if (!$user->hasPermission($permission)) {
            abort(response()->json([
                'status' => 'error', 
                'message' => "Unauthorized. You do not have permission to {$permission}."
            ], 403));
        }
    }

    public function __construct(DisbursementService $disbursementService)
    {
        $this->disbursementService = $disbursementService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Disbursement::with('loan.customer')
            ->whereHas('loan', function ($q) use ($user) {
                $q->where('business_id', $user->business_id);
            });

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search (Loan number or Customer name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('loan', function($lq) use ($search) {
                    $lq->where('loan_number', 'like', "%{$search}%")
                       ->orWhereHas('customer', function($cq) use ($search) {
                           $cq->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                       });
                })->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $disbursements = $query->orderByDesc('created_at')
            ->paginate($request->query('per_page', 15));

        return response()->json(['status' => 'success', 'data' => $disbursements]);
    }

    public function store(Request $request, $loanId)
    {
        $this->authorizeAction($request, 'loans.approve');
        // Manual creation of disbursement record
        $loan = Loan::findOrFail($loanId);
        // Validation...
        $disbursement = $this->disbursementService->createDisbursement($loan, $request->all());

        return response()->json(['status' => 'success', 'data' => $disbursement]);
    }

    public function process(Request $request, $id)
    {
        $this->authorizeAction($request, 'disbursements.process');
        $disbursement = Disbursement::findOrFail($id);
        if ($disbursement->status !== 'PENDING' && $disbursement->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Disbursement already processed or failed'], 400);
        }

        $request->validate([
            'method' => 'required|string',
            'reference' => 'nullable|string'
        ]);

        $processed = $this->disbursementService->processDisbursement($disbursement, $request->all());

        return response()->json(['status' => 'success', 'message' => 'Disbursement processed', 'data' => $processed]);
    }
}

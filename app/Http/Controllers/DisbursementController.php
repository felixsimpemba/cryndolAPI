<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Services\DisbursementService;
use Illuminate\Http\Request;

class DisbursementController extends Controller
{
    protected $disbursementService;

    public function __construct(DisbursementService $disbursementService)
    {
        $this->disbursementService = $disbursementService;
    }

    public function index(Request $request)
    {
        $disbursements = Disbursement::with('loan.borrower')
            ->whereHas('loan', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->orderByDesc('id')
            ->paginate(15);
        return response()->json(['status' => 'success', 'data' => $disbursements]);
    }

    public function store(Request $request, $loanId)
    {
        // Manual creation of disbursement record
        $loan = Loan::findOrFail($loanId);
        // Validation...
        $disbursement = $this->disbursementService->createDisbursement($loan, $request->all());

        return response()->json(['status' => 'success', 'data' => $disbursement]);
    }

    public function process($id)
    {
        $disbursement = Disbursement::findOrFail($id);
        if ($disbursement->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Disbursement already processed or failed'], 400);
        }

        $processed = $this->disbursementService->processDisbursement($disbursement);

        return response()->json(['status' => 'success', 'message' => 'Disbursement processed', 'data' => $processed]);
    }
}

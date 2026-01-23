<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use App\Models\ApprovalLog;
use Illuminate\Support\Facades\DB;
use Exception;

class WorkflowService
{
    protected $disbursementService;

    public function __construct(DisbursementService $disbursementService)
    {
        $this->disbursementService = $disbursementService;
    }

    public function transition(Loan $loan, User $user, string $action, string $toStatus, ?string $comments = null)
    {
        return DB::transaction(function () use ($loan, $user, $action, $toStatus, $comments) {
            $fromStatus = $loan->status;

            // Validate transition (Basic check, can be expanded)
            if ($loan->status === $toStatus) {
                // throw new Exception("Loan is already in status $toStatus");
            }

            // Update Loan Status
            $loan->status = $toStatus;
            $loan->save();

            // Log Action
            ApprovalLog::create([
                'loan_id' => $loan->id,
                'user_id' => $user->id,
                'action' => $action,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'comments' => $comments,
            ]);

            return $loan;
        });
    }

    public function submitApplication(Loan $loan, User $user)
    {
        return $this->transition($loan, $user, 'submit', 'submitted');
    }

    public function approveApplication(Loan $loan, User $user, ?string $comments = null)
    {
        // Add check: Is user authorized? handled by Policy/Controller
        return $this->transition($loan, $user, 'approve', 'approved', $comments);
    }

    public function rejectApplication(Loan $loan, User $user, ?string $comments = null)
    {
        return $this->transition($loan, $user, 'reject', 'rejected', $comments);
    }

    public function disburseLoan(Loan $loan, User $user, ?string $comments = null)
    {
        if ($loan->status !== 'approved') {
            throw new Exception("Only approved loans can be disbursed.");
        }

        // Create Disbursement Record automatically
        $this->disbursementService->createDisbursement($loan, [
            'amount' => $loan->principal,
            'method' => 'manual', // Default or from request
        ]);

        return $this->transition($loan, $user, 'disburse', 'active', $comments);
    }
}

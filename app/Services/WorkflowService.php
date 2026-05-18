<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoanApprovedMail;
use Exception;

class WorkflowService
{
    protected $disbursementService;
    protected $amortizationService;

    public function __construct(DisbursementService $disbursementService, AmortizationService $amortizationService)
    {
        $this->disbursementService = $disbursementService;
        $this->amortizationService = $amortizationService;
    }

    public function transition(Loan $loan, User $user, string $action, string $toStatus, ?string $comments = null)
    {
        return DB::transaction(function () use ($loan, $user, $action, $toStatus, $comments) {
            $fromStatus = $loan->status;

            // Update Loan Status
            $loan->status = $toStatus;
            
            if ($toStatus === 'APPROVED') {
                $loan->approved_at = now();
                $loan->approved_by = $user->id;
            }
            
            $loan->save();

            // Log Action in Audit Log
            AuditLog::create([
                'business_id' => $loan->business_id,
                'user_id' => $user->id,
                'entity_type' => 'LOAN',
                'entity_id' => $loan->id,
                'action' => strtoupper($action),
                'old_values' => ['status' => $fromStatus],
                'new_values' => ['status' => $toStatus],
                'reason' => $comments,
                'ip_address' => request()->ip(),
            ]);

            return $loan;
        });
    }

    public function approveApplication(Loan $loan, User $user, ?string $comments = null)
    {
        $result = $this->transition($loan, $user, 'APPROVE', 'APPROVED', $comments);
        
        if ($loan->customer && $loan->customer->email) {
            Mail::to($loan->customer->email)->queue(new LoanApprovedMail($loan));
        }

        return $result;
    }

    public function rejectApplication(Loan $loan, User $user, ?string $comments = null)
    {
        return $this->transition($loan, $user, 'REJECT', 'CANCELLED', $comments);
    }

    public function disburseLoan(Loan $loan, User $user, ?string $comments = null)
    {
        if ($loan->status !== 'APPROVED') {
            throw new Exception("Only approved loans can be disbursed.");
        }

        // Create Disbursement Record
        $disbursement = $this->disbursementService->createDisbursement($loan, [
            'amount' => $loan->principal_amount,
            'provider' => 'MANUAL',
        ]);

        // Process it (which updates loan status to ACTIVE and creates accounting)
        $this->disbursementService->processDisbursement($disbursement);

        // Generate schedules
        $this->amortizationService->generateSchedule($loan);

        // Logging the disbursement action
        return $this->transition($loan, $user, 'DISBURSE', 'ACTIVE', $comments);
    }
}

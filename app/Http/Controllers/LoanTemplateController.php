<?php

namespace App\Http\Controllers;

use App\Models\LoanTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class LoanTemplateController extends Controller
{
    /**
     * Return the two system templates for this business, auto-seeding if needed.
     */
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;

        $this->ensureSystemTemplatesExist($businessId);

        $templates = LoanTemplate::where('business_id', $businessId)
            ->whereIn('template_type', ['flat_rate', 'smart_loan'])
            ->orderByRaw("FIELD(template_type, 'flat_rate', 'smart_loan')")
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $templates,
        ]);
    }

    /**
     * Display a single template.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $template = LoanTemplate::where('business_id', $request->user()->business_id)->find($id);

        if (!$template) {
            return response()->json(['status' => 'error', 'message' => 'Template not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $template]);
    }

    /**
     * Update a system template — only rates, limits, fees, penalties, and active status.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $template = LoanTemplate::where('business_id', $request->user()->business_id)->find($id);

        if (!$template) {
            return response()->json(['status' => 'error', 'message' => 'Template not found'], 404);
        }

        // Build validation rules based on template type
        $rules = [
            'description'          => 'nullable|string',
            'min_amount'           => 'sometimes|numeric|min:0',
            'max_amount'           => 'sometimes|numeric|min:0',
            'processing_fee_type'  => 'sometimes|in:fixed,percentage',
            'processing_fee_value' => 'sometimes|numeric|min:0',
            'late_penalty_type'    => 'sometimes|in:fixed,percentage',
            'late_penalty_value'   => 'sometimes|numeric|min:0',
            'late_penalty_frequency' => 'sometimes|in:once,daily,weekly,monthly',
            'is_active'            => 'boolean',
        ];

        if ($template->template_type === 'flat_rate') {
            $rules['rate_per_day']    = 'sometimes|numeric|min:0';
            $rules['rate_per_week']   = 'sometimes|numeric|min:0';
            $rules['rate_per_2weeks'] = 'sometimes|numeric|min:0';
            $rules['rate_per_3weeks'] = 'sometimes|numeric|min:0';
            $rules['rate_per_month']  = 'sometimes|numeric|min:0';
        } else {
            // smart_loan
            $rules['interest_rate'] = 'sometimes|numeric|min:0|max:100';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $template->update($validator->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Template updated successfully',
            'data'    => $template->fresh(),
        ]);
    }

    // ─── No store() or destroy() — templates are system-defined ───────────────

    /**
     * Ensure the two system templates exist for a business (idempotent seed).
     */
    private function ensureSystemTemplatesExist(string $businessId): void
    {
        $existing = LoanTemplate::where('business_id', $businessId)
            ->whereIn('template_type', ['flat_rate', 'smart_loan'])
            ->pluck('template_type')
            ->toArray();

        if (!in_array('flat_rate', $existing)) {
            LoanTemplate::create([
                'business_id'          => $businessId,
                'template_type'        => 'flat_rate',
                'name'                 => 'Flat Rate',
                'description'          => 'Simple interest charged at a fixed rate per period.',
                'interest_type'        => 'FLAT',
                'repayment_strategy'   => 'INSTALLMENTS',
                'interest_rate'        => 0,
                'rate_per_day'         => 0,
                'rate_per_week'        => 0,
                'rate_per_2weeks'      => 0,
                'rate_per_3weeks'      => 0,
                'rate_per_month'       => 0,
                'min_amount'           => 0,
                'max_amount'           => 0,
                'term_unit'            => 'months',
                'default_term'         => 1,
                'allow_custom_term'    => true,
                'processing_fee_type'  => 'fixed',
                'processing_fee_value' => 0,
                'late_penalty_type'    => 'fixed',
                'late_penalty_value'   => 0,
                'late_penalty_frequency' => 'once',
                'is_active'            => true,
            ]);
        }

        if (!in_array('smart_loan', $existing)) {
            LoanTemplate::create([
                'business_id'          => $businessId,
                'template_type'        => 'smart_loan',
                'name'                 => 'Smart Loan',
                'description'          => 'Bank-style reducing balance loan with monthly amortization.',
                'interest_type'        => 'REDUCING',
                'repayment_strategy'   => 'INSTALLMENTS',
                'interest_rate'        => 0, // Annual rate %
                'min_amount'           => 0,
                'max_amount'           => 0,
                'term_unit'            => 'months',
                'default_term'         => 12,
                'allow_custom_term'    => true,
                'processing_fee_type'  => 'fixed',
                'processing_fee_value' => 0,
                'late_penalty_type'    => 'fixed',
                'late_penalty_value'   => 0,
                'late_penalty_frequency' => 'once',
                'is_active'            => true,
            ]);
        }
    }
}

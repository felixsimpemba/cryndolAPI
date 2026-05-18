<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_template_id'   => ['nullable', 'uuid', 'exists:loan_templates,id'],
            'customer_id'        => ['required', 'uuid', 'exists:customers,id'],
            'principal_amount'   => ['required', 'numeric', 'min:0.01'],
            'interest_rate'      => ['required', 'numeric', 'min:0'],
            'loan_term_months'   => ['required', 'integer', 'min:1'],
            'term_unit'          => ['nullable', 'string', 'in:days,weeks,months,years,day,week,biweekly,triweekly,month'],
            'repayment_strategy' => ['required', 'string', 'in:INSTALLMENTS,BULLET'],
            'interest_type'      => ['nullable', 'string', 'in:FLAT,REDUCING'],
            'repayment_frequency'=> ['nullable', 'string', 'in:DAILY,WEEKLY,BIWEEKLY,TRIWEEKLY,MONTHLY,YEARLY,daily,weekly,biweekly,triweekly,monthly,yearly'],
            'rate_period'        => ['nullable', 'string', 'in:day,week,biweekly,triweekly,month'],
            'start_date'         => ['required', 'date'],
            'maturity_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'status'             => ['sometimes', 'string', 'in:PENDING,APPROVED,ACTIVE,PAID,DEFAULTED,CANCELLED,pending,approved,active,paid,defaulted,cancelled'],
            'purpose'            => ['nullable', 'string', 'max:255'],

            // Collateral fields
            'collateral_name'        => ['nullable', 'string', 'max:255'],
            'collateral_description' => ['nullable', 'string'],

            // Branch optional
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
        ];
    }
}

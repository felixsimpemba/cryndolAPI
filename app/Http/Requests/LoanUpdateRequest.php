<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'principal_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'interest_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'loan_term_months' => ['sometimes', 'integer', 'min:1'],
            'start_date' => ['sometimes', 'date'],
            'maturity_date' => ['sometimes', 'date', 'after:start_date'],
            'status' => ['sometimes', 'string', 'in:PENDING,APPROVED,ACTIVE,PAID,DEFAULTED,CANCELLED'],
            'purpose' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

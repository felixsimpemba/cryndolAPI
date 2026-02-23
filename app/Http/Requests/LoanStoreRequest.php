<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'loan_product_id' => ['nullable', 'integer', 'exists:loan_products,id'],
            'borrower_id' => ['required', 'integer', 'exists:borrowers,id'],
            'principal' => ['required', 'numeric', 'min:0.01'],
            'interestRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'termMonths' => ['required', 'integer', 'min:1'],
            'term_unit' => ['nullable', 'string', 'in:days,weeks,months,years'],
            'startDate' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'in:pending,approved,active,paid,defaulted,cancelled,submitted'],
            'collateral_name' => ['nullable', 'string', 'max:255'],
            'collateral_description' => ['nullable', 'string'],
            'collateral_photos' => ['nullable', 'array'],
            'collateral_photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:5120'],
        ];
    }

    /**
     * Get custom error messages for validator.
     */
    public function messages(): array
    {
        return [
            'borrower_id.required' => 'Borrower is required',
            'borrower_id.exists' => 'Selected borrower does not exist',
            'principal.required' => 'Loan principal amount is required',
            'principal.min' => 'Principal amount must be greater than 0',
            'interestRate.required' => 'Interest rate is required',
            'interestRate.max' => 'Interest rate cannot exceed 100%',
            'termMonths.required' => 'Loan term in months is required',
            'termMonths.min' => 'Loan term must be at least 1 month',
            'startDate.required' => 'Loan start date is required',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if ($user) {
                $balance = \App\Models\Transaction::where('user_id', $user->id)
                    ->selectRaw("COALESCE(SUM(CASE WHEN type = 'inflow' THEN amount ELSE 0 END),0) - COALESCE(SUM(CASE WHEN type = 'outflow' THEN amount ELSE 0 END),0) as balance")
                    ->value('balance') ?? 0;

                if ($this->input('principal') > $balance) {
                    $validator->errors()->add('principal', 'Loan amount exceeds your current working capital balance (' . $balance . ').');
                }
            }
        });
    }
}

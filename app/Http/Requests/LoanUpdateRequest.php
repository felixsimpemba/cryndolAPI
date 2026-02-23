<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanUpdateRequest extends FormRequest
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
            'principal' => ['sometimes', 'numeric', 'min:0.01'],
            'interestRate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'termMonths' => ['sometimes', 'integer', 'min:1'],
            'startDate' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:PENDING,APPROVED,ACTIVE,PAID,DEFAULTED,CANCELLED'],
            'collateral_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'collateral_description' => ['sometimes', 'nullable', 'string'],
            'collateral_photos' => ['sometimes', 'nullable', 'array'],
            'collateral_photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:5120'],
        ];
    }

    /**
     * Get custom error messages for validator.
     */
    public function messages(): array
    {
        return [
            'principal.min' => 'Principal amount must be greater than 0',
            'interestRate.max' => 'Interest rate cannot exceed 100%',
            'termMonths.min' => 'Loan term must be at least 1 month',
        ];
    }
}

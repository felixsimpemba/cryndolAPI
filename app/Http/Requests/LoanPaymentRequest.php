<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanPaymentRequest extends FormRequest
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
            'paidDate' => ['required', 'date'],
            'amountPaid' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['sometimes', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator.
     */
    public function messages(): array
    {
        return [
            'paidDate.required' => 'Payment date is required',
            'amountPaid.required' => 'Payment amount is required',
            'amountPaid.min' => 'Payment amount must be greater than 0',
            'notes.max' => 'Notes cannot exceed 1000 characters',
        ];
    }
}

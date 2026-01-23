<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|max:255', // Add unique rule if needed per user context
            'phoneNumber' => 'required|string|max:20',
            // KYC Fields
            'nrc_number' => 'nullable|string|max:50',
            'tpin_number' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'employment_status' => 'nullable|in:employed,self_employed,unemployed,student',
            'employer_name' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            // Default Risk
            'risk_segment' => 'nullable|in:low,medium,high',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPersonalRequest extends FormRequest
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
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phoneNumber' => 'required|string|max:20',
            'password' => [
                'required',
                'string',
            ],
            'acceptTerms' => 'required|boolean|accepted',
            'working_capital' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'acceptTerms.accepted' => 'You must accept the terms and conditions.'
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[0-9]/',        // At least one digit
                'regex:/[!@#$%^&*()_+\-=\[\]{};:\\\'"|,.<>\/?]/',   // At least one special char
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Тренутна лозинка је обавезна.',
            'new_password.required' => 'Нова лозинка је обавезна.',
            'new_password.min' => 'Нова лозинка мора садржати минимум :min карактера.',
            'new_password.confirmed' => 'Потврда лозинке се не подудара.',
            'new_password.regex' => 'Лозинка мора садржати минимум један број и један специјални карактер.',
        ];
    }
}

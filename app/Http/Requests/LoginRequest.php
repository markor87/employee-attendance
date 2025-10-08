<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'remember_email' => 'boolean',
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
            'email.required' => 'Email адреса је обавезна.',
            'email.email' => 'Email адреса није валидна.',
            'email.max' => 'Email адреса не сме бити дужа од :max карактера.',
            'password.required' => 'Лозинка је обавезна.',
            'password.min' => 'Лозинка мора садржати минимум :min карактера.',
            'remember_email.boolean' => 'Запамти email мора бити true или false.',
        ];
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Minimum 8 characters
        if (strlen($value) < 8) {
            $fail('Лозинка мора садржати најмање 8 карактера.');
            return;
        }

        // Must contain at least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            $fail('Лозинка мора садржати најмање једно велико слово.');
            return;
        }

        // Must contain at least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            $fail('Лозинка мора садржати најмање једно мало слово.');
            return;
        }

        // Must contain at least one digit
        if (!preg_match('/[0-9]/', $value)) {
            $fail('Лозинка мора садржати најмање један број.');
            return;
        }

        // Must contain at least one special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\/;~`]/', $value)) {
            $fail('Лозинка мора садржати најмање један специјални карактер (!@#$%^&*...).');
            return;
        }

        // Check for common weak passwords
        $weakPasswords = [
            'password', 'password123', '12345678', 'qwerty123',
            'admin123', 'letmein', 'welcome123', 'Password1!',
            'Password123', 'Admin123!', 'Test1234!', 'Qwerty123!'
        ];

        if (in_array(strtolower($value), array_map('strtolower', $weakPasswords))) {
            $fail('Лозинка је превише једноставна. Молимо изаберите јачу лозинку.');
            return;
        }
    }
}

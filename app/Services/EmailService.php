<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class EmailService
{
    /**
     * Send 2FA code email.
     *
     * @param string $email
     * @param string $code
     * @param string $userName
     * @return bool
     */
    public function send2FACode(string $email, string $code, string $userName): bool
    {
        try {
            // Configure SMTP dynamically
            $this->configureSMTP();

            Mail::send('emails.2fa-code', [
                'code' => $code,
                'userName' => $userName,
                'expiryMinutes' => 5,
            ], function ($message) use ($email, $userName) {
                $message->to($email, $userName)
                    ->subject('Ваш 2FA код за верификацију');
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send 2FA email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure SMTP settings dynamically from database.
     *
     * @return void
     */
    protected function configureSMTP(): void
    {
        $settings = Setting::getEmailSettings();

        // Configure mail settings
        Config::set('mail.mailers.smtp.host', $settings['smtp_host']);
        Config::set('mail.mailers.smtp.port', $settings['smtp_port']);
        Config::set('mail.mailers.smtp.encryption', $settings['enable_ssl'] ? 'tls' : null);
        Config::set('mail.mailers.smtp.username', $settings['from_address']);
        Config::set('mail.mailers.smtp.password', $settings['password']);
        Config::set('mail.from.address', $settings['from_address']);
        Config::set('mail.from.name', config('app.name'));
    }

    /**
     * Test email connection.
     *
     * @param string $testEmail
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(string $testEmail): array
    {
        try {
            $this->configureSMTP();

            Mail::raw('Ово је тест email из Employee Attendance система.', function ($message) use ($testEmail) {
                $message->to($testEmail)
                    ->subject('Test Email - Employee Attendance');
            });

            return [
                'success' => true,
                'message' => 'Email успешно послат на ' . $testEmail,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Грешка при слању email-а: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send password reset email.
     *
     * @param string $email
     * @param string $userName
     * @param string $tempPassword
     * @return bool
     */
    public function sendPasswordReset(string $email, string $userName, string $tempPassword): bool
    {
        try {
            $this->configureSMTP();

            Mail::send('emails.password-reset', [
                'userName' => $userName,
                'tempPassword' => $tempPassword,
            ], function ($message) use ($email, $userName) {
                $message->to($email, $userName)
                    ->subject('Ресетована лозинка');
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
            return false;
        }
    }
}

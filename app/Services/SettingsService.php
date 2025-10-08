<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SettingsService
{
    /**
     * Get all settings.
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        return Setting::getAll();
    }

    /**
     * Get two-factor authentication status.
     *
     * @return bool
     */
    public function getTwoFactorEnabled(): bool
    {
        return Setting::isTwoFactorEnabled();
    }

    /**
     * Set two-factor authentication status.
     *
     * @param bool $enabled
     * @return bool
     */
    public function setTwoFactorEnabled(bool $enabled): bool
    {
        return Setting::set('TwoFactorEnabled', $enabled ? '1' : '0');
    }

    /**
     * Get auto-logout enabled status.
     *
     * @return bool
     */
    public function getAutoLogoutEnabled(): bool
    {
        return Setting::isAutoLogoutEnabled();
    }

    /**
     * Set auto-logout enabled status.
     *
     * @param bool $enabled
     * @return bool
     */
    public function setAutoLogoutEnabled(bool $enabled): bool
    {
        return Setting::set('AutoLogoutEnabled', $enabled ? '1' : '0');
    }

    /**
     * Get auto-logout time in minutes.
     *
     * @return int
     */
    public function getAutoLogoutTime(): int
    {
        return Setting::getAutoLogoutTime();
    }

    /**
     * Set auto-logout time in minutes.
     *
     * @param int $minutes
     * @return bool
     * @throws ValidationException
     */
    public function setAutoLogoutTime(int $minutes): bool
    {
        $validator = Validator::make(
            ['minutes' => $minutes],
            [
                'minutes' => 'required|integer|min:5|max:480',
            ],
            [
                'minutes.min' => 'Време мора бити најмање 5 минута.',
                'minutes.max' => 'Време не може бити више од 480 минута (8 сати).',
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Setting::set('AutoLogoutTime', (string) $minutes);
    }

    /**
     * Get silent auto-logout status.
     *
     * @return bool
     */
    public function getSilentAutoLogout(): bool
    {
        return Setting::isSilentAutoLogout();
    }

    /**
     * Set silent auto-logout status.
     *
     * @param bool $enabled
     * @return bool
     */
    public function setSilentAutoLogout(bool $enabled): bool
    {
        return Setting::set('SilentAutoLogout', $enabled ? '1' : '0');
    }

    /**
     * Get email settings.
     *
     * @return array
     */
    public function getEmailSettings(): array
    {
        return Setting::getEmailSettings();
    }

    /**
     * Set email settings.
     *
     * @param array $settings
     * @return bool
     * @throws ValidationException
     */
    public function setEmailSettings(array $settings): bool
    {
        $validator = Validator::make($settings, [
            'from_address' => 'required|email',
            'password' => 'required|string',
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'enable_ssl' => 'required|boolean',
        ], [
            'from_address.required' => 'Email адреса је обавезна.',
            'from_address.email' => 'Унесите валидну email адресу.',
            'password.required' => 'Лозинка је обавезна.',
            'smtp_host.required' => 'SMTP хост је обавезан.',
            'smtp_port.required' => 'SMTP порт је обавезан.',
            'smtp_port.integer' => 'SMTP порт мора бити број.',
            'smtp_port.min' => 'SMTP порт мора бити између 1 и 65535.',
            'smtp_port.max' => 'SMTP порт мора бити између 1 и 65535.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        Setting::set('EmailFromAddress', $settings['from_address']);
        Setting::set('EmailPassword', $settings['password']); // TODO: Encrypt in production
        Setting::set('SmtpHost', $settings['smtp_host']);
        Setting::set('SmtpPort', (string) $settings['smtp_port']);
        Setting::set('EnableSsl', $settings['enable_ssl'] ? '1' : '0');

        return true;
    }

    /**
     * Test email connection.
     *
     * @param array $settings
     * @return bool
     */
    public function testEmailConnection(array $settings): bool
    {
        try {
            // Configure mail settings dynamically
            config([
                'mail.mailers.smtp.host' => $settings['smtp_host'],
                'mail.mailers.smtp.port' => $settings['smtp_port'],
                'mail.mailers.smtp.encryption' => $settings['enable_ssl'] ? 'ssl' : null,
                'mail.mailers.smtp.username' => $settings['from_address'],
                'mail.mailers.smtp.password' => $settings['password'],
                'mail.from.address' => $settings['from_address'],
            ]);

            // Try to send test email
            \Illuminate\Support\Facades\Mail::raw('Test email from Employee Attendance System', function ($message) use ($settings) {
                $message->to($settings['from_address'])
                    ->subject('Test Email');
            });

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update a single setting.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function updateSetting(string $key, $value): bool
    {
        return Setting::set($key, $value);
    }

    /**
     * Get a single setting.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Clear settings cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Setting::clearCache();
    }

    /**
     * Get settings for client-side (safe subset).
     * Do not expose sensitive data like email password.
     *
     * @return array
     */
    public function getClientSettings(): array
    {
        return [
            'two_factor_enabled' => $this->getTwoFactorEnabled(),
            'auto_logout_enabled' => $this->getAutoLogoutEnabled(),
            'auto_logout_time' => $this->getAutoLogoutTime(),
            'silent_auto_logout' => $this->getSilentAutoLogout(),
        ];
    }
}

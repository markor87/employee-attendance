<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Display settings page with all current settings.
     */
    public function index()
    {
        // Check if user is SuperAdmin
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Само SuperAdmin може приступити подешавањима.');
        }

        // Get all settings
        $settings = [
            // Security
            'TwoFactorEnabled' => Setting::getBool('TwoFactorEnabled', false),

            // Auto Logout
            'AutoLogoutEnabled' => Setting::getBool('AutoLogoutEnabled', false),
            'AutoLogoutTime' => Setting::get('AutoLogoutTime', '18:00:00'),

            // Email (read-only from .env)
            'EmailFromAddress' => env('MAIL_USERNAME', env('MAIL_FROM_ADDRESS', '')),
            'SmtpHost' => env('MAIL_HOST', 'smtp.gmail.com'),
            'SmtpPort' => (int) env('MAIL_PORT', 587),
            'EnableSsl' => env('MAIL_ENCRYPTION', 'tls') === 'tls',

            // Reminders
            'ReminderEnabled' => Setting::getBool('ReminderEnabled', false),
            'ReminderCheckInTime' => Setting::get('ReminderCheckInTime', '07:25:00'),
            'ReminderCheckOutTime' => Setting::get('ReminderCheckOutTime', '15:25:00'),
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        // Check if user is SuperAdmin
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Само SuperAdmin може мењати подешавања.');
        }

        $validated = $request->validate([
            'TwoFactorEnabled' => 'sometimes|boolean',
            'AutoLogoutEnabled' => 'sometimes|boolean',
            'AutoLogoutTime' => 'sometimes|string|regex:/^\d{2}:\d{2}:\d{2}$/',
            'ReminderEnabled' => 'sometimes|boolean',
            'ReminderCheckInTime' => 'sometimes|string|regex:/^\d{2}:\d{2}:\d{2}$/',
            'ReminderCheckOutTime' => 'sometimes|string|regex:/^\d{2}:\d{2}:\d{2}$/',
        ]);

        // Update each setting
        foreach ($validated as $key => $value) {
            // Convert boolean to string for storage
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            Setting::set($key, $value);
        }

        // Clear settings cache
        Setting::clearCache();

        // Also clear Laravel application cache
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');

        return back()->with('success', 'Подешавања су успешно сачувана.');
    }

    /**
     * Test email configuration.
     */
    public function testEmail(Request $request)
    {
        // Check if user is SuperAdmin
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            // Get email settings from .env
            $emailFromAddress = env('MAIL_USERNAME', env('MAIL_FROM_ADDRESS', ''));
            $emailPassword = env('MAIL_PASSWORD', '');
            $smtpHost = env('MAIL_HOST', 'smtp.gmail.com');
            $smtpPort = (int) env('MAIL_PORT', 587);
            $enableSsl = env('MAIL_ENCRYPTION', 'tls') === 'tls';

            // Validate settings
            if (empty($emailFromAddress) || empty($emailPassword) || empty($smtpHost)) {
                return back()->withErrors([
                    'email' => 'Email подешавања нису конфигурисана у .env фајлу.',
                ]);
            }

            // Configure mail settings
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $smtpHost,
                'mail.mailers.smtp.port' => $smtpPort,
                'mail.mailers.smtp.encryption' => $enableSsl ? 'tls' : null,
                'mail.mailers.smtp.username' => $emailFromAddress,
                'mail.mailers.smtp.password' => $emailPassword,
                'mail.mailers.smtp.timeout' => 30,
                'mail.from.address' => $emailFromAddress,
                'mail.from.name' => 'Employee Attendance System',
            ]);

            // Re-create mail manager to use new config
            app()->forgetInstance('mail.manager');

            // Send test email
            Mail::raw('Ово је тест порука са Employee Attendance система.' . "\n\n" .
                      'Ако сте примили ову поруку, ваша email конфигурација ради исправно!' . "\n\n" .
                      'Конфигурација:' . "\n" .
                      'SMTP Host: ' . $smtpHost . "\n" .
                      'SMTP Port: ' . $smtpPort . "\n" .
                      'From: ' . $emailFromAddress,
                      function ($message) use ($validated) {
                $message->to($validated['test_email'])
                    ->subject('Test Email - Employee Attendance');
            });

            return back()->with('success', 'Тест email је успешно послат на ' . $validated['test_email']);
        } catch (\Exception $e) {
            \Log::error('Email test failed: ' . $e->getMessage());
            \Log::error('Email test trace: ' . $e->getTraceAsString());
            return back()->withErrors([
                'email' => 'Грешка при слању email-а: ' . $e->getMessage(),
            ]);
        }
    }
}

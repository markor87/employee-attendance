<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendReminderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to checked-in users at specified times';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if reminders are enabled
        $reminderEnabled = Setting::getBool('ReminderEnabled', false);

        if (!$reminderEnabled) {
            $this->info('Email reminders are disabled.');
            return 0;
        }

        // Get the configured reminder times
        $checkInTime = Setting::get('ReminderCheckInTime', '07:25:00');
        $checkOutTime = Setting::get('ReminderCheckOutTime', '15:25:00');

        // Get current time in HH:MM format
        $currentTime = Carbon::now()->format('H:i');
        $checkInConfigured = substr($checkInTime, 0, 5); // Extract HH:MM from HH:MM:SS
        $checkOutConfigured = substr($checkOutTime, 0, 5);

        $reminderType = null;
        $subject = '';
        $message = '';

        // Determine which reminder to send
        if ($currentTime === $checkInConfigured) {
            $reminderType = 'check-in';
            $subject = 'Подсетник за пријаву на посао';
            $message = 'Добар дан! Ово је подсетник да се пријавите на посао.';
        } elseif ($currentTime === $checkOutConfigured) {
            $reminderType = 'check-out';
            $subject = 'Подсетник за одјаву са посла';
            $message = 'Добар дан! Ово је подсетник да се одјавите са посла.';
        } else {
            $this->info("Current time ($currentTime) does not match any reminder times.");
            return 0;
        }

        $this->info("Sending $reminderType reminder at $currentTime");

        // Get users based on reminder type
        if ($reminderType === 'check-in') {
            // Check-in reminder: send to users who are logged out (Odjavljen)
            $allUsers = User::where('Status', 'Odjavljen')->get();
            $users = User::where('Status', 'Odjavljen')->whereNotNull('Email')->get();
        } else {
            // Check-out reminder: send to users who are logged in (Prijavljen)
            $allUsers = User::where('Status', 'Prijavljen')->get();
            $users = User::where('Status', 'Prijavljen')->whereNotNull('Email')->get();
        }

        $this->info("Total users with appropriate status: {$allUsers->count()}");
        $this->info("Users with valid email addresses: {$users->count()}");
        Log::info("$reminderType reminders: {$allUsers->count()} users found, {$users->count()} with valid emails");

        if ($users->isEmpty()) {
            $this->info("No users with valid email addresses for $reminderType reminder.");
            return 0;
        }

        $sentCount = 0;

        // Configure mail settings from database
        $this->configureMailSettings();

        foreach ($users as $user) {
            try {
                $this->info("Attempting to send to: {$user->FirstName} {$user->LastName} ({$user->Email})");
                Log::info("Sending $reminderType reminder to: {$user->Email}");

                Mail::raw($message, function ($mail) use ($user, $subject) {
                    $mail->to($user->Email)
                        ->subject($subject);
                });

                $sentCount++;
                $this->info("✓ Successfully sent to: {$user->Email}");
                Log::info("Successfully sent $reminderType reminder to: {$user->Email}");

            } catch (\Exception $e) {
                Log::error("Failed to send $reminderType reminder to {$user->Email}: " . $e->getMessage());
                $this->error("✗ Failed to send to {$user->Email}: " . $e->getMessage());
            }
        }

        $this->info("Reminder emails sent: $sentCount");
        Log::info("$reminderType reminders: $sentCount emails sent at $currentTime");

        return 0;
    }

    /**
     * Configure mail settings from .env
     */
    private function configureMailSettings()
    {
        $emailFromAddress = env('MAIL_USERNAME', env('MAIL_FROM_ADDRESS', ''));
        $emailPassword = env('MAIL_PASSWORD', '');
        $smtpHost = env('MAIL_HOST', 'smtp.gmail.com');
        $smtpPort = (int) env('MAIL_PORT', 587);
        $enableSsl = env('MAIL_ENCRYPTION', 'tls') === 'tls';

        if (empty($emailFromAddress) || empty($emailPassword)) {
            throw new \Exception('Email settings are not configured properly in .env file.');
        }

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
    }
}

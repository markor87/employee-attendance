<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderEmail;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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

        $dispatchedCount = 0;

        // Dispatch jobs to Queue for each user
        foreach ($users as $user) {
            try {
                $this->info("Dispatching {$reminderType} reminder to Queue for: {$user->FirstName} {$user->LastName} ({$user->Email})");

                SendReminderEmail::dispatch($user, $reminderType, $subject, $message);

                $dispatchedCount++;
                $this->info("✓ Dispatched to Queue: {$user->Email}");

            } catch (\Exception $e) {
                Log::error("Failed to dispatch $reminderType reminder for {$user->Email}: " . $e->getMessage());
                $this->error("✗ Failed to dispatch {$user->Email}: " . $e->getMessage());
            }
        }

        $this->info("Dispatched $dispatchedCount {$reminderType} reminder jobs to Queue");
        Log::info("$reminderType reminders: $dispatchedCount jobs dispatched to Queue at $currentTime");

        return 0;
    }
}

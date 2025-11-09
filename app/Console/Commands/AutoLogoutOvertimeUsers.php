<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\TimeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoLogoutOvertimeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:auto-logout-overtime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically logout users who did not respond to overtime presence check';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for users who need overtime auto-logout...');

        // Get settings
        $overtimeCheckTime = DB::table('Settings')
            ->where('SettingKey', 'overtime_check_time')
            ->value('SettingValue') ?? '15:30';

        $promptTimeout = (int) (DB::table('Settings')
            ->where('SettingKey', 'overtime_prompt_timeout')
            ->value('SettingValue') ?? 10);

        $workingDays = explode(',', DB::table('Settings')
            ->where('SettingKey', 'overtime_working_days')
            ->value('SettingValue') ?? 'Mon,Tue,Wed,Thu,Fri');

        $currentTime = Carbon::now();
        $checkTime = Carbon::parse($currentTime->format('Y-m-d') . ' ' . $overtimeCheckTime);

        // Check if today is a working day
        if (!in_array($currentTime->format('D'), $workingDays)) {
            $this->info('Today is not a working day. Skipping overtime check.');
            return 0;
        }

        // Only run after overtime check time
        if ($currentTime->lt($checkTime)) {
            $this->info("Current time ({$currentTime->format('H:i')}) is before overtime check time ({$overtimeCheckTime}). Skipping.");
            return 0;
        }

        // Find users who:
        // 1. Are currently checked in (Status = 'Prijavljen')
        // 2. Have been shown the overtime prompt (overtime_prompt_shown_at is not null)
        // 3. Have not responded within the timeout period
        $usersToLogout = User::where('Status', 'Prijavljen')
            ->whereNotNull('overtime_prompt_shown_at')
            ->get()
            ->filter(function ($user) use ($currentTime, $promptTimeout) {
                $promptShownAt = Carbon::parse($user->overtime_prompt_shown_at);
                $lastActivityAt = $user->last_activity_at ? Carbon::parse($user->last_activity_at) : null;

                // Calculate minutes since prompt was shown
                $minutesSincePrompt = $currentTime->diffInMinutes($promptShownAt);

                // User should be logged out if:
                // - More than timeout minutes have passed since prompt was shown
                // - AND (no activity recorded OR activity was BEFORE the prompt)
                $shouldLogout = $minutesSincePrompt > $promptTimeout &&
                    (!$lastActivityAt || $lastActivityAt->lte($promptShownAt));

                if ($shouldLogout) {
                    $this->info("User {$user->UserID} ({$user->FirstName} {$user->LastName}): " .
                        "prompt shown {$minutesSincePrompt} min ago, timeout is {$promptTimeout} min");
                }

                return $shouldLogout;
            });

        if ($usersToLogout->isEmpty()) {
            $this->info('No users need overtime auto-logout.');
            return 0;
        }

        $loggedOutCount = 0;

        foreach ($usersToLogout as $user) {
            try {
                DB::beginTransaction();

                Log::info("Overtime auto-logout: Processing user {$user->UserID} ({$user->FirstName} {$user->LastName})");

                // Find active TimeLog
                $activeLog = TimeLog::where('UserID', $user->UserID)
                    ->whereNull('VremeOdjave')
                    ->latest('VremePrijave')
                    ->first();

                if ($activeLog) {
                    // Close the TimeLog with auto-logout reason
                    $activeLog->update([
                        'VremeOdjave' => Carbon::now(),
                        'RazlogOdjave' => 'Аутоматска одјава (одсуство одговора на присуство)',
                        'IpAdresaOdjave' => 'SERVER',
                        'PerformedByOdjava' => $user->UserID,
                        'Napomena' => ($activeLog->Napomena ? $activeLog->Napomena . '; ' : '') .
                            'Корисник није одговорио на упит о присуству након истека времена (server-side auto-logout).',
                    ]);

                    Log::info("Overtime auto-logout: TimeLog {$activeLog->LogID} closed for user {$user->UserID}");
                } else {
                    Log::warning("Overtime auto-logout: User {$user->UserID} is checked in but has no active TimeLog");
                }

                // Update user status
                $user->update(['Status' => 'Odjavljen']);

                // Invalidate all sessions for this user
                try {
                    $deletedSessions = DB::table('sessions')
                        ->where('user_id', $user->UserID)
                        ->delete();
                    Log::info("Overtime auto-logout: Deleted {$deletedSessions} sessions for user {$user->UserID}");
                } catch (\Exception $sessionError) {
                    Log::warning("Overtime auto-logout: Failed to delete sessions for user {$user->UserID}: " . $sessionError->getMessage());
                }

                DB::commit();

                $loggedOutCount++;
                $this->info("✓ Logged out user: {$user->FirstName} {$user->LastName} (ID: {$user->UserID})");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Overtime auto-logout failed for user {$user->UserID}: " . $e->getMessage());
                Log::error("Stack trace: " . $e->getTraceAsString());
                $this->error("✗ Failed to logout user: {$user->FirstName} {$user->LastName}");
            }
        }

        $this->info("Overtime auto-logout completed. Logged out {$loggedOutCount} user(s).");
        Log::info("Overtime auto-logout: {$loggedOutCount} user(s) logged out due to no response to overtime prompt");

        return 0;
    }
}

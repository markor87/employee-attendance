<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\TimeLog;
use App\Models\Setting;
use App\Models\Reason;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoLogoutUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:auto-logout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically logout all authenticated users at specified time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if auto-logout is enabled
        $autoLogoutEnabled = Setting::getBool('AutoLogoutEnabled', false);

        if (!$autoLogoutEnabled) {
            $this->info('Auto-logout is disabled.');
            return 0;
        }

        // Get the configured logout time
        $autoLogoutTime = Setting::get('AutoLogoutTime', '18:00:00');

        // Get current time in HH:MM format
        $currentTime = Carbon::now()->format('H:i');
        $configuredTime = substr($autoLogoutTime, 0, 5); // Extract HH:MM from HH:MM:SS

        // Check if current time matches configured time
        if ($currentTime !== $configuredTime) {
            $this->info("Current time ($currentTime) does not match configured logout time ($configuredTime).");
            return 0;
        }

        $this->info("Auto-logout triggered at $currentTime");

        // Get "Automatska odjava" reason
        $autoLogoutReason = Reason::where('ReasonName', 'Automatska odjava')
            ->where('ReasonType', 'Odlazak')
            ->first();

        if (!$autoLogoutReason) {
            Log::error('Auto-logout reason not found in Reasons table');
            $this->error('Auto-logout reason not found. Please run the migration.');
            return 1;
        }

        // Get all authenticated users from sessions table
        $authenticatedUserIds = DB::table('sessions')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        if ($authenticatedUserIds->isEmpty()) {
            $this->info('No authenticated users found.');
            return 0;
        }

        // Get User models for these authenticated users
        $authenticatedUsers = User::whereIn('UserID', $authenticatedUserIds)->get();

        if ($authenticatedUsers->isEmpty()) {
            $this->info('No users to logout.');
            return 0;
        }

        $loggedOutCount = 0;

        foreach ($authenticatedUsers as $user) {
            try {
                Log::info("Auto-logout: Processing user {$user->UserID} ({$user->FirstName} {$user->LastName}), current status: {$user->Status}");

                // Find active TimeLog for this user (if they were checked in at work)
                $activeLog = TimeLog::where('UserID', $user->UserID)
                    ->whereNull('VremeOdjave')
                    ->latest('VremePrijave')
                    ->first();

                if ($activeLog) {
                    // Close the TimeLog with auto-logout reason
                    $activeLog->VremeOdjave = Carbon::now();
                    $activeLog->RazlogOdjave = $autoLogoutReason->ReasonName;
                    $activeLog->save();
                    Log::info("Auto-logout: TimeLog {$activeLog->LogID} closed for user {$user->UserID}");
                } else {
                    Log::info("Auto-logout: User {$user->UserID} was logged in but not checked in at work (no active TimeLog)");
                }

                // Update user status to Odjavljen
                $oldStatus = $user->Status;
                $user->Status = 'Odjavljen';
                $user->save();

                // Force refresh to verify
                $user->refresh();
                Log::info("Auto-logout: User {$user->UserID} status changed from '{$oldStatus}' to '{$user->Status}'");

                // Invalidate all sessions for this user (in separate try-catch)
                try {
                    $deletedSessions = DB::table('sessions')
                        ->where('user_id', $user->UserID)
                        ->delete();
                    Log::info("Auto-logout: Deleted {$deletedSessions} sessions for user {$user->UserID}");
                } catch (\Exception $sessionError) {
                    // Log session deletion error but don't fail the entire logout
                    Log::warning("Auto-logout: Failed to delete sessions for user {$user->UserID}: " . $sessionError->getMessage());
                    Log::warning("Auto-logout: User {$user->UserID} status was updated, but sessions could not be deleted");
                }

                $loggedOutCount++;
                $this->info("Logged out user: {$user->FirstName} {$user->LastName} (ID: {$user->UserID})");

            } catch (\Exception $e) {
                Log::error("Failed to auto-logout user {$user->UserID}: " . $e->getMessage());
                Log::error("Stack trace: " . $e->getTraceAsString());
                $this->error("Failed to logout user: {$user->FirstName} {$user->LastName}");
            }
        }

        $this->info("Auto-logout completed. Logged out $loggedOutCount users.");
        Log::info("Auto-logout: $loggedOutCount authenticated users logged out at $currentTime");

        return 0;
    }
}

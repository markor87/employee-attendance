<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoginAttemptService
{
    /**
     * Maximum failed attempts before lockout.
     */
    const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in minutes.
     */
    const LOCKOUT_MINUTES = 15;

    /**
     * Record a failed login attempt.
     *
     * @param string $email
     * @param string $ipAddress
     * @return void
     */
    public function recordFailedAttempt(string $email, string $ipAddress): void
    {
        DB::table('failed_login_attempts')->insert([
            'email' => strtolower($email),
            'ip_address' => $ipAddress,
            'attempted_at' => Carbon::now(),
        ]);
    }

    /**
     * Check if account is locked out.
     *
     * @param string $email
     * @return bool
     */
    public function isLockedOut(string $email): bool
    {
        $attempts = $this->getRecentAttempts($email);
        return $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Get number of recent failed attempts.
     *
     * @param string $email
     * @return int
     */
    public function getRecentAttempts(string $email): int
    {
        $since = Carbon::now()->subMinutes(self::LOCKOUT_MINUTES);

        return DB::table('failed_login_attempts')
            ->where('email', strtolower($email))
            ->where('attempted_at', '>=', $since)
            ->count();
    }

    /**
     * Get remaining lockout time in seconds.
     *
     * @param string $email
     * @return int|null
     */
    public function getLockoutRemaining(string $email): ?int
    {
        $since = Carbon::now()->subMinutes(self::LOCKOUT_MINUTES);

        $oldestAttempt = DB::table('failed_login_attempts')
            ->where('email', strtolower($email))
            ->where('attempted_at', '>=', $since)
            ->orderBy('attempted_at', 'asc')
            ->first();

        if (!$oldestAttempt) {
            return null;
        }

        $lockoutEnds = Carbon::parse($oldestAttempt->attempted_at)->addMinutes(self::LOCKOUT_MINUTES);
        $remaining = Carbon::now()->diffInSeconds($lockoutEnds, false);

        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Clear failed attempts for an email.
     *
     * @param string $email
     * @return void
     */
    public function clearAttempts(string $email): void
    {
        DB::table('failed_login_attempts')
            ->where('email', strtolower($email))
            ->delete();
    }

    /**
     * Clean up old failed attempts (older than lockout period).
     *
     * @return void
     */
    public function cleanupOldAttempts(): void
    {
        $since = Carbon::now()->subMinutes(self::LOCKOUT_MINUTES);

        DB::table('failed_login_attempts')
            ->where('attempted_at', '<', $since)
            ->delete();
    }
}

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
     * Maximum failed attempts per IP address before lockout.
     */
    const MAX_IP_ATTEMPTS = 10;

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
     * Check if account is locked out (by email or IP).
     *
     * @param string $email
     * @param string|null $ipAddress
     * @return bool
     */
    public function isLockedOut(string $email, ?string $ipAddress = null): bool
    {
        // Check email-based lockout
        $emailAttempts = $this->getRecentAttempts($email);
        if ($emailAttempts >= self::MAX_ATTEMPTS) {
            return true;
        }

        // Check IP-based lockout (if IP provided)
        if ($ipAddress) {
            $ipAttempts = $this->getRecentIpAttempts($ipAddress);
            if ($ipAttempts >= self::MAX_IP_ATTEMPTS) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get number of recent failed attempts by email.
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
     * Get number of recent failed attempts by IP address.
     *
     * @param string $ipAddress
     * @return int
     */
    public function getRecentIpAttempts(string $ipAddress): int
    {
        $since = Carbon::now()->subMinutes(self::LOCKOUT_MINUTES);

        return DB::table('failed_login_attempts')
            ->where('ip_address', $ipAddress)
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

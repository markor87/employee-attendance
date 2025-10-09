<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class TwoFactorService
{
    /**
     * Session key for 2FA code.
     */
    const CODE_KEY = '2fa_code';

    /**
     * Session key for 2FA code expiry.
     */
    const EXPIRY_KEY = '2fa_expiry';

    /**
     * Session key for 2FA user ID.
     */
    const USER_ID_KEY = '2fa_user_id';

    /**
     * Session key for 2FA verified status.
     */
    const VERIFIED_KEY = '2fa_verified';

    /**
     * Code expiry time in minutes.
     */
    const EXPIRY_MINUTES = 5;

    /**
     * Generate a random 6-digit code.
     *
     * @return string
     */
    public function generateCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Store 2FA code in session.
     *
     * @param string $code
     * @param int $userId
     * @return void
     */
    public function storeCode(string $code, int $userId): void
    {
        Session::put(self::CODE_KEY, $code);
        Session::put(self::EXPIRY_KEY, Carbon::now()->addMinutes(self::EXPIRY_MINUTES));
        Session::put(self::USER_ID_KEY, $userId);
        Session::put(self::VERIFIED_KEY, false);
    }

    /**
     * Verify 2FA code.
     *
     * @param string $code
     * @return bool
     */
    public function verifyCode(string $code): bool
    {
        // Trim whitespace from input code
        $code = trim($code);

        $storedCode = Session::get(self::CODE_KEY);
        $expiry = Session::get(self::EXPIRY_KEY);

        // Log for debugging
        \Log::info("2FA verification attempt", [
            'received_code' => $code,
            'received_length' => strlen($code),
            'stored_code' => $storedCode,
            'stored_length' => $storedCode ? strlen($storedCode) : 0,
            'has_expiry' => !empty($expiry),
            'expiry' => $expiry,
        ]);

        // Check if code exists
        if (!$storedCode || !$expiry) {
            \Log::warning("2FA verification failed: No code or expiry in session");
            return false;
        }

        // Check if code expired
        if (Carbon::parse($expiry)->isPast()) {
            \Log::warning("2FA verification failed: Code expired");
            $this->clearCode();
            return false;
        }

        // Check if code matches
        if ($code !== $storedCode) {
            \Log::warning("2FA verification failed: Code mismatch", [
                'received' => $code,
                'expected' => $storedCode,
            ]);
            return false;
        }

        // Mark as verified
        Session::put(self::VERIFIED_KEY, true);
        \Log::info("2FA verification successful");

        return true;
    }

    /**
     * Check if 2FA is verified for current session.
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return Session::get(self::VERIFIED_KEY, false) === true;
    }

    /**
     * Get user ID from 2FA session.
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return Session::get(self::USER_ID_KEY);
    }

    /**
     * Get code expiry time.
     *
     * @return Carbon|null
     */
    public function getExpiry(): ?Carbon
    {
        $expiry = Session::get(self::EXPIRY_KEY);
        return $expiry ? Carbon::parse($expiry) : null;
    }

    /**
     * Get remaining time in seconds.
     *
     * @return int|null
     */
    public function getRemainingSeconds(): ?int
    {
        $expiry = $this->getExpiry();

        if (!$expiry) {
            return null;
        }

        $remaining = Carbon::now()->diffInSeconds($expiry, false);
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Check if code exists and is not expired.
     *
     * @return bool
     */
    public function hasValidCode(): bool
    {
        $expiry = $this->getExpiry();

        if (!$expiry) {
            return false;
        }

        return !$expiry->isPast();
    }

    /**
     * Clear 2FA code from session.
     *
     * @return void
     */
    public function clearCode(): void
    {
        Session::forget(self::CODE_KEY);
        Session::forget(self::EXPIRY_KEY);
    }

    /**
     * Clear all 2FA session data.
     *
     * @return void
     */
    public function clearAll(): void
    {
        Session::forget(self::CODE_KEY);
        Session::forget(self::EXPIRY_KEY);
        Session::forget(self::USER_ID_KEY);
        Session::forget(self::VERIFIED_KEY);
    }
}

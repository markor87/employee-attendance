<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Helpers\SecurityHelper;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log a security event.
     *
     * @param string $eventType
     * @param string|null $description
     * @param array $metadata
     * @param int|null $userId
     * @return void
     */
    public function log(
        string $eventType,
        ?string $description = null,
        array $metadata = [],
        ?int $userId = null
    ): void {
        $userId = $userId ?? Auth::id();

        AuditLog::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log failed login attempt.
     *
     * @param string $email
     * @param string $reason
     * @return void
     */
    public function logFailedLogin(string $email, string $reason = 'invalid_credentials'): void
    {
        $this->log(
            eventType: 'failed_login',
            description: "Failed login attempt for {$reason}",
            metadata: [
                'email_masked' => SecurityHelper::maskEmail($email),
                'reason' => $reason,
                'ip_masked' => SecurityHelper::maskIp(request()->ip()),
            ],
            userId: null
        );
    }

    /**
     * Log successful login.
     *
     * @param int $userId
     * @return void
     */
    public function logSuccessfulLogin(int $userId): void
    {
        $this->log(
            eventType: 'successful_login',
            description: 'User logged in successfully',
            metadata: [
                'ip_masked' => SecurityHelper::maskIp(request()->ip()),
            ],
            userId: $userId
        );
    }

    /**
     * Log logout.
     *
     * @param int $userId
     * @return void
     */
    public function logLogout(int $userId): void
    {
        $this->log(
            eventType: 'logout',
            description: 'User logged out',
            metadata: [],
            userId: $userId
        );
    }

    /**
     * Log password change.
     *
     * @param int $userId
     * @param bool $wasForced
     * @return void
     */
    public function logPasswordChange(int $userId, bool $wasForced = false): void
    {
        $this->log(
            eventType: 'password_change',
            description: $wasForced ? 'User changed password (forced)' : 'User changed password',
            metadata: [
                'forced' => $wasForced,
            ],
            userId: $userId
        );
    }

    /**
     * Log user creation.
     *
     * @param int $createdUserId
     * @param int $creatorUserId
     * @return void
     */
    public function logUserCreated(int $createdUserId, int $creatorUserId): void
    {
        $this->log(
            eventType: 'user_created',
            description: 'New user created',
            metadata: [
                'created_user_id' => $createdUserId,
            ],
            userId: $creatorUserId
        );
    }

    /**
     * Log user update.
     *
     * @param int $updatedUserId
     * @param int $updaterUserId
     * @param array $changes
     * @return void
     */
    public function logUserUpdated(int $updatedUserId, int $updaterUserId, array $changes = []): void
    {
        $this->log(
            eventType: 'user_updated',
            description: 'User information updated',
            metadata: [
                'updated_user_id' => $updatedUserId,
                'changes' => $changes,
            ],
            userId: $updaterUserId
        );
    }

    /**
     * Log user deletion.
     *
     * @param int $deletedUserId
     * @param int $deleterUserId
     * @return void
     */
    public function logUserDeleted(int $deletedUserId, int $deleterUserId): void
    {
        $this->log(
            eventType: 'user_deleted',
            description: 'User deleted',
            metadata: [
                'deleted_user_id' => $deletedUserId,
            ],
            userId: $deleterUserId
        );
    }

    /**
     * Log 2FA code sent.
     *
     * @param int $userId
     * @return void
     */
    public function log2FASent(int $userId): void
    {
        $this->log(
            eventType: '2fa_code_sent',
            description: '2FA code sent via email',
            metadata: [],
            userId: $userId
        );
    }

    /**
     * Log 2FA verification success.
     *
     * @param int $userId
     * @return void
     */
    public function log2FAVerified(int $userId): void
    {
        $this->log(
            eventType: '2fa_verified',
            description: '2FA code verified successfully',
            metadata: [],
            userId: $userId
        );
    }

    /**
     * Log 2FA verification failure.
     *
     * @param int $userId
     * @return void
     */
    public function log2FAFailed(int $userId): void
    {
        $this->log(
            eventType: '2fa_failed',
            description: '2FA code verification failed',
            metadata: [
                'ip_masked' => SecurityHelper::maskIp(request()->ip()),
            ],
            userId: $userId
        );
    }
}

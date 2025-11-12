<?php

namespace App\Services;

use App\Models\User;
use App\Models\TimeLog;
use App\Models\Reason;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * Check-in user.
     *
     * @param int $userId
     * @param int $reasonId
     * @param string|null $notes
     * @param string|null $ipAddress
     * @param int|null $performedBy
     * @return TimeLog
     * @throws ValidationException
     */
    public function checkIn(
        int $userId,
        int $reasonId,
        ?string $notes = null,
        ?string $ipAddress = null,
        ?int $performedBy = null
    ): TimeLog {
        $user = User::findOrFail($userId);

        // Check if user is already checked in
        if ($user->isCheckedIn()) {
            throw ValidationException::withMessages([
                'attendance' => 'Корисник је већ пријављен.',
            ]);
        }

        // Validate reason exists
        $reason = Reason::findOrFail($reasonId);

        if ($reason->ReasonType !== 'Dolazak') {
            throw ValidationException::withMessages([
                'reason' => 'Разлог мора бити типа "Dolazak".',
            ]);
        }

        // Truncate notes to 500 chars
        $notes = $notes ? substr($notes, 0, 500) : null;

        // Get IP address
        $ipAddress = $ipAddress ?? request()->ip() ?? 'N/A';

        // Create TimeLog
        $timeLog = TimeLog::create([
            'UserID' => $userId,
            'VremePrijave' => now(),
            'RadniDatum' => now()->toDateString(),
            'IpAdresaPrijave' => $ipAddress,
            'RazlogPrijave' => $reasonId,
            'PerformedByPrijava' => $performedBy ?? $userId,
            'Napomena' => $notes,
        ]);

        // Update user status
        $user->Status = 'Prijavljen';
        $user->save();

        return $timeLog;
    }

    /**
     * Check-out user.
     *
     * @param int $userId
     * @param int $reasonId
     * @param string|null $notes
     * @param string|null $ipAddress
     * @param int|null $performedBy
     * @return TimeLog
     * @throws ValidationException
     */
    public function checkOut(
        int $userId,
        int $reasonId,
        ?string $notes = null,
        ?string $ipAddress = null,
        ?int $performedBy = null
    ): TimeLog {
        $user = User::findOrFail($userId);

        // Check if user is checked in
        if (!$user->isCheckedIn()) {
            throw ValidationException::withMessages([
                'attendance' => 'Корисник није пријављен.',
            ]);
        }

        // Validate reason exists
        $reason = Reason::findOrFail($reasonId);

        if ($reason->ReasonType !== 'Odlazak') {
            throw ValidationException::withMessages([
                'reason' => 'Разлог мора бити типа "Odlazak".',
            ]);
        }

        // Get active time log
        $timeLog = $user->activeTimeLog;

        if (!$timeLog) {
            throw ValidationException::withMessages([
                'attendance' => 'Није пронађена активна пријава за одјаву.',
            ]);
        }

        // Get existing check-in note
        $existingNote = $timeLog->CheckInNote;

        // Truncate notes to 500 chars
        $notes = $notes ? substr($notes, 0, 500) : null;

        // Combine notes: "checkInNote;checkOutNote"
        $combinedNotes = $existingNote;
        if ($notes) {
            $combinedNotes = $existingNote ? "{$existingNote};{$notes}" : ";{$notes}";
        }

        // Get IP address
        $ipAddress = $ipAddress ?? request()->ip() ?? 'N/A';

        // Update TimeLog
        $timeLog->VremeOdjave = now();
        $timeLog->IpAdresaOdjave = $ipAddress;
        $timeLog->RazlogOdjave = $reasonId;
        $timeLog->PerformedByOdjava = $performedBy ?? $userId;
        $timeLog->Napomena = $combinedNotes;
        $timeLog->save();

        // Update user status
        $user->Status = 'Odjavljen';
        $user->save();

        return $timeLog;
    }

    /**
     * Force check-in (admin/kadrovik function).
     *
     * @param int $targetUserId
     * @param int $reasonId
     * @param string|null $notes
     * @param int|null $performedBy
     * @return TimeLog
     * @throws ValidationException
     */
    public function forceCheckIn(
        int $targetUserId,
        int $reasonId,
        ?string $notes = null,
        ?int $performedBy = null
    ): TimeLog {
        $performedBy = $performedBy ?? Auth::id();

        // CRITICAL: Self-restriction validation
        if ($targetUserId === $performedBy) {
            throw ValidationException::withMessages([
                'attendance' => 'Не можете да пријавите самог себе преко ове функције. Користите регуларну пријаву.',
            ]);
        }

        // Check if performer has permission
        $performer = User::findOrFail($performedBy);
        if (!$performer->isKadrovik()) {
            throw ValidationException::withMessages([
                'permission' => 'Немате дозволу за ову акцију.',
            ]);
        }

        return $this->checkIn($targetUserId, $reasonId, $notes, 'AUTO', $performedBy);
    }

    /**
     * Force check-out (admin/kadrovik function).
     *
     * @param int $targetUserId
     * @param int $reasonId
     * @param string|null $notes
     * @param int|null $performedBy
     * @return TimeLog
     * @throws ValidationException
     */
    public function forceCheckOut(
        int $targetUserId,
        int $reasonId,
        ?string $notes = null,
        ?int $performedBy = null
    ): TimeLog {
        $performedBy = $performedBy ?? Auth::id();

        // Check if performer has permission
        $performer = User::findOrFail($performedBy);
        if (!$performer->isKadrovik()) {
            throw ValidationException::withMessages([
                'permission' => 'Немате дозволу за ову акцију.',
            ]);
        }

        return $this->checkOut($targetUserId, $reasonId, $notes, 'AUTO', $performedBy);
    }

    /**
     * Get user current status.
     *
     * @param int $userId
     * @return array
     */
    public function getUserStatus(int $userId): array
    {
        $user = User::findOrFail($userId);
        $activeLog = $user->activeTimeLog;

        return [
            'user_id' => $user->UserID,
            'full_name' => $user->FullName,
            'status' => $user->Status,
            'is_checked_in' => $user->isCheckedIn(),
            'active_log' => $activeLog ? [
                'log_id' => $activeLog->LogID,
                'check_in_time' => $activeLog->VremePrijave,
                'reason' => $activeLog->checkInReason?->ReasonName,
                'ip_address' => $activeLog->IpAdresaPrijave,
            ] : null,
        ];
    }

    /**
     * Get user time logs with filters.
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserTimeLogs(int $userId, array $filters = [], int $perPage = 10)
    {
        $query = TimeLog::forUser($userId);

        // Apply date range filter
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        }

        // Order by newest first
        $query->orderBy('VremePrijave', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Admin logout (SuperAdmin only).
     * Logs out from web session but keeps attendance status as "Prijavljen".
     *
     * @param int $userId
     * @return bool
     * @throws ValidationException
     */
    public function adminLogout(int $userId): bool
    {
        $user = User::findOrFail($userId);

        // Only SuperAdmin can use this feature
        if (!$user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'permission' => 'Само SuperAdmin може користити Admin Logout функцију.',
            ]);
        }

        // User status remains "Prijavljen" - we just logout from web session
        // This will be handled in AuthController

        return true;
    }

    /**
     * Auto-logout user after inactivity.
     * This is used by scheduled task or middleware.
     *
     * @param int $userId
     * @param int $reasonId
     * @return TimeLog|null
     */
    public function autoLogout(int $userId, int $reasonId): ?TimeLog
    {
        try {
            return $this->checkOut($userId, $reasonId, 'Auto-logout zbog neaktivnosti', 'AUTO', $userId);
        } catch (ValidationException $e) {
            // User might already be checked out
            return null;
        }
    }
}

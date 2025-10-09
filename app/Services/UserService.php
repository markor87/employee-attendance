<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Verify user password with dual hash support (bcrypt or SHA256).
     *
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function verifyPassword(User $user, string $password): bool
    {
        return $user->verifyPassword($password);
    }

    /**
     * Hash password (always use bcrypt for new passwords).
     *
     * @param string $password
     * @return array ['hash' => string, 'algorithm' => 'bcrypt']
     */
    public function hashPassword(string $password): array
    {
        return [
            'hash' => Hash::make($password),
            'algorithm' => 'bcrypt',
        ];
    }

    /**
     * Validate password strength.
     * Rules: Min 8 chars, at least 1 digit, at least 1 special character.
     *
     * @param string $password
     * @return bool
     * @throws ValidationException
     */
    public function validatePasswordStrength(string $password): bool
    {
        $validator = Validator::make(
            ['password' => $password],
            [
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/[0-9]/',      // At least one digit
                    'regex:/[!@#$%^&*()_+\-=\[\]{};:\\\'"|,.<>\/?]/', // At least one special char
                ],
            ],
            [
                'password.min' => 'Лозинка мора имати минимум 8 карактера.',
                'password.regex' => 'Лозинка мора садржати најмање једну цифру и један специјални карактер (@$!%*#?&).',
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * Check if user can be deleted.
     * Protection: UserID = 1 (SuperAdmin) cannot be deleted.
     *
     * @param int $userId
     * @return bool
     */
    public function canDelete(int $userId): bool
    {
        return $userId !== 1;
    }

    /**
     * Check if user can be edited.
     * Protection: UserID = 1 role and email cannot be changed.
     *
     * @param int $userId
     * @param array $changes
     * @return bool
     */
    public function canEdit(int $userId, array $changes = []): bool
    {
        if ($userId !== 1) {
            return true;
        }

        // UserID=1 exists, check if protected fields are being changed
        $protectedFields = ['Role', 'Email'];

        foreach ($protectedFields as $field) {
            if (isset($changes[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     * @throws ValidationException
     */
    public function createUser(array $data): User
    {
        // Validate password strength
        $this->validatePasswordStrength($data['password']);

        // Hash password
        $passwordData = $this->hashPassword($data['password']);

        // Create user
        $user = User::create([
            'FirstName' => $data['first_name'],
            'LastName' => $data['last_name'],
            'Email' => $data['email'],
            'PasswordHash' => $passwordData['hash'],
            'PasswordHashAlgorithm' => $passwordData['algorithm'],
            'Role' => $data['role'] ?? 'Zaposleni',
            'Status' => 'Odjavljen',
            'PasswordNeedsChange' => $data['password_needs_change'] ?? false,
            'DateCreated' => now(),
        ]);

        return $user;
    }

    /**
     * Update user.
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws ValidationException
     */
    public function updateUser(User $user, array $data): User
    {
        // Check if edit is allowed
        if (!$this->canEdit($user->UserID, $data)) {
            throw ValidationException::withMessages([
                'user' => 'Не можете мењати улогу или email SuperAdmin корисника (UserID=1).',
            ]);
        }

        // Update fields
        if (isset($data['first_name'])) {
            $user->FirstName = $data['first_name'];
        }

        if (isset($data['last_name'])) {
            $user->LastName = $data['last_name'];
        }

        if (isset($data['email']) && $user->UserID !== 1) {
            $user->Email = $data['email'];
        }

        if (isset($data['role']) && $user->UserID !== 1) {
            $user->Role = $data['role'];
        }

        if (isset($data['password'])) {
            $this->validatePasswordStrength($data['password']);
            $user->setPasswordHash($data['password']);
        }

        $user->save();

        return $user;
    }

    /**
     * Delete user.
     *
     * @param int $userId
     * @return bool
     * @throws ValidationException
     */
    public function deleteUser(int $userId): bool
    {
        if (!$this->canDelete($userId)) {
            throw ValidationException::withMessages([
                'user' => 'Не можете обрисати SuperAdmin корисника (UserID=1).',
            ]);
        }

        $user = User::findOrFail($userId);
        return $user->delete();
    }

    /**
     * Force password change for user.
     *
     * @param int $userId
     * @return bool
     */
    public function forcePasswordChange(int $userId): bool
    {
        $user = User::findOrFail($userId);
        $user->PasswordNeedsChange = true;
        return $user->save();
    }

    /**
     * Get users with pagination and filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUsers(array $filters = [], int $perPage = 17)
    {
        $query = User::query();

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply role filter
        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        // Order by UserID
        $query->orderBy('UserID');

        return $query->paginate($perPage);
    }

    /**
     * Get user statistics.
     *
     * @return array
     */
    public function getUserStatistics(): array
    {
        $total = User::count();
        $checkedIn = User::status('Prijavljen')->count();
        $checkedOut = User::status('Odjavljen')->count();

        return [
            'total' => $total,
            'checked_in' => $checkedIn,
            'checked_out' => $checkedOut,
        ];
    }
}

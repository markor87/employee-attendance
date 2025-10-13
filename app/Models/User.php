<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Users';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'UserID';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'FirstName',
        'LastName',
        'Email',
        'PasswordHash',
        'PasswordHashAlgorithm',
        'Role',
        'Status',
        'sector_id',
        'PasswordNeedsChange',
        'DateCreated',
        'DateUpdated',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'PasswordHash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'PasswordNeedsChange' => 'boolean',
            'DateCreated' => 'datetime',
            'DateUpdated' => 'datetime',
        ];
    }

    /**
     * Get the password for authentication.
     * Laravel expects 'password' but our DB has 'PasswordHash'.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->PasswordHash;
    }

    /**
     * Verify password using bcrypt.
     * Uses password_verify() to accept both $2a$ and $2y$ bcrypt variants.
     *
     * @param string $password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->PasswordHash);
    }

    /**
     * Set password hash using bcrypt.
     *
     * @param string $password
     * @return void
     */
    public function setPasswordHash(string $password): void
    {
        $this->PasswordHash = Hash::make($password);
        $this->PasswordHashAlgorithm = 'bcrypt';
    }

    /**
     * Check if user is SuperAdmin.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->Role === 'SuperAdmin';
    }

    /**
     * Check if user is Admin or higher.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return in_array($this->Role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Check if user is Kadrovik (HR) or higher.
     *
     * @return bool
     */
    public function isKadrovik(): bool
    {
        return in_array($this->Role, ['SuperAdmin', 'Admin', 'Kadrovik']);
    }

    /**
     * Check if user is Rukovodilac (Manager).
     *
     * @return bool
     */
    public function isRukovodilac(): bool
    {
        return $this->Role === 'Rukovodilac';
    }

    /**
     * Check if user is currently checked in.
     *
     * @return bool
     */
    public function isCheckedIn(): bool
    {
        return $this->Status === 'Prijavljen';
    }

    /**
     * Get full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->FirstName} {$this->LastName}";
    }

    /**
     * Get current status (including scheduled absence).
     *
     * Prioritizes:
     * 1. Active check-in (Status = "Prijavljen")
     * 2. Currently in scheduled absence (VremePrijave <= NOW <= VremeOdjave)
     * 3. Otherwise "Odjavljen"
     *
     * @return string
     */
    public function getCurrentStatusAttribute(): string
    {
        // 1. If user has active check-in, return "Prijavljen"
        if ($this->Status === 'Prijavljen') {
            return 'Prijavljen';
        }

        // 2. Check if user is currently in a scheduled absence
        $now = now();
        $scheduledAbsence = $this->timeLogs()
            ->whereNotNull('VremeOdjave')
            ->where('VremePrijave', '<=', $now)
            ->where('VremeOdjave', '>=', $now)
            ->exists();

        if ($scheduledAbsence) {
            return 'Службено одсуство';
        }

        // 3. Otherwise, user is checked out
        return 'Odjavljen';
    }

    /**
     * Relationship: User belongs to a Sector.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id', 'id');
    }

    /**
     * Relationship: User has many TimeLogs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class, 'UserID', 'UserID');
    }

    /**
     * Get active (unclosed) time log for user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function activeTimeLog()
    {
        return $this->hasOne(TimeLog::class, 'UserID', 'UserID')
            ->whereNull('VremeOdjave')
            ->latest('VremePrijave');
    }

    /**
     * Scope: Filter by role.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('Role', $role);
    }

    /**
     * Scope: Filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('Status', $status);
    }

    /**
     * Scope: Search by name or email.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('FirstName', 'LIKE', "%{$search}%")
              ->orWhere('LastName', 'LIKE', "%{$search}%")
              ->orWhere('Email', 'LIKE', "%{$search}%");
        });
    }
}

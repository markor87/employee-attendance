<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'TimeLogs';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'LogID';

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
        'UserID',
        'VremePrijave',
        'VremeOdjave',
        'RadniDatum',
        'IpAdresaPrijave',
        'IpAdresaOdjave',
        'RazlogPrijave',    // VARCHAR - reason name, not ID
        'RazlogOdjave',     // VARCHAR - reason name, not ID
        'PerformedByPrijava',
        'PerformedByOdjava',
        'Napomena',
        'DateCreated',
        'DateUpdated',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'VremePrijave' => 'datetime',
            'VremeOdjave' => 'datetime',
            'RadniDatum' => 'date',
            'DateCreated' => 'datetime',
            'DateUpdated' => 'datetime',
        ];
    }

    /**
     * Relationship: TimeLog belongs to User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'UserID');
    }

    /**
     * Relationship: Check-in performed by (admin/kadrovik).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function performedByCheckIn()
    {
        return $this->belongsTo(User::class, 'PerformedByPrijava', 'UserID');
    }

    /**
     * Relationship: Check-out performed by (admin/kadrovik).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function performedByCheckOut()
    {
        return $this->belongsTo(User::class, 'PerformedByOdjava', 'UserID');
    }

    // Note: RazlogPrijave and RazlogOdjave are VARCHAR fields storing reason names directly,
    // not foreign keys. No relationship to Reason model.

    /**
     * Get check-in notes.
     * Notes are stored as "checkInNote;checkOutNote".
     *
     * @return string|null
     */
    public function getCheckInNoteAttribute(): ?string
    {
        if (!$this->Napomena) {
            return null;
        }

        $parts = explode(';', $this->Napomena, 2);
        return $parts[0] ?? null;
    }

    /**
     * Get check-out notes.
     * Notes are stored as "checkInNote;checkOutNote".
     *
     * @return string|null
     */
    public function getCheckOutNoteAttribute(): ?string
    {
        if (!$this->Napomena) {
            return null;
        }

        $parts = explode(';', $this->Napomena, 2);
        return $parts[1] ?? null;
    }

    /**
     * Set combined notes (check-in + check-out).
     *
     * @param string|null $checkInNote
     * @param string|null $checkOutNote
     * @return void
     */
    public function setCombinedNotes(?string $checkInNote, ?string $checkOutNote): void
    {
        $notes = [];

        if ($checkInNote) {
            $notes[] = substr($checkInNote, 0, 500);
        }

        if ($checkOutNote) {
            if (empty($notes)) {
                $notes[] = ''; // Empty check-in note
            }
            $notes[] = substr($checkOutNote, 0, 500);
        }

        $this->Napomena = !empty($notes) ? implode(';', $notes) : null;
    }

    /**
     * Check if time log is active (not checked out yet).
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->VremeOdjave === null;
    }

    /**
     * Get total work duration in minutes.
     *
     * @return int|null
     */
    public function getDurationInMinutes(): ?int
    {
        if (!$this->VremeOdjave) {
            return null;
        }

        return $this->VremePrijave->diffInMinutes($this->VremeOdjave);
    }

    /**
     * Get formatted duration (HH:MM).
     *
     * @return string|null
     */
    public function getFormattedDuration(): ?string
    {
        $minutes = $this->getDurationInMinutes();

        if ($minutes === null) {
            return null;
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * Scope: Filter by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('UserID', $userId);
    }

    /**
     * Scope: Filter by date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('RadniDatum', [$startDate, $endDate]);
    }

    /**
     * Scope: Only active (unclosed) logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('VremeOdjave');
    }

    /**
     * Scope: Only completed (closed) logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('VremeOdjave');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reasons';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ReasonID';

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
        'ReasonName',
        'ReasonType',
    ];

    // Note: RazlogPrijave and RazlogOdjave are VARCHAR fields storing reason names,
    // not foreign keys to this table. Relationships removed.

    /**
     * Scope: Filter by type (Dolazak or Odlazak).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query, string $type)
    {
        return $query->where('ReasonType', $type);
    }

    /**
     * Scope: Get only "Dolazak" (check-in) reasons.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDolazak($query)
    {
        return $query->where('ReasonType', 'Dolazak');
    }

    /**
     * Scope: Get only "Odlazak" (check-out) reasons.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOdlazak($query)
    {
        return $query->where('ReasonType', 'Odlazak');
    }

    /**
     * Scope: Exclude normal reason (ReasonID = 1).
     * Used for force check-in/out where "Dolazak na posao" is not appropriate.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExcludeNormal($query)
    {
        return $query->where('ReasonID', '!=', 1);
    }

    /**
     * Get the normal check-in reason (ReasonID = 1).
     * CRITICAL: This MUST be "Dolazak na posao".
     *
     * @return self|null
     */
    public static function getNormalCheckIn(): ?self
    {
        return static::find(1);
    }

    /**
     * Check if this is the normal check-in reason.
     *
     * @return bool
     */
    public function isNormalCheckIn(): bool
    {
        return $this->ReasonID === 1;
    }

    /**
     * Validate that ReasonID=1 is "Dolazak na posao".
     * This is a critical business rule.
     *
     * @return bool
     */
    public static function validateNormalReason(): bool
    {
        $reason = static::find(1);

        if (!$reason) {
            return false;
        }

        return $reason->ReasonName === 'Dolazak na posao'
            && $reason->ReasonType === 'Dolazak';
    }
}

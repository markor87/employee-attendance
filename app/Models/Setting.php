<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Settings';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'SettingKey';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

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
        'SettingKey',
        'SettingValue',
    ];

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // Use shorter cache for critical settings like TwoFactorEnabled
        $cacheDuration = in_array($key, ['TwoFactorEnabled', 'AutoLogoutEnabled']) ? 60 : 3600;

        return Cache::remember("setting.{$key}", $cacheDuration, function () use ($key, $default) {
            $setting = static::find($key);
            return $setting ? $setting->SettingValue : $default;
        });
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set(string $key, $value): bool
    {
        $setting = static::updateOrCreate(
            ['SettingKey' => $key],
            ['SettingValue' => $value]
        );

        // Clear cache
        Cache::forget("setting.{$key}");

        return $setting->exists;
    }

    /**
     * Get boolean setting value.
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        // Convert string to lowercase for proper boolean parsing
        if (is_string($value)) {
            $value = strtolower(trim($value));
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get integer setting value.
     *
     * @param string $key
     * @param int $default
     * @return int
     */
    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::get($key, $default);
    }

    /**
     * Get all settings as array.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return static::all()->pluck('SettingValue', 'SettingKey')->toArray();
    }

    /**
     * Clear all settings cache.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $keys = static::all()->pluck('SettingKey');

        foreach ($keys as $key) {
            Cache::forget("setting.{$key}");
        }
    }

    /**
     * Get two-factor authentication status.
     *
     * @return bool
     */
    public static function isTwoFactorEnabled(): bool
    {
        return static::getBool('TwoFactorEnabled', false);
    }

    /**
     * Get auto-logout status.
     *
     * @return bool
     */
    public static function isAutoLogoutEnabled(): bool
    {
        return static::getBool('AutoLogoutEnabled', false);
    }

    /**
     * Get auto-logout time in minutes.
     *
     * @return int
     */
    public static function getAutoLogoutTime(): int
    {
        return static::getInt('AutoLogoutTime', 30);
    }

    /**
     * Get silent auto-logout status.
     *
     * @return bool
     */
    public static function isSilentAutoLogout(): bool
    {
        return static::getBool('SilentAutoLogout', false);
    }

    /**
     * Get email settings from .env file.
     *
     * @return array
     */
    public static function getEmailSettings(): array
    {
        return [
            'from_address' => env('MAIL_USERNAME', env('MAIL_FROM_ADDRESS', '')),
            'password' => env('MAIL_PASSWORD', ''),
            'smtp_host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'smtp_port' => (int) env('MAIL_PORT', 587),
            'enable_ssl' => env('MAIL_ENCRYPTION', 'tls') === 'tls',
        ];
    }
}

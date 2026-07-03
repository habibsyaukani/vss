<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    /**
     * Get setting value dengan type casting
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set setting value
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }

    /**
     * Cast value berdasarkan type
     */
    private static function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Check apakah cleanup enabled
     */
    public static function isCleanupEnabled(): bool
    {
        return (bool) static::get('cleanup_enabled', true);
    }

    /**
     * Get cleanup retention days
     */
    public static function getCleanupRetentionDays(): int
    {
        return (int) static::get('cleanup_retention_days', 30);
    }

    /**
     * Update cleanup last run
     */
    public static function updateCleanupLastRun(): void
    {
        static::set('cleanup_last_run', now()->toDateTimeString());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    /**
     * Get setting value dengan type casting
     */
    public static function get(string $key, $default = null)
    {
        // Cache individual settings for 30 seconds to avoid repeated DB hits
        $cacheKey = 'system_setting_' . $key;
        $setting = Cache::remember($cacheKey, 30, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Get multiple settings in a single query (much faster than calling get() multiple times)
     */
    public static function getMany(array $keys): array
    {
        $cacheKey = 'system_settings_batch_' . md5(implode(',', $keys));
        $settings = Cache::remember($cacheKey, 30, function () use ($keys) {
            return static::whereIn('key', $keys)->get()->keyBy('key');
        });

        $result = [];
        foreach ($keys as $key) {
            $setting = $settings->get($key);
            $result[$key] = $setting ? static::castValue($setting->value, $setting->type) : null;
        }
        return $result;
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
        // Invalidate cache for this key after update
        Cache::forget('system_setting_' . $key);
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

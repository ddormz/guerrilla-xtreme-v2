<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key, with caching.
     */
    public static function getValue(string $key, $default = null): mixed
    {
        return Cache::remember("site_setting:{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value and clear cache.
     */
    public static function setValue(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("site_setting:{$key}");
    }

    /**
     * Check if a module is enabled.
     */
    public static function isModuleEnabled(string $module): bool
    {
        return static::getValue("module_{$module}_enabled", '1') === '1';
    }

    /**
     * Get all module settings as an array.
     */
    public static function getModuleSettings(): array
    {
        return [
            'rifas' => static::isModuleEnabled('rifas'),
            'torneos' => static::isModuleEnabled('torneos'),
            'liga' => static::isModuleEnabled('liga'),
            'ranking' => static::isModuleEnabled('ranking'),
        ];
    }
}

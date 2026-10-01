<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configuración clave/valor. Los valores predeterminados viven en DEFAULTS
 * para que el sistema funcione aunque la tabla esté vacía.
 */
class Setting extends Model
{
    public const DEFAULTS = [
        'vat_rate' => '16',
        'quote_followup_days' => '3',
        'upcoming_census_days' => '7',
        'quote_folio_start' => '1',
    ];

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn () => static::pluck('value', 'key')->all());

        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('settings.all');
    }

    public static function vatRate(): float
    {
        return (float) static::get('vat_rate');
    }

    public static function quoteFollowupDays(): int
    {
        return max(1, (int) static::get('quote_followup_days'));
    }

    public static function upcomingCensusDays(): int
    {
        return max(1, (int) static::get('upcoming_census_days'));
    }
}

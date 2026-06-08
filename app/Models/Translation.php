<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    protected $fillable = ['locale', 'key', 'value'];

    /** Supported locales. */
    public const LOCALES = [
        'en' => 'English',
        'fr' => 'Français',
    ];

    protected static function cacheKey(string $locale): string
    {
        return "translations.{$locale}";
    }

    /** All key => value strings for a locale, cached. */
    public static function forLocale(string $locale): array
    {
        return Cache::rememberForever(
            self::cacheKey($locale),
            fn () => static::query()->where('locale', $locale)->pluck('value', 'key')->all(),
        );
    }

    public static function put(string $locale, string $key, ?string $value): void
    {
        static::updateOrCreate(['locale' => $locale, 'key' => $key], ['value' => $value]);
    }

    public static function flush(string $locale): void
    {
        Cache::forget(self::cacheKey($locale));
    }

    protected static function booted(): void
    {
        static::saved(fn (Translation $t) => static::flush($t->locale));
        static::deleted(fn (Translation $t) => static::flush($t->locale));
    }
}

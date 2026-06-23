<?php

namespace App\Models;

use App\Console\Commands\GenerateLocalContent;
use Illuminate\Database\Eloquent\Model;

/**
 * Stored AI copy for one programmatic local page: an Ontario category hub
 * (city_slug null) or a city x category page. See the local-SEO autofill
 * command ({@see GenerateLocalContent}).
 */
class LocalContent extends Model
{
    protected $fillable = ['category', 'city_slug', 'intro', 'faqs'];

    protected function casts(): array
    {
        return ['faqs' => 'array'];
    }

    /** Find the stored copy for a page, or null. A null $citySlug = the hub. */
    public static function forPage(string $category, ?string $citySlug = null): ?self
    {
        return static::where('category', $category)
            ->where('city_slug', $citySlug)
            ->first();
    }
}

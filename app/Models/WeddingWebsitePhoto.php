<?php

namespace App\Models;

use Database\Factories\WeddingWebsitePhotoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingWebsitePhoto extends Model
{
    /** @use HasFactory<WeddingWebsitePhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_website_id',
        'path',
        'caption',
        'sort_order',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(WeddingWebsite::class, 'wedding_website_id');
    }

    /** @param Builder<WeddingWebsitePhoto> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order');
    }
}

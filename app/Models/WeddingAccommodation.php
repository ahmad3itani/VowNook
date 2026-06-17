<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A place to stay or a way to get around that the couple recommends to guests —
 * a hotel room block, a vacation rental, or a transport/shuttle option.
 */
class WeddingAccommodation extends Model
{
    protected $fillable = [
        'wedding_id',
        'name',
        'type',
        'address',
        'blurb',
        'booking_url',
        'block_code',
        'price_note',
        'distance_note',
        'image_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}

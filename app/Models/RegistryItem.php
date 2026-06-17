<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistryItem extends Model
{
    protected $fillable = [
        'wedding_id',
        'name',
        'blurb',
        'image_path',
        'price_cents',
        'store_url',
        'quantity',
        'claimed_count',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'quantity' => 'integer',
            'claimed_count' => 'integer',
            'sort_order' => 'integer',
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

    /** Whether every unit has been claimed by guests. */
    public function isFullyClaimed(): bool
    {
        return $this->claimed_count >= $this->quantity;
    }
}

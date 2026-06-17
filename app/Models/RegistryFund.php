<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistryFund extends Model
{
    protected $fillable = [
        'wedding_id',
        'title',
        'blurb',
        'image_path',
        'type',
        'goal_cents',
        'raised_cents',
        'payout_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'goal_cents' => 'integer',
            'raised_cents' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(RegistryContribution::class);
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

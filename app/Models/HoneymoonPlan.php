<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A couple's honeymoon plan — destination, dates, a simple budget, and notes.
 * The hotel map + flight search on the planner page are built from these via the
 * affiliate links, so bookings earn commission. One plan per wedding.
 */
class HoneymoonPlan extends Model
{
    protected $fillable = [
        'wedding_id',
        'destination',
        'airport',
        'start_date',
        'end_date',
        'budget_items',
        'notes',
        'preferences',
        'packages',
        'chosen_tier',
        'registry_added',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget_items' => 'array',
            'preferences' => 'array',
            'packages' => 'array',
            'registry_added' => 'boolean',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /** @param  Builder<HoneymoonPlan>  $query */
    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }
}

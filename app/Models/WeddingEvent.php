<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A single celebration in a multi-event wedding weekend — rehearsal dinner,
 * welcome party, ceremony, reception, farewell brunch … Guests reply to each
 * rsvpable event individually via the event_guest pivot; the couple's overall
 * reply stays on guests.rsvp_status for back-compat (seating/dashboard).
 */
class WeddingEvent extends Model
{
    protected $fillable = [
        'wedding_id',
        'name',
        'type',
        'event_date',
        'start_time',
        'end_time',
        'venue_name',
        'address',
        'dress_code',
        'description',
        'is_rsvpable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_rsvpable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class, 'event_guest')
            ->withPivot('rsvp_status')
            ->withTimestamps();
    }

    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('event_date')->orderBy('start_time');
    }
}

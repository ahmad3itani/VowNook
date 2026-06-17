<?php

namespace App\Models;

use App\Enums\AgeGroup;
use App\Enums\GuestSide;
use App\Enums\RsvpStatus;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'group_id',
        'table_id',
        'seat_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'side',
        'age_group',
        'is_plus_one',
        'rsvp_status',
        'meal_choice',
        'appetizer_choice',
        'dessert_choice',
        'dietary_notes',
        'invited_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'side' => GuestSide::class,
            'age_group' => AgeGroup::class,
            'rsvp_status' => RsvpStatus::class,
            'is_plus_one' => 'boolean',
            'invited_at' => 'datetime',
            'seat_number' => 'integer',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(GuestGroup::class, 'group_id');
    }

    public function seatingTable(): BelongsTo
    {
        return $this->belongsTo(SeatingTable::class, 'table_id');
    }

    /** Per-event RSVP replies (multi-event weddings). */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(WeddingEvent::class, 'event_guest')
            ->withPivot('rsvp_status')
            ->withTimestamps();
    }

    /** Scope to a single wedding (the active tenant). */
    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }
}

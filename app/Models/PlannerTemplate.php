<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reusable checklist or budget blueprint owned by a planner (or any user).
 * Items are stored as JSON; checklist items carry an offset in days relative
 * to the wedding's event date so due dates land correctly on every client.
 */
class PlannerTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'items',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}

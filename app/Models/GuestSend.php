<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A save-the-date or invitation sent to a guest, with a unique token used by
 * the email tracking pixel to record opens.
 */
class GuestSend extends Model
{
    public const KINDS = ['save_the_date', 'invitation'];

    protected $fillable = [
        'wedding_id',
        'guest_id',
        'kind',
        'token',
        'sent_at',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }
}

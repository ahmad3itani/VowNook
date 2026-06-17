<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-off announcement the couple emailed to a chosen guest audience.
 * Kept for the sent-history list on the Messages page.
 */
class GuestBroadcast extends Model
{
    protected $fillable = [
        'wedding_id',
        'subject',
        'body',
        'audience',
        'recipient_count',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipient_count' => 'integer',
            'sent_at' => 'datetime',
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
}

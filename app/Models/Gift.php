<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A gift the couple received, with thank-you tracking. Registry fund
 * contributions auto-create these; couples add physical/cash gifts manually.
 */
class Gift extends Model
{
    public const KINDS = ['fund', 'item', 'cash', 'physical'];

    protected $fillable = [
        'wedding_id',
        'guest_id',
        'registry_contribution_id',
        'from_name',
        'kind',
        'amount_cents',
        'received_at',
        'thank_you_sent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'received_at' => 'date',
            'thank_you_sent' => 'boolean',
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

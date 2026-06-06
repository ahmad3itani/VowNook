<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEvent extends Model
{
    /** @use HasFactory<\Database\Factories\TimelineEventFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'vendor_id',
        'title',
        'type',
        'starts_at',
        'ends_at',
        'location',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }
}

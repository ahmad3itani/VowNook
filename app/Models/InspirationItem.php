<?php

namespace App\Models;

use App\Enums\InspirationCategory;
use Database\Factories\InspirationItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspirationItem extends Model
{
    /** @use HasFactory<InspirationItemFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'title',
        'category',
        'image_url',
        'link_url',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => InspirationCategory::class,
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /** Scope to a single wedding (the active tenant). */
    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }
}

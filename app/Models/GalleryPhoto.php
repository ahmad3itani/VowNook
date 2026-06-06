<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryPhoto extends Model
{
    /** @use HasFactory<\Database\Factories\GalleryPhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'path',
        'original_name',
        'mime',
        'size',
        'caption',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
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

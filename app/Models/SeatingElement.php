<?php

namespace App\Models;

use App\Enums\SeatingElementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatingElement extends Model
{
    /** @use HasFactory<\Database\Factories\SeatingElementFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'type',
        'label',
        'position_x',
        'position_y',
        'width',
        'height',
        'rotation',
    ];

    protected function casts(): array
    {
        return [
            'type' => SeatingElementType::class,
            'position_x' => 'integer',
            'position_y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'rotation' => 'integer',
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

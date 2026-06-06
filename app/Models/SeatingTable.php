<?php

namespace App\Models;

use App\Enums\TableShape;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatingTable extends Model
{
    /** @use HasFactory<\Database\Factories\SeatingTableFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'name',
        'shape',
        'capacity',
        'position_x',
        'position_y',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'shape' => TableShape::class,
            'capacity' => 'integer',
            'position_x' => 'integer',
            'position_y' => 'integer',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'table_id');
    }

    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }
}

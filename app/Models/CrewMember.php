<?php

namespace App\Models;

use App\Enums\CrewRole;
use Database\Factories\CrewMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrewMember extends Model
{
    /** @use HasFactory<CrewMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'name',
        'role',
        'email',
        'phone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'role' => CrewRole::class,
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

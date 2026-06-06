<?php

namespace App\Models;

use Database\Factories\BudgetItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetItem extends Model
{
    /** @use HasFactory<BudgetItemFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'category_id',
        'name',
        'estimated_cents',
        'actual_cents',
        'paid_cents',
        'due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cents' => 'integer',
            'actual_cents' => 'integer',
            'paid_cents' => 'integer',
            'due_date' => 'date',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'category_id');
    }

    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCategory extends Model
{
    /** @use HasFactory<\Database\Factories\BudgetCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'name',
        'sort_order',
    ];

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class, 'category_id');
    }
}

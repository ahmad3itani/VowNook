<?php

namespace App\Models;

use App\Enums\VendorCategory;
use App\Enums\VendorStatus;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'name',
        'category',
        'status',
        'rating',
        'price_level',
        'contact_name',
        'email',
        'phone',
        'website',
        'cost_cents',
        'paid_cents',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => VendorCategory::class,
            'status' => VendorStatus::class,
            'rating' => 'integer',
            'price_level' => 'integer',
            'cost_cents' => 'integer',
            'paid_cents' => 'integer',
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

<?php

namespace App\Models;

use App\Enums\VendorCategory;
use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'name',
        'category',
        'status',
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

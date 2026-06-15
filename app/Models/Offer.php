<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'total_cents',
        'deposit_cents',
        'line_items',
        'terms',
        'valid_until',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status'      => OfferStatus::class,
            'valid_until' => 'date',
            'line_items'  => 'array',
            'total_cents'   => 'integer',
            'deposit_cents' => 'integer',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }
}

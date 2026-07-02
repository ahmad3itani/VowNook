<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A digital-stationery order from the VowNook Shop (/shop). Created pending at
 * checkout, fulfilled by the Stripe webhook, delivered by signed download link.
 */
class ShopOrder extends Model
{
    protected $fillable = [
        'stripe_session_id',
        'product_key',
        'product_name',
        'amount_cents',
        'currency',
        'email',
        'status',
        'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function isFulfilled(): bool
    {
        return $this->status === 'fulfilled';
    }

    /** The catalog entry this order was for (name, amount_cents, file). */
    public function product(): ?array
    {
        return config('shop.products.'.$this->product_key);
    }
}

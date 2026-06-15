<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'code', 'kind', 'plan', 'duration_days', 'max_redemptions',
        'redeemed_count', 'expires_at', 'is_active', 'note',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'duration_days' => 'integer',
            'max_redemptions' => 'integer',
            'redeemed_count' => 'integer',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromoRedemption::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function isRedeemableBy(User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_redemptions !== null && $this->redeemed_count >= $this->max_redemptions) {
            return false;
        }

        return ! $this->redemptions()->where('user_id', $user->id)->exists();
    }
}

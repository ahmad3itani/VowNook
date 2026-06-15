<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A pending invitation to collaborate on a wedding. Unlike a membership, the
 * invitee need not have an account yet — they accept via an emailed token link.
 */
class WeddingInvitation extends Model
{
    protected $fillable = [
        'wedding_id',
        'email',
        'role',
        'permissions',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'permissions' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WeddingInvitation $invitation) {
            $invitation->token ??= self::freshToken();
            $invitation->expires_at ??= now()->addDays(14);
        });
    }

    public static function freshToken(): string
    {
        return Str::random(64);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /** Still open: not yet accepted and not expired. */
    public function isAcceptable(): bool
    {
        return $this->accepted_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at');
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}

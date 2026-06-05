<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function ownedWeddings(): HasMany
    {
        return $this->hasMany(Wedding::class, 'owner_id');
    }

    public function weddings(): BelongsToMany
    {
        return $this->belongsToMany(Wedding::class, 'wedding_user')
            ->withPivot(['role', 'permissions', 'invited_at', 'accepted_at'])
            ->withTimestamps();
    }

    public function currentWedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class, 'current_wedding_id');
    }

    /** All weddings the user can access (owned + member), de-duplicated. */
    public function accessibleWeddings()
    {
        return $this->weddings->merge($this->ownedWeddings)->unique('id')->values();
    }

    public function plan(): array
    {
        $tiers = config('plans.tiers');

        return $tiers[$this->plan] ?? $tiers[config('plans.default')];
    }

    public function planLimit(string $key): ?int
    {
        return $this->plan()[$key] ?? null;
    }
}

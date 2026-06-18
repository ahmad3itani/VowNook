<?php

namespace App\Models;

use App\Enums\AccountType;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'account_type', 'email_preferences', 'marketing_consent_at', 'referred_by'])]
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
            'account_type' => AccountType::class,
            'two_factor_confirmed_at' => 'datetime',
            'email_preferences' => 'array',
            'marketing_consent_at' => 'datetime',
            'plan_comped_until' => 'datetime',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /** Whether the account is currently suspended (blocked from the app). */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /** Audit-trail entries this user performed (actor side). */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }

    /** Support tickets this user opened. */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (blank($user->referral_code)) {
                $user->referral_code = static::uniqueReferralCode();
            }
        });
    }

    public static function uniqueReferralCode(): string
    {
        do {
            $code = strtoupper(\Illuminate\Support\Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function isVendor(): bool
    {
        return $this->account_type === AccountType::Vendor;
    }

    public function isPlanner(): bool
    {
        return $this->account_type === AccountType::Planner;
    }

    public function isCouple(): bool
    {
        return $this->account_type === AccountType::Couple;
    }

    public function vendorProfile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
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

        // Read the raw column — `$this->plan` would recurse into this method
        // when the attribute isn't loaded (e.g. freshly created models).
        $plan = $this->getAttributes()['plan'] ?? null;

        return $tiers[$plan] ?? $tiers[config('plans.default')];
    }

    public function planLimit(string $key): ?int
    {
        return $this->plan()[$key] ?? null;
    }

    /**
     * The plan tier KEY (e.g. "free"/"premium"/"planner"), read safely from the
     * raw attribute — `$this->plan` collides with the plan() method when the
     * attribute isn't loaded (e.g. on an actingAs() model in tests).
     */
    public function planKey(): string
    {
        return $this->getAttributes()['plan'] ?? config('plans.default');
    }

    /** Whether the user's plan tier includes a named feature flag. */
    public function hasFeature(string $key): bool
    {
        return (bool) ($this->plan()['features'][$key] ?? false);
    }

    /**
     * Whether the user can use a named plan feature. Planner accounts and
     * admins are always entitled; everyone else depends on their plan tier's
     * feature flag. This is the single gate for paid capabilities
     * (`ai`, `website_publish`, `seating`, …).
     */
    public function canUseFeature(string $key): bool
    {
        return $this->is_admin || $this->isPlanner() || $this->hasFeature($key);
    }

    /**
     * AI assistance is a paid perk: Premium couples (plan feature), all Planner
     * accounts, and admins. Free couples are excluded and shown an upsell.
     */
    public function canUseAi(): bool
    {
        return $this->canUseFeature('ai');
    }
}

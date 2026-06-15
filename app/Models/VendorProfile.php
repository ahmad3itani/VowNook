<?php

namespace App\Models;

use App\Enums\VendorCategory;
use App\Enums\VendorProfileStatus;
use Database\Factories\VendorProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A marketplace vendor business. Owned by a single user (account_type=vendor),
 * serves many couples. Distinct from the per-wedding `vendors` CRM row.
 */
class VendorProfile extends Model
{
    /** @use HasFactory<VendorProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'category',
        'tagline',
        'description',
        'logo_path',
        'cover_path',
        'video_url',
        'brochure_path',
        'city',
        'region',
        'country',
        'service_area',
        'base_price_cents',
        'price_unit',
        'website',
        'phone',
        'email',
        'socials',
        'status',
        'is_founding',
        'featured_until',
        'verified_at',
        'agreement_accepted_at',
        'stripe_account_id',
        'stripe_charges_enabled',
        'stripe_details_submitted',
        'is_accepting_bookings',
    ];

    protected function casts(): array
    {
        return [
            'category' => VendorCategory::class,
            'status' => VendorProfileStatus::class,
            'is_founding' => 'boolean',
            'featured_until' => 'datetime',
            'verified_at' => 'datetime',
            'agreement_accepted_at' => 'datetime',
            'socials' => 'array',
            'base_price_cents' => 'integer',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'response_hours' => 'integer',
            'response_count' => 'integer',
            'is_accepting_bookings' => 'boolean',
            'stripe_charges_enabled' => 'boolean',
            'stripe_details_submitted' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VendorProfile $profile) {
            if (blank($profile->slug)) {
                $profile->slug = static::uniqueSlug($profile->business_name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'vendor';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(VendorService::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(VendorMedia::class)->orderBy('sort_order');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(VendorAvailability::class)->orderBy('date');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Recompute the denormalized response-time stats from the vendor's most
     * recently answered inquiries. Median over the last 30 first responses,
     * rounded up to whole hours (minimum 1).
     */
    public static function syncResponseStats(int $vendorProfileId): void
    {
        $hours = Inquiry::forVendorProfile($vendorProfileId)
            ->whereNotNull('first_response_at')
            ->latest('first_response_at')
            ->limit(30)
            ->get(['created_at', 'first_response_at'])
            ->map(fn (Inquiry $i) => $i->created_at->floatDiffInHours($i->first_response_at))
            ->sort()
            ->values();

        static::whereKey($vendorProfileId)->update([
            'response_hours' => $hours->isEmpty() ? null : max(1, (int) ceil($hours->median())),
            'response_count' => $hours->count(),
        ]);
    }

    /** Only profiles that are live on public routes. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', VendorProfileStatus::Published->value);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

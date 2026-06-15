<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A couple's post-booking review of a marketplace vendor. One per booking;
 * feeds the denormalized rating_avg / rating_count on vendor_profiles.
 */
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'wedding_id',
        'vendor_profile_id',
        'couple_user_id',
        'rating',
        'body',
        'vendor_response',
        'vendor_responded_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'vendor_responded_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function vendorProfile(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class);
    }

    public function coupleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'couple_user_id');
    }

    public function scopeForVendorProfile(Builder $query, int $vendorProfileId): Builder
    {
        return $query->where('vendor_profile_id', $vendorProfileId);
    }

    /** Recalculate the denormalized rating columns on the vendor profile. */
    public static function syncVendorRating(int $vendorProfileId): void
    {
        $stats = static::forVendorProfile($vendorProfileId)
            ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg')
            ->first();

        VendorProfile::whereKey($vendorProfileId)->update([
            'rating_count' => (int) ($stats->cnt ?? 0),
            'rating_avg' => round((float) ($stats->avg ?? 0), 2),
        ]);
    }

    /** Privacy-preserving display name: first name + last initial. */
    public function authorDisplayName(): string
    {
        $name = trim((string) $this->coupleUser?->name);

        if ($name === '') {
            return 'A couple';
        }

        $parts = preg_split('/\s+/', $name) ?: [$name];
        $first = $parts[0];

        if (count($parts) === 1) {
            return $first;
        }

        return $first.' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.';
    }
}

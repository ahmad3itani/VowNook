<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'couple_user_id',
        'vendor_profile_id',
        'vendor_service_id',
        'event_date',
        'guest_count',
        'budget_cents',
        'message',
        'status',
        'first_response_at',
    ];

    protected function casts(): array
    {
        return [
            'status'     => InquiryStatus::class,
            'event_date' => 'date',
            'budget_cents' => 'integer',
            'guest_count' => 'integer',
            'first_response_at' => 'datetime',
        ];
    }

    /**
     * Stamp the vendor's first reply (offer or message) and refresh the
     * vendor's denormalized response-time stats. No-op on later replies.
     */
    public function recordVendorResponse(): void
    {
        if ($this->first_response_at !== null) {
            return;
        }

        $this->forceFill(['first_response_at' => now()])->save();

        VendorProfile::syncResponseStats($this->vendor_profile_id);
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function coupleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'couple_user_id');
    }

    public function vendorProfile(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class);
    }

    public function vendorService(): BelongsTo
    {
        return $this->belongsTo(VendorService::class);
    }

    public function offer(): HasOne
    {
        return $this->hasOne(Offer::class)->latest();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InquiryMessage::class)->oldest();
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }

    public function scopeForVendorProfile(Builder $query, int $vendorProfileId): Builder
    {
        return $query->where('vendor_profile_id', $vendorProfileId);
    }

    /** Offers awaiting the couple's response — drives the Vendors hub tab badge. */
    public static function offersAwaiting(?int $weddingId): int
    {
        if ($weddingId === null) {
            return 0;
        }

        return static::where('wedding_id', $weddingId)
            ->where('status', InquiryStatus::Offered->value)
            ->count();
    }
}

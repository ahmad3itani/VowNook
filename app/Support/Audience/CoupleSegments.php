<?php

namespace App\Support\Audience;

use App\Enums\VendorCategory;
use App\Enums\WeddingSeason;
use App\Enums\WeddingVibe;
use App\Models\Inquiry;
use App\Models\Wedding;
use App\Support\Budget\BudgetAllocator;
use App\Support\OntarioCities;
use Carbon\CarbonInterface;

/**
 * Cheap, computed-only audience segmentation for a wedding — no AI, no new
 * data capture, no persistence. Everything here is derived on the fly from
 * columns the couple already gave us (budget, city, event date, guest list,
 * plan tier) plus their own first-party marketplace behaviour (which vendor
 * categories they've actually inquired about).
 *
 * GUARDRAIL (from the growth feasibility study): this service must NEVER
 * derive or store sensitive characteristics — religion, ethnicity, sexual
 * orientation, health, or political affiliation. Segments are strictly
 * limited to budget range, city, wedding timeline, guest-list scale, plan
 * tier and self-reported category interest. Used only for internal
 * personalization and vendor-recruitment prioritization — do not extend this
 * class to infer anything beyond that list.
 */
class CoupleSegments
{
    /**
     * @return array{
     *     budget_tier: string,
     *     budget_tier_label: string,
     *     city: string|null,
     *     city_name: string|null,
     *     timeline_urgency: string,
     *     timeline_label: string,
     *     guest_scale: string,
     *     guest_scale_label: string,
     *     plan_tier: string,
     *     vibe: string|null,
     *     vibe_label: string|null,
     *     season: string|null,
     *     season_label: string|null,
     *     interested_categories: list<array{category: string, label: string, count: int}>,
     *     referred: bool,
     * }
     */
    public function for(Wedding $wedding): array
    {
        $budgetTier = $this->budgetTier($wedding->total_budget_cents);
        $timelineUrgency = $this->timelineUrgency($wedding->event_date);
        $guestScale = $this->guestScale($wedding->guests()->count());

        $owner = $wedding->owner;

        // Self-reported aesthetic preferences from the onboarding-enrichment
        // screen. tryFrom() degrades a stale/renamed enum value to null
        // rather than throwing.
        $settings = $wedding->settings ?? [];
        $vibe = WeddingVibe::tryFrom($settings['vibe'] ?? '');
        $season = WeddingSeason::tryFrom($settings['season'] ?? '');

        return [
            'budget_tier' => $budgetTier,
            'budget_tier_label' => $this->budgetTierLabel($budgetTier),
            'city' => $wedding->city,
            'city_name' => $wedding->city !== null ? OntarioCities::name($wedding->city) : null,
            'timeline_urgency' => $timelineUrgency,
            'timeline_label' => $this->timelineLabel($timelineUrgency),
            'guest_scale' => $guestScale,
            'guest_scale_label' => $this->guestScaleLabel($guestScale),
            'plan_tier' => $owner?->planKey() ?? config('plans.default'),
            'vibe' => $vibe?->value,
            'vibe_label' => $vibe?->label(),
            'season' => $season?->value,
            'season_label' => $season?->label(),
            'interested_categories' => $this->interestedCategories($wedding),
            'referred' => $owner?->referred_by !== null,
        ];
    }

    /** Reuses the exact band keys BudgetAllocator::bands() shows the couple. */
    private function budgetTier(?int $totalBudgetCents): string
    {
        $cents = $totalBudgetCents ?? 0;

        return match (true) {
            $cents <= 0 => 'unset',
            $cents < 1_500_000 => 'under-15k',
            $cents < 2_500_000 => '15-25k',
            $cents < 4_000_000 => '25-40k',
            $cents < 6_000_000 => '40-60k',
            default => '60k-plus',
        };
    }

    private function budgetTierLabel(string $tier): string
    {
        if ($tier === 'unset') {
            return 'Not set yet';
        }

        $band = collect(BudgetAllocator::bands())->firstWhere('key', $tier);

        return $band['label'] ?? 'Not set yet';
    }

    /**
     * 'urgent' at 0 months means "the wedding is this month or later within
     * the next 3 months" — not yet passed. Uses a signed month diff so a
     * negative value unambiguously means the date is behind us.
     */
    private function timelineUrgency(?CarbonInterface $eventDate): string
    {
        if ($eventDate === null) {
            return 'no-date';
        }

        $monthsAway = now()->startOfDay()->diffInMonths($eventDate->copy()->startOfDay(), false);

        return match (true) {
            $monthsAway < 0 => 'past',
            $monthsAway < 3 => 'urgent',
            $monthsAway < 12 => 'this-year',
            $monthsAway < 24 => 'planning-ahead',
            default => 'far-out',
        };
    }

    private function timelineLabel(string $urgency): string
    {
        return match ($urgency) {
            'no-date' => 'No date set yet',
            'past' => 'Wedding has passed',
            'urgent' => 'Under 3 months away',
            'this-year' => '3–12 months away',
            'planning-ahead' => '1–2 years away',
            default => '2+ years away',
        };
    }

    private function guestScale(int $guestCount): string
    {
        return match (true) {
            $guestCount <= 0 => 'unset',
            $guestCount <= 30 => 'intimate',
            $guestCount <= 100 => 'medium',
            $guestCount <= 200 => 'large',
            default => 'grand',
        };
    }

    private function guestScaleLabel(string $scale): string
    {
        return match ($scale) {
            'unset' => 'Not set yet',
            'intimate' => 'Intimate (up to 30)',
            'medium' => 'Medium (31–100)',
            'large' => 'Large (101–200)',
            default => 'Grand (201+)',
        };
    }

    /**
     * Distinct vendor categories this wedding has actually inquired about —
     * real first-party behaviour, not inference. Sorted by count desc, then
     * category asc.
     *
     * @return list<array{category: string, label: string, count: int}>
     */
    private function interestedCategories(Wedding $wedding): array
    {
        $counts = Inquiry::query()
            ->where('wedding_id', $wedding->id)
            ->join('vendor_profiles', 'vendor_profiles.id', '=', 'inquiries.vendor_profile_id')
            ->selectRaw('vendor_profiles.category as category, count(*) as c')
            ->groupBy('vendor_profiles.category')
            ->pluck('c', 'category');

        return $counts
            ->map(fn ($count, $category) => [
                'category' => $category,
                'label' => VendorCategory::tryFrom($category)?->label() ?? $category,
                'count' => (int) $count,
            ])
            ->sortBy([
                fn ($a, $b) => $b['count'] <=> $a['count'],
                fn ($a, $b) => $a['category'] <=> $b['category'],
            ])
            ->values()
            ->all();
    }
}

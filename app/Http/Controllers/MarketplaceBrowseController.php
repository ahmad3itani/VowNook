<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Enums\VendorCategory;
use App\Models\Inquiry;
use App\Support\Budget\BudgetAllocator;
use App\Support\CurrentWedding;
use App\Support\MarketplaceCatalog;
use App\Support\OntarioCities;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-portal marketplace browse for couples — same catalog as the public
 * `/marketplace`, but rendered inside the couple layout (sidebar + Vendors hub
 * tabs) so discovery never leaves the planning app.
 */
class MarketplaceBrowseController extends Controller
{
    public function __construct(
        protected MarketplaceCatalog $catalog,
        protected CurrentWedding $wedding,
    ) {}

    public function index(Request $request): Response
    {
        $filters = [
            'category'  => $request->query('category', ''),
            'city'      => $request->query('city', ''),
            'region'    => $request->query('region', ''),
            'min_price' => $request->query('min_price', ''),
            'max_price' => $request->query('max_price', ''),
        ];

        $context = $this->personalizationContext();

        $profiles = $this->catalog->browse($filters, $context['city_name'] ?? null);

        return Inertia::render('vendors/marketplace', [
            'profiles'   => $profiles->map(fn ($p) => $this->catalog->cardData($p, $context)),
            'categories' => collect(VendorCategory::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
            'filters'     => $filters,
            'total'       => $profiles->count(),
            'quote_badge' => Inquiry::offersAwaiting($this->wedding->id()),
        ]);
    }

    public function show(string $slug): Response
    {
        $profile = $this->catalog->findPublished($slug);
        $weddingId = $this->wedding->id();

        $existingInquiry = $weddingId
            ? Inquiry::where('wedding_id', $weddingId)
                ->where('vendor_profile_id', $profile->id)
                ->whereIn('status', [InquiryStatus::Requested->value, InquiryStatus::Offered->value])
                ->first()
            : null;

        return Inertia::render('vendors/marketplace-show', [
            'profile'           => $this->catalog->profileData($profile, $this->personalizationContext()),
            'auth_context'      => [
                'is_couple'        => true,
                'has_wedding'      => (bool) $weddingId,
                'existing_inquiry' => $existingInquiry?->id,
            ],
            'services_for_select' => $this->catalog->serviceOptions($profile),
            'quote_badge'         => Inquiry::offersAwaiting($weddingId),
        ]);
    }

    /**
     * "Fits your budget" / "near you" context derived from the couple's own
     * captured budget + city — zero cost, first-party data only. Empty when
     * the couple hasn't captured a budget/city yet, which leaves cardData()
     * and profileData() emitting no personalization keys for that request.
     *
     * @return array{category_budgets?: array<string, int>, city_name?: string|null}
     */
    protected function personalizationContext(): array
    {
        $wedding = $this->wedding->get();
        $context = [];

        if ($wedding !== null) {
            if ($wedding->total_budget_cents !== null) {
                $context['category_budgets'] = app(BudgetAllocator::class)->categoryBudgetsFor($wedding->total_budget_cents);
            }

            if ($wedding->city !== null) {
                $context['city_name'] = OntarioCities::name($wedding->city);
            }
        }

        return $context;
    }
}

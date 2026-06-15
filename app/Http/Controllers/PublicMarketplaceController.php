<?php

namespace App\Http\Controllers;

use App\Enums\VendorCategory;
use App\Support\MarketplaceCatalog;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicMarketplaceController extends Controller
{
    public function __construct(protected MarketplaceCatalog $catalog) {}

    public function index(Request $request): Response
    {
        $filters = [
            'category'  => $request->query('category', ''),
            'city'      => $request->query('city', ''),
            'region'    => $request->query('region', ''),
            'min_price' => $request->query('min_price', ''),
            'max_price' => $request->query('max_price', ''),
        ];

        $profiles = $this->catalog->browse($filters);

        // Filtered views duplicate the indexable /{category}/{city} programmatic
        // pages, so they canonical to the clean index and are not indexed.
        $hasFilters = collect($filters)->filter(fn ($v) => $v !== '' && $v !== null)->isNotEmpty();

        $seo = Seo::make(
            title: 'Wedding Vendors in Ontario',
            description: 'Browse trusted Ontario wedding vendors — photographers, venues, florists, caterers, music and more. Compare packages, read verified reviews and request quotes for free.',
            canonical: route('public.marketplace'),
            index: ! $hasFilters,
            schemas: [Seo::breadcrumbs(['Marketplace' => route('public.marketplace')])],
        );

        return Inertia::render('public/marketplace', [
            'profiles'   => $profiles->map(fn ($p) => $this->catalog->cardData($p)),
            'categories' => collect(VendorCategory::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
            'filters' => $filters,
            'total'   => $profiles->count(),
        ])->withViewData(['seo' => $seo]);
    }
}

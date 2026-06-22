<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoneymoonPlan;
use App\Models\WeddingWebsite;
use App\Support\Affiliates\AffiliateReports;
use App\Support\Affiliates\TravelAffiliates;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin "Affiliate revenue" dashboard — platform-wide travel-affiliate adoption
 * (our own data) plus live partner earnings where the partner exposes an API,
 * and deep links to each partner's own dashboard otherwise.
 */
class AffiliateController extends Controller
{
    public function index(AffiliateReports $reports): Response
    {
        $affiliates = app(TravelAffiliates::class);

        $staysLive = WeddingWebsite::query()
            ->where('is_published', true)
            ->where('show_travel_stays', true)
            ->where(fn ($q) => $q->whereNotNull('venue_name')->orWhereNotNull('venue_address'))
            ->count();

        $flightsLive = WeddingWebsite::query()
            ->where('is_published', true)
            ->where('show_travel_stays', true)
            ->whereNotNull('nearest_airport')
            ->where('nearest_airport', '!=', '')
            ->count();

        return Inertia::render('admin/affiliates', [
            'adoption' => [
                'stays_live' => $staysLive,
                'flights_live' => $flightsLive,
                'honeymoons' => HoneymoonPlan::count(),
                'honeymoons_planned' => HoneymoonPlan::whereNotNull('destination')->where('destination', '!=', '')->count(),
            ],
            'stay22' => [
                'enabled' => $affiliates->isConfigured(),
                'dashboard_url' => config('affiliates.stay22.dashboard_url'),
            ],
            'travelpayouts' => [
                'enabled' => $affiliates->flightsConfigured(),
                'dashboard_url' => config('affiliates.travelpayouts.dashboard_url'),
                'balance' => $reports->travelpayoutsBalance(),
            ],
        ]);
    }
}

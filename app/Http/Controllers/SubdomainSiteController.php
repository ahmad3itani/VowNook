<?php

namespace App\Http\Controllers;

use App\Models\WeddingWebsite;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

/**
 * Resolves a couple's free personal web address — {subdomain}.vownook.com —
 * to their published wedding site. The canonical URL stays /w/{slug} (set by
 * PublicWebsiteController), so there's no duplicate-content penalty.
 */
class SubdomainSiteController extends Controller
{
    public function show(string $subdomain): Response|RedirectResponse
    {
        $website = WeddingWebsite::query()
            ->where('subdomain', $subdomain)
            ->where('is_published', true)
            ->with('wedding')
            ->first();

        // Unknown / unclaimed / unpublished → send visitors to the main site.
        if ($website?->wedding === null) {
            return redirect()->away(config('app.url'));
        }

        return app(PublicWebsiteController::class)->show($website->wedding);
    }
}

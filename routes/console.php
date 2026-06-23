<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Lifecycle & engagement schedule ──────────────────────────────────────────
// Production requires a cron entry: `* * * * * php artisan schedule:run`.
Schedule::command('weddings:milestones')->dailyAt('14:00');
Schedule::command('weddings:rsvp-reminders')->dailyAt('14:05');
Schedule::command('couples:onboarding-nudge')->dailyAt('14:10');
Schedule::command('vendors:unanswered-inquiries')->dailyAt('15:00');
Schedule::command('weddings:weekly-digest')->weeklyOn(1, '14:30'); // Mondays
Schedule::command('plans:expire-comps')->dailyAt('02:00');
Schedule::command('weddings:post-wedding')->dailyAt('16:00');
Schedule::command('admin:daily-digest')->dailyAt('08:00');

// Content engine: writes + publishes one SEO article from the curated Ontario
// topic queue. No-ops until BLOG_AUTOPILOT_ENABLED=true, so a slow, steady
// cadence (one per week) keeps it clear of scaled-content penalties.
Schedule::command('blog:autopilot')->weeklyOn(2, '09:00'); // Tuesdays

// Local SEO: fills programmatic Ontario pages (hubs + gated city pages) with
// unique stored guide copy + FAQs. No-ops until LOCAL_SEO_AUTOFILL_ENABLED=true.
Schedule::command('seo:generate-local')->weeklyOn(4, '09:00'); // Thursdays

<?php

namespace App\Support\Blog;

use App\Enums\BlogCategory;

/**
 * The curated Ontario-wedding topic queue the blog autopilot draws from. Each
 * topic has a STABLE slug so the autopilot can skip ones already published
 * (de-dupe) and never spin near-duplicates. Topics are distinct search intents
 * — chosen for genuine usefulness + local relevance, not keyword stuffing.
 *
 * Real Weddings is intentionally excluded: it implies real couples/stories,
 * which must never be AI-fabricated.
 */
class BlogTopics
{
    /**
     * @return list<array{slug:string, title:string, category:string, brief:string}>
     */
    public static function all(): array
    {
        $b = BlogCategory::Budgeting->value;
        $p = BlogCategory::PlanningTips->value;
        $v = BlogCategory::Venues->value;
        $g = BlogCategory::VendorGuides->value;

        return [
            ['slug' => 'how-to-save-money-on-a-wedding-in-ontario', 'title' => 'How to Save Money on a Wedding in Ontario (Without It Showing)', 'category' => $b, 'brief' => 'Specific, practical ways Ontario couples cut costs: off-season/weekday dates, guest count, bar choices, where to spend vs save on decor. Include real Ontario price context and where NOT to cut.'],
            ['slug' => 'average-wedding-cost-toronto', 'title' => 'The Average Cost of a Wedding in Toronto (2026)', 'category' => $b, 'brief' => 'Toronto-specific numbers and why the GTA runs higher than the rest of Ontario; a category breakdown and how to plan a Toronto wedding on a tighter budget.'],
            ['slug' => 'wedding-budget-breakdown-by-category', 'title' => 'Wedding Budget Breakdown: How to Split Your Spend by Category', 'category' => $b, 'brief' => 'The standard percentage allocation (venue, catering, photo, etc.), how to adjust for your priorities, with a worked example for a $30k Ontario budget.'],
            ['slug' => 'micro-wedding-cost-ontario', 'title' => 'Micro-Weddings in Ontario: What They Cost and How to Plan One', 'category' => $b, 'brief' => 'What a micro-wedding is, typical cost for 20–50 guests, the trade-offs, Ontario venue ideas, and how the math differs from a full wedding.'],
            ['slug' => 'average-wedding-cost-ottawa', 'title' => 'The Average Cost of a Wedding in Ottawa (2026)', 'category' => $b, 'brief' => 'Ottawa-specific cost numbers, local venue context, and how to save in the capital region. Distinct from the Toronto piece.'],
            ['slug' => 'who-pays-for-what-wedding', 'title' => 'Who Pays for What at a Wedding? (A Modern Take)', 'category' => $b, 'brief' => 'Traditional vs modern splits, how to have the money conversation, and contribution etiquette without the awkwardness.'],

            ['slug' => 'how-to-plan-an-ontario-wedding-in-6-months', 'title' => 'How to Plan an Ontario Wedding in 6 Months', 'category' => $p, 'brief' => 'A condensed timeline for short-engagement couples: what to prioritise, what to drop, and Ontario booking-lead-time realities. Distinct from the 12-month timeline post.'],
            ['slug' => 'wedding-day-timeline-template', 'title' => 'A Wedding Day Timeline Template (Hour by Hour)', 'category' => $p, 'brief' => 'A realistic run-of-show from getting-ready to send-off, with timing tips and where to build in buffer.'],
            ['slug' => 'how-to-make-a-wedding-guest-list', 'title' => 'How to Make Your Wedding Guest List (and Cut It Down)', 'category' => $p, 'brief' => 'A framework for building and trimming the list, plus-one rules, the cost-per-guest reality, and handling family pressure.'],
            ['slug' => 'marriage-licence-ontario', 'title' => 'How to Get a Marriage Licence in Ontario (Step by Step)', 'category' => $p, 'brief' => 'The process, where to apply, cost, ID needed, the 90-day validity window, and common mistakes. Factual — advise readers to confirm details with ServiceOntario.'],
            ['slug' => 'best-time-of-year-to-get-married-in-ontario', 'title' => 'The Best Time of Year to Get Married in Ontario', 'category' => $p, 'brief' => 'Season-by-season pros and cons — weather, pricing, daylight, venue availability — with honest trade-offs.'],
            ['slug' => 'wedding-rsvp-etiquette', 'title' => 'Wedding RSVP Etiquette: Deadlines, Wording, and Chasing Replies', 'category' => $p, 'brief' => 'When to set the deadline, how to word it, digital vs paper, and how to follow up with non-responders politely.'],
            ['slug' => 'how-to-make-a-wedding-seating-chart', 'title' => 'How to Make a Wedding Seating Chart That Actually Works', 'category' => $p, 'brief' => 'A step-by-step approach, handling family dynamics, when to start, and tools that make it easier.'],
            ['slug' => 'how-to-stay-sane-planning-a-wedding', 'title' => 'How to Stay Sane While Planning a Wedding', 'category' => $p, 'brief' => 'Splitting the workload, beating decision fatigue, delegating, and the "good enough" mindset. Warm and practical.'],
            ['slug' => 'local-vs-destination-wedding-ontario', 'title' => 'Local vs Destination Wedding: An Honest Comparison for Ontario Couples', 'category' => $p, 'brief' => 'Cost, guest attendance, logistics, and the honeymoon overlap — to help couples decide.'],

            ['slug' => 'types-of-wedding-venues-ontario', 'title' => 'Types of Wedding Venues in Ontario (and Who Each Suits)', 'category' => $v, 'brief' => 'Barns, ballrooms, wineries, gardens, lofts, all-inclusive halls — vibe, capacity, cost, and pros/cons of each to help couples shortlist.'],
            ['slug' => 'planning-an-outdoor-wedding-in-ontario', 'title' => 'Planning an Outdoor Wedding in Ontario: The Weather Question', 'category' => $v, 'brief' => 'Realistic weather by season, the non-negotiable backup plan, tenting costs, and heat/bug/rain contingencies.'],
            ['slug' => 'all-inclusive-vs-diy-wedding-venue', 'title' => 'All-Inclusive vs DIY Wedding Venues: Which Saves More?', 'category' => $v, 'brief' => 'The real cost-and-effort trade-off, what "all-inclusive" can hide, and when DIY actually saves money.'],
            ['slug' => 'questions-to-ask-a-wedding-caterer', 'title' => 'Questions to Ask a Wedding Caterer Before You Book', 'category' => $v, 'brief' => 'Tastings, per-head pricing, dietary handling, staffing, corkage, and the hidden fees to watch for. Ontario context.'],
            ['slug' => 'winery-weddings-ontario', 'title' => 'Winery Weddings in Ontario: What to Know', 'category' => $v, 'brief' => 'Regions like Niagara and Prince Edward County, what they cost, the best seasons, and the pros and cons.'],
            ['slug' => 'wedding-venue-capacity-guide', 'title' => 'How Many Guests Will Fit? A Wedding Venue Capacity Guide', 'category' => $v, 'brief' => 'Seated vs cocktail vs dance-floor capacity, space-per-guest rules of thumb, and why "maximum capacity" misleads.'],

            ['slug' => 'how-to-choose-a-wedding-florist', 'title' => 'How to Choose a Wedding Florist in Ontario', 'category' => $g, 'brief' => 'Matching style, seasonal/local flowers and what they cost, what a given budget gets you, the questions to ask, and the red flags.'],
            ['slug' => 'how-to-choose-a-wedding-dj', 'title' => 'How to Choose a Wedding DJ (Questions That Reveal a Pro)', 'category' => $g, 'brief' => 'Experience vs price, MC skills, equipment and backups, song requests, and the contract must-haves.'],
            ['slug' => 'do-you-need-a-wedding-videographer', 'title' => 'Do You Need a Wedding Videographer? How to Choose One', 'category' => $g, 'brief' => 'Photo vs video, the main styles, what to budget in Ontario, deliverables and timelines, and the questions to ask.'],
            ['slug' => 'should-you-hire-a-wedding-planner-ontario', 'title' => 'Should You Hire a Wedding Planner? An Ontario Guide', 'category' => $g, 'brief' => 'Full vs partial vs day-of coordination, what each costs and does, and when it is genuinely worth it.'],
            ['slug' => 'how-to-spot-fake-wedding-vendor-reviews', 'title' => 'How to Read Wedding Vendor Reviews (and Spot Fake Ones)', 'category' => $g, 'brief' => 'What trustworthy reviews look like, why booking-verified reviews matter, and the red flags of paid or fake ones.'],
            ['slug' => 'wedding-cake-guide-ontario', 'title' => 'A Practical Guide to Wedding Cakes in Ontario', 'category' => $g, 'brief' => 'Pricing per slice, flavours and tiers, tastings, alternatives like dessert tables, and the questions for your baker.'],
            ['slug' => 'booking-wedding-hair-and-makeup', 'title' => 'Booking Wedding Hair and Makeup: A Complete Guide', 'category' => $g, 'brief' => 'Trials, timing on the day, per-person costs, travel fees, and the questions to ask before you book.'],
        ];
    }
}

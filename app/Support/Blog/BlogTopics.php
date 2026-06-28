<?php

namespace App\Support\Blog;

use App\Enums\BlogCategory;

/**
 * The curated Ontario-wedding topic queue the blog autopilot draws from — ~100
 * keyword-targeted posts organised into topic CLUSTERS (hub & spoke). Each topic
 * carries a stable slug (for de-dupe) and a `cluster`; the autopilot links each
 * new post up to its cluster pillar + already-published siblings, building real
 * topical authority instead of 100 disconnected posts. See docs/BLOG-CONTENT-PLAN.md.
 *
 * New cluster PILLARS are listed first so they publish before their spokes (so a
 * spoke always has a pillar to link to). Five pillars already exist as seeded
 * posts; their clusters point at those slugs. Real Weddings is excluded
 * (it implies real couples/stories, which must never be AI-fabricated).
 */
class BlogTopics
{
    /**
     * cluster key => the pillar (hub) post each cluster's spokes link up to.
     * Some pillars are existing seeded posts; others are generated first below.
     *
     * @return array<string, array{pillar:string, label:string}>
     */
    public static function clusters(): array
    {
        return [
            'costs' => ['pillar' => 'how-much-does-a-wedding-cost-in-ontario', 'label' => 'Wedding costs'],
            'planning' => ['pillar' => 'ontario-wedding-planning-timeline', 'label' => 'Planning timeline'],
            'free' => ['pillar' => 'how-to-plan-a-wedding-for-free-ontario', 'label' => 'Free planning'],
            'venues' => ['pillar' => 'questions-to-ask-wedding-venue', 'label' => 'Venues'],
            'photo' => ['pillar' => 'how-to-choose-a-wedding-photographer-ontario', 'label' => 'Photography & video'],
            'vendors' => ['pillar' => 'how-to-choose-your-wedding-vendors-ontario', 'label' => 'Choosing vendors'],
            'flowers' => ['pillar' => 'wedding-flowers-guide-ontario', 'label' => 'Flowers & decor'],
            'food' => ['pillar' => 'wedding-catering-guide-ontario', 'label' => 'Food & drink'],
            'music' => ['pillar' => 'wedding-music-entertainment-guide', 'label' => 'Music & entertainment'],
            'attire' => ['pillar' => 'wedding-attire-and-beauty-guide', 'label' => 'Attire & beauty'],
            'guests' => ['pillar' => 'wedding-guest-list-and-rsvp-guide', 'label' => 'Guests & RSVP'],
            'honeymoon' => ['pillar' => 'honeymoon-planning-guide', 'label' => 'Honeymoon & travel'],
            'registry' => ['pillar' => 'wedding-registry-guide', 'label' => 'Registry & gifts'],
            'seasons' => ['pillar' => 'getting-married-in-ontario-guide', 'label' => 'Getting married in Ontario'],
        ];
    }

    /**
     * @return list<array{slug:string, title:string, category:string, cluster:string, brief:string}>
     */
    public static function all(): array
    {
        $b = BlogCategory::Budgeting->value;
        $p = BlogCategory::PlanningTips->value;
        $v = BlogCategory::Venues->value;
        $g = BlogCategory::VendorGuides->value;

        return [
            // ── New cluster PILLARS (publish first so spokes can link up) ──────
            ['slug' => 'how-to-choose-your-wedding-vendors-ontario', 'title' => 'How to Choose Your Wedding Vendors in Ontario', 'category' => $g, 'cluster' => 'vendors', 'brief' => 'An overview of every wedding vendor type, what each costs in Ontario, when to book, and how to vet them.'],
            ['slug' => 'wedding-flowers-guide-ontario', 'title' => 'A Complete Guide to Wedding Flowers in Ontario', 'category' => $g, 'cluster' => 'flowers', 'brief' => 'Choosing a florist, seasonal/local blooms, what florals cost, and where to spend vs save.'],
            ['slug' => 'wedding-catering-guide-ontario', 'title' => 'A Guide to Wedding Catering in Ontario', 'category' => $g, 'cluster' => 'food', 'brief' => 'Catering styles, per-head costs, bar options, dietary planning, and tasting tips for Ontario couples.'],
            ['slug' => 'wedding-music-entertainment-guide', 'title' => 'A Guide to Wedding Music and Entertainment', 'category' => $g, 'cluster' => 'music', 'brief' => 'DJ vs band, ceremony/cocktail/reception music, costs, and keeping the party going.'],
            ['slug' => 'wedding-attire-and-beauty-guide', 'title' => 'The Wedding Attire and Beauty Guide', 'category' => $g, 'cluster' => 'attire', 'brief' => 'Dresses, suits, alterations timelines, hair and makeup, and realistic budgets.'],
            ['slug' => 'wedding-guest-list-and-rsvp-guide', 'title' => 'The Wedding Guest List and RSVP Guide', 'category' => $p, 'cluster' => 'guests', 'brief' => 'Building and trimming the list, plus-ones, save-the-dates, invitations, and RSVPs.'],
            ['slug' => 'honeymoon-planning-guide', 'title' => 'The Honeymoon Planning Guide for Canadian Couples', 'category' => $p, 'cluster' => 'honeymoon', 'brief' => 'How to budget, choose a destination, when to book, and using a honeymoon fund.'],
            ['slug' => 'wedding-registry-guide', 'title' => 'The Modern Wedding Registry Guide', 'category' => $p, 'cluster' => 'registry', 'brief' => 'Cash funds vs gift items vs honeymoon funds, registry etiquette, and thank-yous.'],
            ['slug' => 'getting-married-in-ontario-guide', 'title' => 'Getting Married in Ontario: The Complete Guide', 'category' => $p, 'cluster' => 'seasons', 'brief' => 'Seasons, marriage licence basics, regions, and what makes Ontario weddings unique.'],

            // ── Cluster 1: Wedding costs (pillar: seeded cost post) ────────────
            ['slug' => 'how-to-save-money-on-a-wedding-in-ontario', 'title' => 'How to Save Money on a Wedding in Ontario', 'category' => $b, 'cluster' => 'costs', 'brief' => 'Specific ways to cut costs: dates, guest count, bar, decor priorities — and where not to cut.'],
            ['slug' => 'average-wedding-cost-toronto', 'title' => 'The Average Cost of a Wedding in Toronto (2026)', 'category' => $b, 'cluster' => 'costs', 'brief' => 'Toronto-specific numbers, why the GTA runs higher, and how to save in the city.'],
            ['slug' => 'average-wedding-cost-ottawa', 'title' => 'The Average Cost of a Wedding in Ottawa (2026)', 'category' => $b, 'cluster' => 'costs', 'brief' => 'Ottawa cost numbers, local venue context, and how to save in the capital region.'],
            ['slug' => 'average-wedding-cost-hamilton', 'title' => 'The Average Cost of a Wedding in Hamilton (2026)', 'category' => $b, 'cluster' => 'costs', 'brief' => 'Hamilton-area costs and why it draws couples wanting character without Toronto prices.'],
            ['slug' => 'average-wedding-cost-niagara', 'title' => 'The Average Cost of a Wedding in Niagara (2026)', 'category' => $b, 'cluster' => 'costs', 'brief' => 'Niagara wine-country wedding costs, seasonality, and what drives the price.'],
            ['slug' => 'wedding-budget-breakdown-by-category', 'title' => 'Wedding Budget Breakdown: How to Split Your Spend', 'category' => $b, 'cluster' => 'costs', 'brief' => 'Standard percentage allocation by category with a worked example for a $30k Ontario budget.'],
            ['slug' => 'micro-wedding-cost-ontario', 'title' => 'Micro-Weddings in Ontario: What They Cost', 'category' => $b, 'cluster' => 'costs', 'brief' => 'Typical cost for 20–50 guests, trade-offs, and how the math differs from a full wedding.'],
            ['slug' => 'who-pays-for-what-wedding', 'title' => 'Who Pays for What at a Wedding? (A Modern Take)', 'category' => $b, 'cluster' => 'costs', 'brief' => 'Traditional vs modern splits and how to have the money conversation.'],
            ['slug' => 'hidden-wedding-costs-to-watch', 'title' => 'The Hidden Wedding Costs Couples Forget', 'category' => $b, 'cluster' => 'costs', 'brief' => 'Gratuities, taxes, overtime, vendor meals, alterations — the line items that blow budgets.'],
            ['slug' => 'wedding-cost-per-guest', 'title' => 'How Much Does a Wedding Cost Per Guest?', 'category' => $b, 'cluster' => 'costs', 'brief' => 'The real per-guest math and why guest count is the biggest lever in your budget.'],

            // ── Cluster 2: Planning timeline (pillar: seeded timeline post) ────
            ['slug' => 'how-to-plan-an-ontario-wedding-in-6-months', 'title' => 'How to Plan an Ontario Wedding in 6 Months', 'category' => $p, 'cluster' => 'planning', 'brief' => 'A condensed timeline for short-engagement couples: what to prioritise, what to drop.'],
            ['slug' => 'wedding-day-timeline-template', 'title' => 'A Wedding Day Timeline Template (Hour by Hour)', 'category' => $p, 'cluster' => 'planning', 'brief' => 'A realistic run-of-show from getting-ready to send-off, with buffer advice.'],
            ['slug' => 'biggest-wedding-planning-mistakes', 'title' => 'The Biggest Wedding Planning Mistakes to Avoid', 'category' => $p, 'cluster' => 'planning', 'brief' => 'Common, costly mistakes Ontario couples make and how to sidestep them.'],
            ['slug' => 'marriage-licence-ontario', 'title' => 'How to Get a Marriage Licence in Ontario', 'category' => $p, 'cluster' => 'planning', 'brief' => 'The process, cost, ID, the 90-day validity, and common mistakes (verify with ServiceOntario).'],
            ['slug' => 'how-to-stay-sane-planning-a-wedding', 'title' => 'How to Stay Sane While Planning a Wedding', 'category' => $p, 'cluster' => 'planning', 'brief' => 'Splitting the workload, decision fatigue, delegating, and the “good enough” mindset.'],
            ['slug' => 'how-to-elope-in-ontario', 'title' => 'How to Elope in Ontario (A Practical Guide)', 'category' => $p, 'cluster' => 'planning', 'brief' => 'What eloping involves, costs, the licence, and beautiful Ontario spots to do it.'],
            ['slug' => 'wedding-planning-checklist-for-beginners', 'title' => 'A Wedding Planning Checklist for Total Beginners', 'category' => $p, 'cluster' => 'planning', 'brief' => 'Where to actually start when you have no idea where to start.'],
            ['slug' => 'what-to-do-right-after-getting-engaged', 'title' => 'Just Engaged? Here’s What to Do First', 'category' => $p, 'cluster' => 'planning', 'brief' => 'The first five decisions that unlock everything else — before you book anything.'],

            // ── Cluster 3: Free planning & tools (pillar: seeded free post) ────
            ['slug' => 'free-wedding-website-builder-guide', 'title' => 'How to Make a Free Wedding Website', 'category' => $p, 'cluster' => 'free', 'brief' => 'What a wedding website should include and how to build one free, with RSVP and registry.'],
            ['slug' => 'how-to-make-a-wedding-seating-chart', 'title' => 'How to Make a Wedding Seating Chart', 'category' => $p, 'cluster' => 'free', 'brief' => 'A step-by-step approach, handling family dynamics, and free tools that help.'],
            ['slug' => 'free-wedding-guest-list-tools', 'title' => 'How to Manage Your Wedding Guest List for Free', 'category' => $p, 'cluster' => 'free', 'brief' => 'Tracking invites, addresses, RSVPs and meals without paying for an app.'],
            ['slug' => 'how-to-make-a-wedding-budget', 'title' => 'How to Make a Wedding Budget (Free Template Approach)', 'category' => $b, 'cluster' => 'free', 'brief' => 'Setting a total, capping categories, and tracking real quotes against it.'],
            ['slug' => 'digital-vs-paper-wedding-rsvp', 'title' => 'Digital vs Paper Wedding RSVPs: Which Is Better?', 'category' => $p, 'cluster' => 'free', 'brief' => 'Pros, cons, costs and response rates of online vs mailed RSVPs.'],
            ['slug' => 'best-free-wedding-planning-tools', 'title' => 'The Best Free Wedding Planning Tools (2026)', 'category' => $p, 'cluster' => 'free', 'brief' => 'A roundup of what you can do for free — checklist, budget, seating, website, registry.'],

            // ── Cluster 4: Venues (pillar: seeded venue post) ─────────────────
            ['slug' => 'types-of-wedding-venues-ontario', 'title' => 'Types of Wedding Venues in Ontario', 'category' => $v, 'cluster' => 'venues', 'brief' => 'Barns, ballrooms, wineries, gardens, lofts — vibe, capacity, cost and who each suits.'],
            ['slug' => 'planning-an-outdoor-wedding-in-ontario', 'title' => 'Planning an Outdoor Wedding in Ontario', 'category' => $v, 'cluster' => 'venues', 'brief' => 'Weather by season, the backup-plan must, tenting costs and contingencies.'],
            ['slug' => 'all-inclusive-vs-diy-wedding-venue', 'title' => 'All-Inclusive vs DIY Wedding Venues', 'category' => $v, 'cluster' => 'venues', 'brief' => 'The real cost-and-effort trade-off and when DIY actually saves money.'],
            ['slug' => 'winery-weddings-ontario', 'title' => 'Winery Weddings in Ontario: What to Know', 'category' => $v, 'cluster' => 'venues', 'brief' => 'Niagara and Prince Edward County, costs, best seasons and the trade-offs.'],
            ['slug' => 'barn-wedding-venues-ontario', 'title' => 'Barn Wedding Venues in Ontario', 'category' => $v, 'cluster' => 'venues', 'brief' => 'What rustic/barn venues cost, what to check, and the practicalities of a country wedding.'],
            ['slug' => 'wedding-venue-capacity-guide', 'title' => 'How Many Guests Will Fit? A Venue Capacity Guide', 'category' => $v, 'cluster' => 'venues', 'brief' => 'Seated vs cocktail vs dance-floor capacity and why “max capacity” misleads.'],
            ['slug' => 'average-wedding-venue-cost-ontario', 'title' => 'How Much Does a Wedding Venue Cost in Ontario?', 'category' => $b, 'cluster' => 'venues', 'brief' => 'Typical venue pricing, what’s included, minimums, and seasonal/day-of-week swings.'],
            ['slug' => 'backyard-wedding-guide', 'title' => 'How to Plan a Backyard Wedding', 'category' => $v, 'cluster' => 'venues', 'brief' => 'The hidden costs of “free” home weddings — rentals, power, washrooms, permits.'],
            ['slug' => 'loft-and-industrial-wedding-venues', 'title' => 'Loft and Industrial Wedding Venues in Ontario', 'category' => $v, 'cluster' => 'venues', 'brief' => 'Blank-canvas urban spaces: what they cost and what you have to bring in.'],
            ['slug' => 'best-wedding-venues-toronto', 'title' => 'How to Find the Best Wedding Venue in Toronto', 'category' => $v, 'cluster' => 'venues', 'brief' => 'Toronto venue styles, neighbourhoods and what to ask before booking.'],
            ['slug' => 'best-wedding-venues-niagara', 'title' => 'How to Find a Wedding Venue in Niagara', 'category' => $v, 'cluster' => 'venues', 'brief' => 'Niagara venue types — vineyards, falls-view, estates — and how to choose.'],

            // ── Cluster 5: Photography & video (pillar: seeded photo post) ─────
            ['slug' => 'how-much-does-wedding-photography-cost-ontario', 'title' => 'How Much Does Wedding Photography Cost in Ontario?', 'category' => $b, 'cluster' => 'photo', 'brief' => 'Typical price ranges, what drives them, and why very cheap is a red flag.'],
            ['slug' => 'wedding-photography-styles-explained', 'title' => 'Wedding Photography Styles, Explained', 'category' => $g, 'cluster' => 'photo', 'brief' => 'Light-and-airy, dark-and-moody, documentary, editorial — how to know what you like.'],
            ['slug' => 'should-you-do-an-engagement-shoot', 'title' => 'Should You Do an Engagement Shoot?', 'category' => $g, 'cluster' => 'photo', 'brief' => 'What an engagement session costs, why it helps, and how to make the most of it.'],
            ['slug' => 'do-you-need-a-wedding-videographer', 'title' => 'Do You Need a Wedding Videographer?', 'category' => $g, 'cluster' => 'photo', 'brief' => 'Photo vs video, what video costs in Ontario, and what you actually get.'],
            ['slug' => 'how-to-choose-a-wedding-videographer', 'title' => 'How to Choose a Wedding Videographer', 'category' => $g, 'cluster' => 'photo', 'brief' => 'Styles, deliverables, timelines and the questions that reveal a pro.'],
            ['slug' => 'wedding-photo-shot-list', 'title' => 'The Wedding Photo Shot List You Actually Need', 'category' => $g, 'cluster' => 'photo', 'brief' => 'Must-have shots and how to brief your photographer without micromanaging.'],
            ['slug' => 'what-is-a-first-look', 'title' => 'What Is a First Look (and Should You Do One)?', 'category' => $g, 'cluster' => 'photo', 'brief' => 'The pros and cons of a first look and how it changes your day-of timeline.'],

            // ── Cluster 6: Choosing vendors (pillar: new vendors pillar) ──────
            ['slug' => 'how-to-choose-a-wedding-florist', 'title' => 'How to Choose a Wedding Florist in Ontario', 'category' => $g, 'cluster' => 'vendors', 'brief' => 'Style match, seasonal flowers, what a budget gets you, and the red flags.'],
            ['slug' => 'how-to-choose-a-wedding-dj', 'title' => 'How to Choose a Wedding DJ', 'category' => $g, 'cluster' => 'vendors', 'brief' => 'Experience vs price, MC skills, backups, and the contract must-haves.'],
            ['slug' => 'questions-to-ask-a-wedding-caterer', 'title' => 'Questions to Ask a Wedding Caterer', 'category' => $g, 'cluster' => 'vendors', 'brief' => 'Tastings, per-head pricing, dietary handling, staffing and hidden fees.'],
            ['slug' => 'wedding-cake-guide-ontario', 'title' => 'A Practical Guide to Wedding Cakes in Ontario', 'category' => $g, 'cluster' => 'vendors', 'brief' => 'Pricing per slice, flavours and tiers, tastings and alternatives.'],
            ['slug' => 'booking-wedding-hair-and-makeup', 'title' => 'Booking Wedding Hair and Makeup', 'category' => $g, 'cluster' => 'vendors', 'brief' => 'Trials, day-of timing, per-person costs, travel fees and questions to ask.'],
            ['slug' => 'how-to-choose-a-wedding-officiant', 'title' => 'How to Choose a Wedding Officiant in Ontario', 'category' => $g, 'cluster' => 'vendors', 'brief' => 'Religious vs secular vs bilingual, what it costs, and personalising your ceremony.'],
            ['slug' => 'should-you-hire-a-wedding-planner-ontario', 'title' => 'Should You Hire a Wedding Planner?', 'category' => $g, 'cluster' => 'vendors', 'brief' => 'Full vs partial vs day-of coordination, costs, and when it’s worth it.'],
            ['slug' => 'how-to-spot-fake-wedding-vendor-reviews', 'title' => 'How to Read Wedding Vendor Reviews (and Spot Fakes)', 'category' => $g, 'cluster' => 'vendors', 'brief' => 'What trustworthy reviews look like and why booking-verified reviews matter.'],

            // ── Cluster 7: Flowers & decor (pillar: new flowers pillar) ───────
            ['slug' => 'best-seasonal-wedding-flowers-ontario', 'title' => 'The Best Seasonal Wedding Flowers in Ontario', 'category' => $g, 'cluster' => 'flowers', 'brief' => 'What’s in season by month and how local/seasonal choices cut cost.'],
            ['slug' => 'how-much-do-wedding-flowers-cost', 'title' => 'How Much Do Wedding Flowers Cost?', 'category' => $b, 'cluster' => 'flowers', 'brief' => 'Typical florals budget, what a given amount gets you, and where to save.'],
            ['slug' => 'budget-wedding-centerpiece-ideas', 'title' => 'Budget Wedding Centerpiece Ideas That Look Expensive', 'category' => $g, 'cluster' => 'flowers', 'brief' => 'High-impact, low-cost centrepiece and tablescape ideas.'],
            ['slug' => 'real-vs-artificial-wedding-flowers', 'title' => 'Real vs Artificial Wedding Flowers', 'category' => $g, 'cluster' => 'flowers', 'brief' => 'Cost, look, logistics and when faux actually makes sense.'],
            ['slug' => 'wedding-colour-palette-ideas', 'title' => 'How to Choose Your Wedding Colour Palette', 'category' => $g, 'cluster' => 'flowers', 'brief' => 'Building a palette that works with your season, venue and flowers.'],
            ['slug' => 'how-to-choose-your-wedding-bouquet', 'title' => 'How to Choose Your Wedding Bouquet', 'category' => $g, 'cluster' => 'flowers', 'brief' => 'Shapes, blooms, size and cost — matching the bouquet to your dress and day.'],

            // ── Cluster 8: Food & drink (pillar: new food pillar) ─────────────
            ['slug' => 'wedding-catering-styles-explained', 'title' => 'Wedding Catering Styles, Explained', 'category' => $g, 'cluster' => 'food', 'brief' => 'Plated, buffet, family-style and stations — cost, feel and logistics of each.'],
            ['slug' => 'wedding-bar-options-and-costs', 'title' => 'Wedding Bar Options and What They Cost', 'category' => $b, 'cluster' => 'food', 'brief' => 'Open bar vs cash bar vs signature cocktails, and how to control the tab.'],
            ['slug' => 'planning-for-dietary-restrictions-wedding', 'title' => 'How to Handle Dietary Restrictions at Your Wedding', 'category' => $p, 'cluster' => 'food', 'brief' => 'Collecting needs, working with your caterer, and labelling for guests.'],
            ['slug' => 'late-night-wedding-snack-ideas', 'title' => 'Late-Night Wedding Snack Ideas Guests Love', 'category' => $g, 'cluster' => 'food', 'brief' => 'Crowd-pleasing late-night bites and what they typically cost.'],
            ['slug' => 'what-to-expect-at-a-catering-tasting', 'title' => 'What to Expect at a Wedding Catering Tasting', 'category' => $g, 'cluster' => 'food', 'brief' => 'How tastings work, what to ask, and how to lock in your final menu.'],

            // ── Cluster 9: Music & entertainment (pillar: new music pillar) ───
            ['slug' => 'how-much-does-a-wedding-dj-cost', 'title' => 'How Much Does a Wedding DJ Cost?', 'category' => $b, 'cluster' => 'music', 'brief' => 'Typical DJ pricing in Ontario, what’s included, and add-ons to budget for.'],
            ['slug' => 'how-to-choose-your-first-dance-song', 'title' => 'How to Choose Your First Dance Song', 'category' => $g, 'cluster' => 'music', 'brief' => 'Picking a song that fits you, the room, and your comfort on the floor.'],
            ['slug' => 'wedding-ceremony-music-ideas', 'title' => 'Wedding Ceremony Music Ideas', 'category' => $g, 'cluster' => 'music', 'brief' => 'Processional, signing and recessional ideas — live and recorded.'],
            ['slug' => 'live-band-vs-dj-wedding', 'title' => 'Live Band vs DJ: Which Is Right for Your Wedding?', 'category' => $g, 'cluster' => 'music', 'brief' => 'Cost, energy, space and flexibility — an honest comparison.'],
            ['slug' => 'how-to-keep-the-dance-floor-full', 'title' => 'How to Keep Your Wedding Dance Floor Full', 'category' => $g, 'cluster' => 'music', 'brief' => 'Timeline, song choices and the MC details that keep guests dancing.'],

            // ── Cluster 10: Attire & beauty (pillar: new attire pillar) ───────
            ['slug' => 'wedding-dress-shopping-timeline', 'title' => 'The Wedding Dress Shopping Timeline', 'category' => $g, 'cluster' => 'attire', 'brief' => 'When to start, how long alterations take, and how to avoid a rush.'],
            ['slug' => 'how-much-to-budget-for-a-wedding-dress', 'title' => 'How Much to Budget for a Wedding Dress', 'category' => $b, 'cluster' => 'attire', 'brief' => 'Typical dress + alterations + accessories costs in Ontario.'],
            ['slug' => 'groom-suit-and-tuxedo-guide', 'title' => 'The Groom’s Suit and Tuxedo Guide', 'category' => $g, 'cluster' => 'attire', 'brief' => 'Buy vs rent, fit and timeline, and coordinating with the wedding party.'],
            ['slug' => 'wedding-hair-and-makeup-trial-tips', 'title' => 'How to Get the Most From Your Hair and Makeup Trial', 'category' => $g, 'cluster' => 'attire', 'brief' => 'When to book it, what to bring, and how to communicate your look.'],
            ['slug' => 'bridal-skincare-timeline', 'title' => 'A Bridal Skincare Timeline Before the Wedding', 'category' => $g, 'cluster' => 'attire', 'brief' => 'A sensible, no-nonsense lead-up so your skin looks its best (no fads).'],

            // ── Cluster 11: Guests & RSVP (pillar: new guests pillar) ─────────
            ['slug' => 'how-to-make-a-wedding-guest-list', 'title' => 'How to Make Your Wedding Guest List', 'category' => $p, 'cluster' => 'guests', 'brief' => 'A framework for building the list, plus the cost-per-guest reality.'],
            ['slug' => 'how-to-cut-down-your-guest-list', 'title' => 'How to Cut Down Your Wedding Guest List', 'category' => $p, 'cluster' => 'guests', 'brief' => 'Tiers, rules and tactful ways to trim without family drama.'],
            ['slug' => 'wedding-plus-one-etiquette', 'title' => 'Wedding Plus-One Etiquette', 'category' => $p, 'cluster' => 'guests', 'brief' => 'Who gets a plus-one, how to word it, and handling the awkward asks.'],
            ['slug' => 'wedding-rsvp-etiquette', 'title' => 'Wedding RSVP Etiquette and Timelines', 'category' => $p, 'cluster' => 'guests', 'brief' => 'When to set the deadline, how to word it, and chasing non-responders.'],
            ['slug' => 'save-the-dates-vs-invitations', 'title' => 'Save-the-Dates vs Invitations: What to Send When', 'category' => $p, 'cluster' => 'guests', 'brief' => 'Timing, what each includes, and who to send each to.'],
            ['slug' => 'wedding-invitation-wording-guide', 'title' => 'A Wedding Invitation Wording Guide', 'category' => $p, 'cluster' => 'guests', 'brief' => 'Templates and etiquette for hosts, times, dress code and RSVP.'],

            // ── Cluster 12: Honeymoon & travel (pillar: new honeymoon pillar) ─
            ['slug' => 'how-to-budget-for-a-honeymoon', 'title' => 'How to Budget for a Honeymoon', 'category' => $p, 'cluster' => 'honeymoon', 'brief' => 'Setting a realistic honeymoon budget and where the money goes.'],
            ['slug' => 'best-honeymoon-destinations-from-canada', 'title' => 'Best Honeymoon Destinations From Canada', 'category' => $p, 'cluster' => 'honeymoon', 'brief' => 'Great-value and bucket-list destinations by season and budget.'],
            ['slug' => 'what-is-a-honeymoon-fund', 'title' => 'What Is a Honeymoon Fund (and How to Set One Up)?', 'category' => $p, 'cluster' => 'honeymoon', 'brief' => 'How honeymoon funds work and how guests can contribute to the real trip.'],
            ['slug' => 'when-to-book-your-honeymoon', 'title' => 'When Should You Book Your Honeymoon?', 'category' => $p, 'cluster' => 'honeymoon', 'brief' => 'Timing flights and stays for the best price, and post-wedding recovery time.'],
            ['slug' => 'all-inclusive-vs-touring-honeymoon', 'title' => 'All-Inclusive vs Touring Honeymoon', 'category' => $p, 'cluster' => 'honeymoon', 'brief' => 'Relax vs explore — cost, pace and which suits you.'],

            // ── Cluster 13: Registry & gifts (pillar: new registry pillar) ────
            ['slug' => 'cash-fund-vs-gift-registry', 'title' => 'Cash Fund vs Gift Registry: Which Is Right?', 'category' => $p, 'cluster' => 'registry', 'brief' => 'Pros and cons of cash funds vs traditional gift registries.'],
            ['slug' => 'how-a-honeymoon-fund-works', 'title' => 'How a Honeymoon Fund Works for Your Registry', 'category' => $p, 'cluster' => 'registry', 'brief' => 'Letting guests fund flights, hotels and experiences toward your trip.'],
            ['slug' => 'wedding-registry-etiquette', 'title' => 'Wedding Registry Etiquette', 'category' => $p, 'cluster' => 'registry', 'brief' => 'How to share it, price ranges, and what’s polite (and what isn’t).'],
            ['slug' => 'how-to-write-wedding-thank-you-notes', 'title' => 'How to Write Wedding Thank-You Notes', 'category' => $p, 'cluster' => 'registry', 'brief' => 'Timelines, wording and a system so none slip through the cracks.'],
            ['slug' => 'what-to-put-on-your-wedding-registry', 'title' => 'What to Put on Your Wedding Registry', 'category' => $p, 'cluster' => 'registry', 'brief' => 'A practical mix of items, funds and experiences across price points.'],

            // ── Cluster 14: Getting married in Ontario (pillar: new seasons) ──
            ['slug' => 'best-time-of-year-to-get-married-in-ontario', 'title' => 'The Best Time of Year to Get Married in Ontario', 'category' => $p, 'cluster' => 'seasons', 'brief' => 'Season-by-season pros and cons — weather, pricing, daylight, availability.'],
            ['slug' => 'winter-weddings-in-ontario', 'title' => 'Planning a Winter Wedding in Ontario', 'category' => $v, 'cluster' => 'seasons', 'brief' => 'The magic and the logistics — cost savings, weather plans and cosy details.'],
            ['slug' => 'fall-weddings-in-ontario', 'title' => 'Planning a Fall Wedding in Ontario', 'category' => $v, 'cluster' => 'seasons', 'brief' => 'Why autumn is peak season here, colours, and booking far ahead.'],
            ['slug' => 'summer-wedding-heat-tips', 'title' => 'How to Plan a Summer Wedding (and Beat the Heat)', 'category' => $p, 'cluster' => 'seasons', 'brief' => 'Shade, hydration, timing and guest comfort for a hot-weather wedding.'],
            ['slug' => 'long-weekend-wedding-pros-and-cons', 'title' => 'Long-Weekend Weddings: Pros and Cons', 'category' => $p, 'cluster' => 'seasons', 'brief' => 'Travel, attendance and pricing trade-offs of a holiday-weekend date.'],
        ];
    }
}

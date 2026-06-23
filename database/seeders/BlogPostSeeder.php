<?php

namespace Database\Seeders;

use App\Enums\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Starter, Ontario-targeted SEO articles so the blog ranks and isn't empty at
 * launch. Idempotent (updateOrCreate by slug) — safe to re-run.
 */
class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->posts() as $i => $post) {
            BlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                array_merge($post, [
                    'status' => 'published',
                    'author_name' => 'VowNook',
                    'published_at' => Carbon::now()->subDays(($i + 1) * 9),
                ]),
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    protected function posts(): array
    {
        return [
            [
                'slug' => 'how-to-plan-a-wedding-for-free-ontario',
                'title' => 'How to Plan Your Wedding for Free in Ontario (2026)',
                'cover_image_path' => 'images/blog/timeline.webp',
                'cover_alt' => 'A wedding planning checklist, budget notes and fresh flowers laid out on a bright desk',
                'category' => BlogCategory::PlanningTips->value,
                'excerpt' => 'You can plan an entire Ontario wedding — checklist, budget, seating, website and registry — without paying for planning tools. Here\'s the free-first way to do it.',
                'meta_description' => 'How to plan your wedding for free in Ontario: the free tools that replace pricey planning apps — checklist, budget, seating chart, wedding website and registry — plus trusted vendors.',
                'body' => <<<'MD'
You don't need to pay for a wedding-planning app. In 2026 you can plan an entire Ontario wedding — guest list, budget, seating chart, a wedding website and a gift registry — without spending a dollar on planning tools. The trick is knowing which pieces to set up, and in what order.

Here's the free-first approach that actually works.

## Begin with three numbers

Before any tool, lock in your **budget**, a rough **guest count**, and a **season**. Every other decision flows from these three — and getting them down on paper prevents the most expensive mistakes. If you're not sure what's realistic, our breakdown of [what a wedding really costs in Ontario](/blog/how-much-does-a-wedding-cost-in-ontario) is a good place to start.

## Keep everything in one free workspace

Planning feels chaotic because it lives in ten places — a notes app, three spreadsheets, your inbox. Put it in one **free** workspace instead: a checklist that knows your wedding date, a budget that tracks real quotes against your cap, and a guest list with RSVPs. You can set all of this up for free in our [planning workspace](/dashboard) — no trial, no card.

## Build your seating chart without the pricey tools

Seating tools are notorious for hiding the good part behind a paywall. You can skip that: arrange tables, drag guests into seats, count meal choices for the caterer and export a print-ready chart — free. Do it once your RSVPs are mostly in, about 2–3 weeks out.

## Give guests a free wedding website + registry

A wedding website answers the same questions a hundred times so you don't have to — date, venue, dress code, travel, RSVP. Pair it with a **registry** (cash funds, a honeymoon fund or gift items) and your guests have everything in one link. Both are free, on your own `name.vownook.com` address.

## Find vendors with reviews you can actually trust

The one place "free" usually breaks down is vendors — and the bigger problem is reviews you can't believe. Look for a marketplace where **every review is tied to a real booking**, so the five-stars mean something. Browse [Ontario wedding vendors](/marketplace), compare real quotes side by side, and keep them all in your workspace instead of buried in email.

## Is free really enough to plan a whole wedding?

For the planning itself, yes. The tools above cover the parts couples usually pay an app for — organization, budget, seating, a website and a registry. The only thing you pay for is the wedding itself: your vendors. And even there it's free to use — vendors pay a small fee only when they're booked, which is why couples never hit a paywall.

## Your free-first plan, in order

1. Set budget, guest count and season.
2. Open your free workspace — checklist, budget, guest list.
3. Build your wedding website and registry; share the link.
4. Shortlist and quote vendors; compare side by side.
5. Finalize the seating chart as RSVPs land.

Planning a wedding is a big job. Paying to plan it shouldn't be part of it. Start free in your [planning workspace](/dashboard), and book the rest with [vendors you can trust](/marketplace).
MD,
            ],
            [
                'slug' => 'how-much-does-a-wedding-cost-in-ontario',
                'title' => 'How Much Does a Wedding Cost in Ontario? (2026 Real Numbers)',
                'cover_image_path' => 'images/blog/cost.webp',
                'cover_alt' => 'An elegant Ontario wedding reception with beautifully set tables and soft daylight',
                'category' => BlogCategory::Budgeting->value,
                'excerpt' => 'A realistic breakdown of what couples actually spend on an Ontario wedding in 2026 — by category, by guest count, and where the money really goes.',
                'meta_description' => 'What a wedding really costs in Ontario in 2026: average totals, a full category breakdown (venue, catering, photography), and practical ways to spend less without it showing.',
                'body' => <<<'MD'
The honest answer: most Ontario weddings in 2026 land somewhere between **$25,000 and $45,000**, with the average around **$35,000** for roughly 100 guests. But "average" hides a lot — your venue choice, guest count and city move that number more than anything else.

Here's where the money actually goes, and how to keep it under control.

## The typical breakdown (100 guests)

- **Venue & rentals — 30%** (~$10,500). Usually your single biggest line.
- **Catering & bar — 25%** (~$8,750). Often priced per head, so guest count is everything.
- **Photography & video — 12%** (~$4,200). The thing you keep forever.
- **Flowers & decor — 8%** (~$2,800).
- **Attire & beauty — 7%** (~$2,450).
- **Music / DJ or band — 6%** (~$2,100).
- **Stationery, favours, cake & the rest — 12%** (~$4,200).

Toronto and the GTA run 15–25% above these numbers; Ottawa, London, Kitchener-Waterloo and Niagara tend to come in under.

## Guest count is the real budget lever

Every guest costs you a meal, a drink tab, a chair, a place setting and a slice of cake. Trimming a 120-person list to 90 can save **$5,000+** on its own — far more than haggling with any one vendor. Decide your number first, then build the budget around it.

## Where couples overspend

- **Saturday in peak season (June, September).** Friday, Sunday or an off-season date can cut venue pricing 20–30%.
- **A full open bar all night.** A signature cocktail + beer/wine is plenty and far cheaper.
- **Decor you'll see for four hours.** Spend on what's in every photo (florals at the centre, good lighting), save on the rest.

## Where not to cut

Photography and catering are the two things guests remember. A great photographer and food people actually enjoy are worth protecting in the budget.

## Build the budget before you book

Set your total, assign each category a dollar cap, and track real quotes against it. You can do this for free in our [planning workspace](/dashboard) — and when you're ready to price things out, compare real quotes from [Ontario wedding vendors](/marketplace) side by side instead of guessing.
MD,
            ],
            [
                'slug' => 'ontario-wedding-planning-timeline',
                'title' => 'The Complete Ontario Wedding Planning Timeline (12-Month Checklist)',
                'cover_image_path' => 'images/blog/timeline.webp',
                'cover_alt' => 'Wedding planning essentials on a marble desk — a planner, fresh flowers, swatches and a ring box',
                'category' => BlogCategory::PlanningTips->value,
                'excerpt' => 'Exactly what to do and when — a month-by-month checklist that keeps an Ontario wedding on track from "we\'re engaged" to "we do".',
                'meta_description' => 'A month-by-month Ontario wedding planning timeline: when to book the venue, send invitations, finalize the guest list and confirm vendors — a clear 12-month checklist.',
                'body' => <<<'MD'
A wedding feels enormous until you break it into months. Here's the order that actually works in Ontario, where the best venues and photographers book **12–18 months** ahead.

## 12+ months out

- Set your **budget** and rough **guest count** — every other decision depends on these.
- Pick 2–3 possible dates (have a season in mind).
- Book the **venue** and your **photographer**. These two sell out first.

## 9–11 months out

- Book the big vendors: **catering** (if not in-house), **band or DJ**, **florist**, **officiant**.
- Start the **guest list** in earnest and collect addresses.
- Book hotel room blocks if guests travel.

## 6–8 months out

- Order attire — dresses and suits need time for alterations.
- Send **save-the-dates**.
- Plan the **ceremony** and book any rentals (chairs, tent, decor).
- Book hair & makeup and do a trial.

## 4–5 months out

- Finalize the **menu** and do a tasting.
- Order **invitations** and your day-of stationery.
- Confirm transportation and the wedding-night stay.

## 2–3 months out

- Mail invitations (aim for ~8 weeks before; destination guests earlier).
- Apply for your **marriage licence** — in Ontario it's valid for 90 days, so don't get it too early.
- Build the **seating plan** as RSVPs land.

## 1 month out

- Chase final RSVPs and give the venue your headcount.
- Confirm timing and details with every vendor.
- Final dress fitting; break in your shoes.

## The final week

- Hand the venue and coordinator a **run-of-show** timeline.
- Pack an emergency kit, confirm payments and tips, and delegate day-of tasks.
- Rehearse, then stop planning and enjoy it.

You don't have to track this on paper. Generate a personalized checklist, budget and day-of timeline for free in our [planning workspace](/dashboard), and find the vendors you need in the [Ontario marketplace](/marketplace).
MD,
            ],
            [
                'slug' => 'questions-to-ask-wedding-venue',
                'title' => '12 Questions to Ask Before You Book a Wedding Venue',
                'cover_image_path' => 'images/blog/venue.webp',
                'cover_alt' => 'A stunning Ontario wedding venue at golden hour with an outdoor ceremony aisle lined with florals',
                'category' => BlogCategory::Venues->value,
                'excerpt' => 'The venue is your biggest decision and your biggest cost. Ask these 12 questions before you sign — they reveal the hidden costs and dealbreakers.',
                'meta_description' => 'The 12 questions every couple should ask a wedding venue before booking — covering hidden fees, vendor rules, capacity, weather backup and exactly what\'s included.',
                'body' => <<<'MD'
Your venue sets the date, the guest count, the vibe and roughly a third of your budget. Before you fall in love with the photos, get straight answers to these.

## Money & the contract

1. **What's the all-in price**, including taxes, gratuity and service charges? The headline number is rarely the real number.
2. **What exactly is included** — tables, chairs, linens, setup, teardown, staff?
3. **What's the deposit, and the cancellation and postponement policy?** Read this twice.
4. **Are there minimum spends** on food or bar, and do they change by day or season?

## Logistics

5. **How many hours do we get**, and what does overtime cost?
6. **What's the real capacity** seated with a dance floor — not the theoretical maximum?
7. **Is there a backup plan for weather** if any part is outdoors?
8. **What's the parking and accessibility** situation for older guests?

## Vendors & rules

9. **Can we bring our own vendors**, or is there a required list? Forced vendor lists can quietly raise your costs.
10. **Is there a kitchen for outside caterers**, and a corkage fee if we supply alcohol?
11. **What are the noise and end-time rules?** Some Ontario venues cut music at 11pm.
12. **Who's our day-of contact**, and will they be there the whole event?

## A quick gut check

Walk the space at the **time of day** your wedding will happen — light changes everything. Picture 100 guests in the room, not an empty hall.

When you're comparing venues, line their quotes up against each other instead of juggling email threads. Browse and compare [Ontario wedding venues](/wedding-venues) in one place, and keep every quote organized in your [planning workspace](/dashboard).
MD,
            ],
            [
                'slug' => 'how-to-choose-a-wedding-photographer-ontario',
                'title' => 'How to Choose a Wedding Photographer in Ontario',
                'cover_image_path' => 'images/blog/photographer.webp',
                'cover_alt' => 'A wedding photographer capturing a couple outdoors in warm autumn Ontario light',
                'category' => BlogCategory::VendorGuides->value,
                'excerpt' => 'Your photos outlast the flowers, the cake and the dress. Here\'s how to choose a photographer whose style — and reliability — you\'ll still love in 20 years.',
                'meta_description' => 'How to choose a wedding photographer in Ontario: how to read their style, what to check in the contract, average pricing, and the questions that reveal a pro.',
                'body' => <<<'MD'
Almost everything at a wedding is temporary. The photographs aren't. They're also one of the few vendors you can't "fix" afterward — so choose carefully.

## Start with style, not price

Photographers fall into a few broad styles: **light and airy**, **dark and moody**, **classic and timeless**, **bold and editorial**. Look at *full galleries*, not just highlight reels — anyone can post ten perfect shots. You want to love the 800 photos, not the top 10.

## Look for consistency and full days

- Ask to see a **complete wedding** from start to finish, ideally at a venue like yours.
- Check they shoot well in **low light** — receptions are dark.
- Make sure the person whose work you love is the person **actually shooting your day**.

## What to confirm in the contract

- **Hours of coverage** and what happens if the day runs long.
- **Number of edited images** and the **delivery timeline** (8–12 weeks is normal).
- **Backup gear and a backup plan** if they're sick — true pros have a network.
- **Image rights**: you should be able to print and share freely.

## What it costs in Ontario

Experienced Ontario wedding photographers generally run **$2,500–$5,500** for full-day coverage, more in Toronto and for albums or a second shooter. Be wary of pricing far below this — it often means a beginner or someone who'll disappear at editing time.

## Questions that reveal a pro

- "How do you handle a tight or delayed timeline?"
- "What's your approach when the light or weather isn't cooperating?"
- "Can I talk to a couple you shot last season?"

A confident, specific answer tells you more than any price quote.

When you're ready, browse [wedding photographers in Ontario](/wedding-photographers), read reviews tied to real bookings, and request quotes you can compare side by side in your [planning workspace](/dashboard).
MD,
            ],
        ];
    }
}

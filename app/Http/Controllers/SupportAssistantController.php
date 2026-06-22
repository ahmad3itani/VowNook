<?php

namespace App\Http\Controllers;

use App\Support\Ai\AiException;
use App\Support\Ai\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * A lightweight AI help bot for the support page: answers common "how do I…"
 * questions about using VowNook from a curated knowledge base, so couples can
 * self-serve the easy things before opening a ticket. Degrades gracefully when
 * AI isn't configured (the front-end simply hides the bot).
 */
class SupportAssistantController extends Controller
{
    public function ask(Request $request, AiService $ai): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        // Curated instant answers for the most common questions. These work even
        // when the AI provider is slow, erroring, or unconfigured — so the help
        // bot always handles the basics ("how do I share my site?") reliably,
        // with zero latency and zero cost.
        if ($quick = $this->quickAnswer($data['question'])) {
            return response()->json(['available' => true, 'answer' => $quick, 'confident' => true]);
        }

        if (! $ai->isConfigured()) {
            return response()->json(['available' => false]);
        }

        $tool = [
            'name' => 'provide_help',
            'description' => 'Answer the user’s question about using VowNook.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'answer' => [
                        'type' => 'string',
                        'description' => 'A concise, friendly answer in plain language (1–3 short paragraphs; markdown lists allowed).',
                    ],
                    'confident' => [
                        'type' => 'boolean',
                        'description' => 'True only if you fully answered from the VowNook help knowledge. False for account-specific, billing, bug, or uncertain questions.',
                    ],
                ],
                'required' => ['answer', 'confident'],
            ],
        ];

        try {
            $result = $ai->generateStructured($this->systemPrompt(), $data['question'], $tool);
        } catch (AiException $e) {
            return response()->json([
                'available' => true,
                'answer' => $e->getMessage(),
                'confident' => false,
            ]);
        } catch (Throwable $e) {
            // Belt-and-braces: the help bot must never return a non-JSON 500 (which
            // the front-end can only surface as a scary "something went wrong"). Any
            // unexpected error degrades to a friendly nudge toward the request form.
            report($e);

            return response()->json([
                'available' => true,
                'answer' => 'Sorry — I couldn’t answer that just now. Please send a request below and our team will help.',
                'confident' => false,
            ]);
        }

        return response()->json([
            'available' => true,
            'answer' => (string) ($result['answer'] ?? ''),
            'confident' => (bool) ($result['confident'] ?? false),
        ]);
    }

    /**
     * Match the most common questions to a curated, always-correct answer so the
     * basics never depend on the AI provider being reachable. Returns null when
     * nothing clearly matches, and we fall through to the AI for the long tail.
     */
    private function quickAnswer(string $question): ?string
    {
        $q = mb_strtolower($question);

        $has = function (array $needles) use ($q): bool {
            foreach ($needles as $n) {
                if (str_contains($q, $n)) {
                    return true;
                }
            }

            return false;
        };

        return match (true) {
            $has(['share', 'link to', 'my link', 'web address', ' url', 'send the invit', 'send my site', 'send out the invit']) => 'To share your site, first publish it (Website → flip **Publish**, an Atelier feature). Then share your link: your free address **your-names.vownook.com** (claim it under Website → “Your web address”), or the **/w/your-names** link. Both are shown right on the Website page — copy either and send it to your guests.',

            $has(['publish', 'go live', 'make it live', 'make my site live']) => 'Open **Website**, finish your content, then turn on the **Publish** toggle (an Atelier feature). Once published, guests can view your site and RSVP.',

            $has(['add guest', 'add a guest', 'invite guest', 'import guest', 'guest list', 'upload guest']) => 'Open **Guests** from the sidebar, then click **Add guest** (or import a CSV). You can group guests into households and track their RSVPs from the same page.',

            $has(['rsvp', 'collect response', 'collect rsvp', 'who is coming', 'who’s coming']) => 'Publish your wedding website — guests RSVP on its **/rsvp** page. You can set meal options for them to choose from, and send reminders to anyone who hasn’t replied from the **Guests** page.',

            $has(['upgrade', 'atelier', 'how much', 'pricing', 'price', 'subscription', 'cost to', 'free tier', 'free plan']) => 'Open **Settings → Plan** to upgrade to **Atelier**. It unlocks publishing your website, the seating/floor-plan studio, the gift & cash registry, multiple events, save-the-dates, a custom web address, the AI assistant, and more.',

            $has(['collaborat', 'add my partner', 'invite my partner', 'invite my planner', 'add my planner', 'add my family', 'my team', 'add someone']) => 'Open **Collaborators**, enter the person’s email, and choose exactly what they can access (guests, budget, website, and so on). They’ll get an email invite to join your wedding.',

            $has(['seating', 'floor plan', 'seat chart', 'table plan', 'assign seat', 'seating chart']) => 'Use the **Floor plan** tool (an Atelier feature) to lay out your tables and assign guests to seats. On a phone you can tap a guest then tap a seat; full drag-edit is on desktop.',

            $has(['vendor', 'quote', 'marketplace', 'photographer', 'florist', 'caterer', 'book a ']) => 'Open **Marketplace**, browse by category and city, open a vendor and click **Request a quote**. Compare the offers under **Vendors → My quotes**, then book — deposits are paid securely via Stripe.',

            $has(['registry', 'cash fund', 'honeymoon fund', 'gift list', 'wedding gift']) => 'Open **Registry** to add cash funds (a honeymoon fund, for example) and gift items. Guests can contribute securely right from your website.',

            default => null,
        };
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
        You are the VowNook Help Assistant — a warm, concise support helper inside VowNook,
        a free wedding-planning studio and Ontario (Canada) wedding-vendor marketplace.

        Answer ONLY questions about using VowNook, in plain, friendly language. Keep it short.
        Never invent features. If you are unsure, or the question is about a specific account,
        a charge/refund, or a possible bug, set confident=false and gently suggest the person
        send a support request using the form on this page.

        WHAT VOWNOOK DOES
        - Couples plan their wedding for free: guest list, budget, checklist, timeline,
          inspiration board, photo gallery, and a wedding website.
        - A marketplace of trusted Ontario vendors: browse by category/city, request quotes,
          compare offers, and book. Deposits/balances are paid securely via Stripe.

        PLANS (couples)
        - Free: guest list (up to 25 guests), budget, checklist, timeline, inspiration,
          gallery (up to 15 photos), and building a wedding website as a draft.
        - Atelier (one-time upgrade): everything free, plus PUBLISHING the website, the seating/
          floor-plan studio, gift & cash registry, multiple events/itinerary, travel & hotel
          blocks, guest broadcasts, save-the-dates & invitations, a custom name.vownook.com
          address, the AI planning assistant, up to 500 guests, and up to 10 collaborators.
        - Planner HQ (for wedding planners): unlimited weddings and all features.
        Note: an admin may unlock some Atelier tools for free accounts as a promotion.

        COMMON HOW-TOs
        - Add guests: open "Guests" from the sidebar, then "Add guest".
        - Collect RSVPs: publish the wedding website; guests RSVP at its /rsvp page. You can set
          meal options the guests choose from.
        - Publish the website: open "Website", finish the content, then toggle Publish (Atelier).
        - Invite a partner/planner/family to help: "Collaborators", choose what each can access.
        - Change plan / upgrade: Settings → Plan.
        - Find & book vendors: "Marketplace" → open a vendor → "Request a quote".
        - Seating chart: the "Floor plan" tool (Atelier).

        Always be encouraging and brief. Prefer steps the person can follow right now.
        PROMPT;
    }
}

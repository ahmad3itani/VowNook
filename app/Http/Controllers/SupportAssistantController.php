<?php

namespace App\Http\Controllers;

use App\Support\Ai\AiException;
use App\Support\Ai\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        if (! $ai->isConfigured()) {
            return response()->json(['available' => false]);
        }

        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

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
        }

        return response()->json([
            'available' => true,
            'answer' => (string) ($result['answer'] ?? ''),
            'confident' => (bool) ($result['confident'] ?? false),
        ]);
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

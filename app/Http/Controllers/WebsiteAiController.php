<?php

namespace App\Http\Controllers;

use App\Models\Wedding;
use App\Models\WeddingWebsite;
use App\Support\Ai\AiException;
use App\Support\Ai\AiService;
use App\Support\CurrentWedding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * AI-fill for the wedding website: drafts the welcome, "our story", FAQ, local
 * guide, and wedding-party bios from the couple's own details. The route is
 * gated to paid plans (plan.feature:ai); the couple edits the draft before it
 * ever publishes. Degrades gracefully when AI isn't configured.
 */
class WebsiteAiController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function generate(Request $request, AiService $ai): JsonResponse
    {
        if (! $ai->isConfigured()) {
            return response()->json(['available' => false]);
        }

        $data = $request->validate([
            'section' => ['required', Rule::in(['welcome', 'story', 'faq', 'local', 'party_bio'])],
            'name' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:80'],
        ]);

        $wedding = $this->current->get();
        $context = $this->context($wedding, $wedding->website, $data);

        try {
            return response()->json(['available' => true] + match ($data['section']) {
                'welcome' => $this->text($ai, 'a warm, elegant 2-3 sentence welcome message for the wedding website home page', $context),
                'story' => $this->text($ai, 'a heartfelt "our story" in 2 short paragraphs (how they met and the proposal). Invent gentle, generic romantic details the couple can edit', $context),
                'party_bio' => $this->text($ai, 'a friendly 1-2 sentence bio for this wedding-party member', $context),
                'faq' => $this->faq($ai, $context),
                'local' => $this->local($ai, $context),
            });
        } catch (AiException $e) {
            return response()->json(['available' => true, 'error' => $e->getMessage()]);
        }
    }

    /** @return array{content:string} */
    private function text(AiService $ai, string $ask, string $context): array
    {
        $result = $ai->generateStructured(
            "You write warm, tasteful wedding-website copy. {$context} Keep it elegant and concise; no emojis.",
            "Write {$ask}.",
            [
                'name' => 'write_copy',
                'description' => 'Return the drafted copy.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => ['content' => ['type' => 'string', 'description' => 'The drafted text.']],
                    'required' => ['content'],
                ],
            ],
        );

        return ['content' => (string) ($result['content'] ?? '')];
    }

    /** @return array{items:array<int,array{question:string,answer:string}>} */
    private function faq(AiService $ai, string $context): array
    {
        $result = $ai->generateStructured(
            "You draft a wedding-website FAQ. {$context}",
            'Draft 6-8 common guest FAQs with helpful default answers (dress code, timing, kids, parking, plus-ones, RSVP, gifts, accommodation). The couple will edit specifics.',
            [
                'name' => 'write_faq',
                'description' => 'Return the FAQ list.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'question' => ['type' => 'string'],
                                    'answer' => ['type' => 'string'],
                                ],
                                'required' => ['question', 'answer'],
                            ],
                        ],
                    ],
                    'required' => ['items'],
                ],
            ],
        );

        return ['items' => array_values((array) ($result['items'] ?? []))];
    }

    /** @return array{items:array<int,array{title:string,category:string,description:string}>} */
    private function local(AiService $ai, string $context): array
    {
        $result = $ai->generateStructured(
            "You suggest things for out-of-town wedding guests to do. {$context}",
            'Suggest 5-6 real-ish recommendations near the venue city (restaurants, attractions, activities) with a category and a one-line description. If the city is unknown, suggest generic categories the couple can fill.',
            [
                'name' => 'write_local',
                'description' => 'Return the local recommendations.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'category' => ['type' => 'string'],
                                    'description' => ['type' => 'string'],
                                ],
                                'required' => ['title', 'category', 'description'],
                            ],
                        ],
                    ],
                    'required' => ['items'],
                ],
            ],
        );

        return ['items' => array_values((array) ($result['items'] ?? []))];
    }

    private function context(Wedding $wedding, ?WeddingWebsite $website, array $data): string
    {
        $parts = ["The couple is {$wedding->name}."];

        if ($wedding->event_date) {
            $parts[] = 'The wedding date is '.$wedding->event_date->format('F j, Y').'.';
        }
        if ($website?->venue_name) {
            $parts[] = "The venue is {$website->venue_name}".($website->venue_address ? " ({$website->venue_address})" : '').'.';
        }
        if (($data['section'] ?? null) === 'party_bio' && ! empty($data['name'])) {
            $parts[] = "The person is {$data['name']}".(! empty($data['role']) ? ", the {$data['role']}" : '').'.';
        }

        return implode(' ', $parts);
    }
}

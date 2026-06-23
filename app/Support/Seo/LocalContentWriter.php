<?php

namespace App\Support\Seo;

use App\Console\Commands\GenerateLocalContent;
use App\Support\Ai\AiException;
use App\Support\Ai\AiService;
use Illuminate\Support\Collection;

/**
 * Generates unique local-guide copy + FAQs for one programmatic local page,
 * so an otherwise-thin listing page becomes a genuinely useful local resource.
 * Stored once by {@see GenerateLocalContent}.
 */
class LocalContentWriter
{
    public function __construct(protected AiService $ai) {}

    /**
     * @return array{intro:string, faqs:array<int, array{question:string, answer:string}>}|null
     */
    public function write(string $categoryNoun, ?string $cityName): ?array
    {
        $place = $cityName !== null ? "{$cityName}, Ontario" : 'Ontario';

        $tool = [
            'name' => 'local_guide',
            'description' => 'Return unique local-guide copy and FAQs for a wedding-vendor category in a place.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'intro_markdown' => ['type' => 'string', 'description' => '2-3 short paragraphs of genuinely useful, specific guidance for couples looking for this vendor type here: what to know, typical CAD price ranges, local or seasonal notes. Markdown, NO headings. ~120-200 words.'],
                    'faqs' => [
                        'type' => 'array',
                        'description' => '3-4 frequently asked questions with concise, honest answers.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'question' => ['type' => 'string'],
                                'answer' => ['type' => 'string', 'description' => '1-3 sentences, specific and honest.'],
                            ],
                            'required' => ['question', 'answer'],
                        ],
                    ],
                ],
                'required' => ['intro_markdown', 'faqs'],
            ],
        ];

        $system = implode(' ', [
            'You write concise, genuinely useful local-guide copy for VowNook, a free Ontario wedding marketplace.',
            'Be specific and honest; use Canadian spelling and CAD. NEVER invent statistics, awards, or named businesses, and frame any price as a typical range.',
            'Do not mention that you are an AI and do not repeat the page title verbatim.',
        ]);

        try {
            $result = $this->ai->generateStructured($system, "Write the guide for: {$categoryNoun} in {$place}.", $tool, 30);
        } catch (AiException) {
            return null;
        }

        $intro = trim((string) ($result['intro_markdown'] ?? ''));

        if ($intro === '') {
            return null;
        }

        $faqs = Collection::make($result['faqs'] ?? [])
            ->map(fn ($f) => [
                'question' => trim((string) ($f['question'] ?? '')),
                'answer' => trim((string) ($f['answer'] ?? '')),
            ])
            ->filter(fn ($f) => $f['question'] !== '' && $f['answer'] !== '')
            ->take(6)
            ->values()
            ->all();

        return ['intro' => $intro, 'faqs' => $faqs];
    }
}

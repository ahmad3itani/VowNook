<?php

namespace App\Support\Blog;

use App\Support\Ai\AiException;
use App\Support\Ai\AiService;

/**
 * Turns a topic from the {@see BlogTopics} queue into a complete, publish-ready
 * article via the LLM. Enforces a length/quality floor so the autopilot never
 * publishes thin content (which is what Google's scaled-content policy punishes).
 */
class AiBlogWriter
{
    public function __construct(protected AiService $ai) {}

    /**
     * @param  array{slug:string, title:string, category:string, brief:string, cluster?:string}  $topic
     * @param  list<array{title:string, url:string}>  $related  Already-published cluster posts to link to (hub & spoke internal linking).
     * @return array{title:string, excerpt:?string, body:string, meta_description:?string}|null Null when generation fails or the draft is too thin.
     */
    public function write(array $topic, array $related = []): ?array
    {
        $tool = [
            'name' => 'write_article',
            'description' => 'Return one complete, publish-ready blog article.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string', 'description' => 'Final SEO title, ~45-65 characters, keyword-first.'],
                    'excerpt' => ['type' => 'string', 'description' => 'A 1-2 sentence summary, ~140-180 characters.'],
                    'body_markdown' => ['type' => 'string', 'description' => 'The full article in GitHub-flavored markdown: an answer-first opening paragraph, then 4-6 "## " sections (bullet lists allowed). 600-900 words. No top-level "# " heading.'],
                    'meta_description' => ['type' => 'string', 'description' => 'Search-result meta description, max 160 characters.'],
                ],
                'required' => ['title', 'excerpt', 'body_markdown', 'meta_description'],
            ],
        ];

        $prompt = "Write the article.\nWorking title: {$topic['title']}\nWhat to cover: {$topic['brief']}";

        if ($related !== []) {
            $links = collect($related)
                ->take(5)
                ->map(fn ($r) => "- [{$r['title']}]({$r['url']})")
                ->implode("\n");
            $prompt .= "\n\nWhere it genuinely helps the reader, link to 2-3 of these related "
                .'VowNook articles using markdown links — only the most relevant, placed naturally '
                ."in the body (never a list at the end):\n{$links}";
        }

        try {
            $result = $this->ai->generateStructured($this->system(), $prompt, $tool, 45);
        } catch (AiException) {
            return null;
        }

        $title = trim((string) ($result['title'] ?? $topic['title']));
        $body = trim((string) ($result['body_markdown'] ?? ''));

        // Quality floor: never publish empty or thin content.
        if ($title === '' || str_word_count(strip_tags($body)) < (int) config('ai.blog_autopilot.min_words')) {
            return null;
        }

        $excerpt = trim((string) ($result['excerpt'] ?? ''));
        $metaDescription = trim((string) ($result['meta_description'] ?? ''));

        return [
            'title' => $title,
            'excerpt' => $excerpt !== '' ? $excerpt : null,
            'body' => $body,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
        ];
    }

    protected function system(): string
    {
        return implode(' ', [
            'You are the content writer for VowNook, a free Ontario wedding-planning studio and a curated marketplace of trusted Ontario wedding vendors.',
            'Write a genuinely useful, original article for engaged couples planning a wedding in Ontario, Canada.',
            'Voice: warm, editorial, practical, specific — never salesy, padded, or generic. Use Canadian spelling and dollars (CAD).',
            'Structure: open with a direct, answer-first paragraph (no "planning a wedding is exciting" preamble), then 4-6 short "## " sections; bullet lists are welcome. Do NOT include a top-level "# " heading — the title is rendered separately.',
            'Length: 600-900 words, concrete and specific to Ontario (seasons, cities, realistic price ranges).',
            'E-E-A-T and honesty: be accurate; NEVER invent statistics, studies, awards, surveys, or quotes. Frame any figure as a typical range, not a hard fact.',
            'Where it genuinely helps, link naturally to the free [planning workspace](/dashboard) and to [Ontario wedding vendors](/marketplace) — at most a couple of links, never stuffed.',
            'Never mention that you are an AI and never discuss SEO or meta tags inside the article body.',
        ]);
    }
}

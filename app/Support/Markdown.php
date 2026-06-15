<?php

namespace App\Support;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Renders trusted markdown (admin-authored blog posts) to safe HTML. Raw HTML
 * in the source is stripped, so the output is XSS-safe even though the author
 * is trusted.
 */
class Markdown
{
    public static function toHtml(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = (string) $converter->convert($markdown);

        // Make body images SEO/CWV-friendly: native lazy-loading, async decode,
        // and a responsive class (CLS-safe via height:auto in the prose styles).
        return preg_replace(
            '/<img(?![^>]*\bloading=)/i',
            '<img loading="lazy" decoding="async" class="prose-img"',
            $html,
        ) ?? $html;
    }
}

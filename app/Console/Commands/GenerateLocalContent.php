<?php

namespace App\Console\Commands;

use App\Enums\VendorCategory;
use App\Models\LocalContent;
use App\Support\Ai\AiService;
use App\Support\MarketplaceCatalog;
use App\Support\OntarioCities;
use App\Support\Seo\LocalContentWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Fills the programmatic local pages with stored, unique AI guide copy + FAQs:
 * every Ontario category hub, plus the city x category pages that clear the
 * vendor gate (so we never spend tokens on thin, noindex'd pages). De-dupes
 * against already-filled pages. Off unless LOCAL_SEO_AUTOFILL_ENABLED=true.
 */
class GenerateLocalContent extends Command
{
    protected $signature = 'seo:generate-local
        {--force : Run even when LOCAL_SEO_AUTOFILL_ENABLED is off}
        {--limit= : Override how many pages to fill this run}';

    protected $description = 'Generate stored unique local-guide copy + FAQs for the programmatic Ontario pages.';

    /** Mirrors PublicLocalController — only index/fill city pages with enough vendors. */
    private const INDEX_THRESHOLD = 3;

    public function handle(AiService $ai, LocalContentWriter $writer, MarketplaceCatalog $catalog): int
    {
        if (! config('ai.local_seo.enabled') && ! $this->option('force')) {
            $this->info('Local-SEO autofill is off (set LOCAL_SEO_AUTOFILL_ENABLED=true). Skipping.');

            return self::SUCCESS;
        }

        if (! $ai->isConfigured()) {
            $this->warn('AI is not configured — skipping local-SEO autofill.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) ($this->option('limit') ?: config('ai.local_seo.per_run')));

        $existing = LocalContent::all(['category', 'city_slug'])
            ->map(fn (LocalContent $c) => $c->category.'|'.($c->city_slug ?? ''))
            ->all();

        $queue = $this->buildQueue($catalog, $existing)->take($limit);

        if ($queue->isEmpty()) {
            $this->info('Nothing to fill — all eligible local pages already have content.');

            return self::SUCCESS;
        }

        $filled = 0;

        foreach ($queue as $page) {
            /** @var VendorCategory $cat */
            $cat = $page['category'];
            $content = $writer->write($cat->seoNoun(), $page['city_name']);

            if ($content === null) {
                $this->warn("Skipped (generation failed): {$cat->value}/".($page['city_slug'] ?? 'hub'));

                continue;
            }

            LocalContent::create([
                'category' => $cat->value,
                'city_slug' => $page['city_slug'],
                'intro' => $content['intro'],
                'faqs' => $content['faqs'],
            ]);

            $filled++;
            $this->info('Filled: '.($page['city_name'] ? "{$cat->value} in {$page['city_name']}" : "{$cat->value} (Ontario hub)"));
        }

        $this->info("Local-SEO autofill finished — {$filled} page(s) filled.");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $existing  Keys "category|city_slug" already filled.
     * @return Collection<int, array{category:VendorCategory, city_slug:?string, city_name:?string}>
     */
    private function buildQueue(MarketplaceCatalog $catalog, array $existing): Collection
    {
        $queue = Collection::make();

        foreach (VendorCategory::seoCases() as $cat) {
            // The Ontario hub is always indexable, so always eligible.
            if (! in_array($cat->value.'|', $existing, true)) {
                $queue->push(['category' => $cat, 'city_slug' => null, 'city_name' => null]);
            }

            // City pages only once they clear the vendor gate.
            foreach (OntarioCities::all() as $slug => $data) {
                if (in_array($cat->value.'|'.$slug, $existing, true)) {
                    continue;
                }

                $count = $catalog->browse(['category' => $cat->value, 'city' => $data['name']])->count();

                if ($count >= self::INDEX_THRESHOLD) {
                    $queue->push(['category' => $cat, 'city_slug' => $slug, 'city_name' => $data['name']]);
                }
            }
        }

        return $queue;
    }
}

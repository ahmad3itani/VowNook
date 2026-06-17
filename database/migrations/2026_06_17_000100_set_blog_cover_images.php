<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Attaches the AI-generated cover images (committed under public/images/blog)
 * to the seeded starter articles. Idempotent by slug; safe on prod where the
 * seeder doesn't re-run.
 */
return new class extends Migration
{
    /** @var array<string, array{0:string, 1:string}> */
    private array $covers = [
        'how-much-does-a-wedding-cost-in-ontario' => [
            'images/blog/cost.webp',
            'An elegant Ontario wedding reception with beautifully set tables and soft daylight',
        ],
        'ontario-wedding-planning-timeline' => [
            'images/blog/timeline.webp',
            'Wedding planning essentials on a marble desk — a planner, fresh flowers, swatches and a ring box',
        ],
        'questions-to-ask-wedding-venue' => [
            'images/blog/venue.webp',
            'A stunning Ontario wedding venue at golden hour with an outdoor ceremony aisle lined with florals',
        ],
        'how-to-choose-a-wedding-photographer-ontario' => [
            'images/blog/photographer.webp',
            'A wedding photographer capturing a couple outdoors in warm autumn Ontario light',
        ],
    ];

    public function up(): void
    {
        foreach ($this->covers as $slug => [$path, $alt]) {
            DB::table('blog_posts')->where('slug', $slug)->update([
                'cover_image_path' => $path,
                'cover_alt' => $alt,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('blog_posts')
            ->whereIn('slug', array_keys($this->covers))
            ->update(['cover_image_path' => null, 'cover_alt' => null]);
    }
};

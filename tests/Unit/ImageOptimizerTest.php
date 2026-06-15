<?php

namespace Tests\Unit;

use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    public function test_large_images_are_scaled_down_to_the_max_edge(): void
    {
        Storage::fake();

        $upload = UploadedFile::fake()->image('big.jpg', 4000, 3000);

        $path = ImageOptimizer::store($upload, 'test-uploads', 2000);

        Storage::assertExists($path);

        [$width, $height] = getimagesizefromstring(Storage::get($path));
        $this->assertSame(2000, max($width, $height));
        // Re-encoded to WebP for a smaller payload (CWV win).
        $this->assertStringEndsWith('.webp', $path);
    }

    public function test_store_with_meta_returns_dimensions(): void
    {
        Storage::fake();

        $meta = ImageOptimizer::storeWithMeta(
            UploadedFile::fake()->image('big.jpg', 4000, 3000),
            'test-uploads',
            2000,
        );

        $this->assertSame(2000, $meta['width']);
        $this->assertSame(1500, $meta['height']);
        Storage::assertExists($meta['path']);
        $this->assertSame([2000, 1500], ImageOptimizer::dimensions($meta['path']));
    }

    public function test_small_images_keep_their_dimensions(): void
    {
        Storage::fake();

        $upload = UploadedFile::fake()->image('small.jpg', 600, 400);

        $path = ImageOptimizer::store($upload, 'test-uploads', 2000);

        [$width, $height] = getimagesizefromstring(Storage::get($path));
        $this->assertSame(600, $width);
        $this->assertSame(400, $height);
    }

    public function test_png_uploads_stay_png(): void
    {
        Storage::fake();

        $upload = UploadedFile::fake()->image('logo.png', 1200, 1200);

        $path = ImageOptimizer::store($upload, 'test-uploads', 800);

        // PNGs are re-encoded to WebP too (which preserves transparency).
        $this->assertStringEndsWith('.webp', $path);

        [$width] = getimagesizefromstring(Storage::get($path));
        $this->assertSame(800, $width);
    }
}

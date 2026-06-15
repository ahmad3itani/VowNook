<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorMedia;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorPortfolioTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: VendorProfile} */
    protected function vendor(): array
    {
        $user = User::factory()->create(['account_type' => 'vendor']);
        $profile = VendorProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    public function test_vendor_can_upload_multiple_photos_at_once(): void
    {
        Storage::fake();
        [$user, $profile] = $this->vendor();

        $this->actingAs($user)->post('/vendor/profile/media', [
            'photos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ],
        ])->assertRedirect();

        $this->assertSame(3, $profile->media()->count());
        $this->assertSame([1, 2, 3], $profile->media()->orderBy('sort_order')->pluck('sort_order')->all());
    }

    public function test_vendor_can_set_alt_text_and_reorder(): void
    {
        [$user, $profile] = $this->vendor();
        $a = VendorMedia::create(['vendor_profile_id' => $profile->id, 'path' => 'p/a.webp', 'original_name' => 'a', 'mime' => 'image/webp', 'size' => 1, 'sort_order' => 1]);
        $b = VendorMedia::create(['vendor_profile_id' => $profile->id, 'path' => 'p/b.webp', 'original_name' => 'b', 'mime' => 'image/webp', 'size' => 1, 'sort_order' => 2]);

        $this->actingAs($user)->put("/vendor/profile/media/{$a->id}", ['alt_text' => 'A sunset ceremony'])->assertRedirect();
        $this->assertSame('A sunset ceremony', $a->fresh()->alt_text);

        $this->actingAs($user)->post('/vendor/profile/media/reorder', [
            'items' => [['id' => $b->id, 'sort_order' => 1], ['id' => $a->id, 'sort_order' => 2]],
        ])->assertRedirect();
        $this->assertSame(1, $b->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
    }

    public function test_a_vendor_cannot_edit_another_vendors_media(): void
    {
        [$user] = $this->vendor();
        [, $other] = $this->vendor();
        $media = VendorMedia::create(['vendor_profile_id' => $other->id, 'path' => 'p/x.webp', 'original_name' => 'x', 'mime' => 'image/webp', 'size' => 1, 'sort_order' => 1]);

        $this->actingAs($user)->put("/vendor/profile/media/{$media->id}", ['alt_text' => 'hax'])->assertNotFound();
    }

    public function test_vendor_can_upload_a_pdf_brochure_but_not_other_files(): void
    {
        Storage::fake();
        [$user, $profile] = $this->vendor();

        $this->actingAs($user)->post('/vendor/profile/brochure', [
            'brochure' => UploadedFile::fake()->create('packages.pdf', 200, 'application/pdf'),
        ])->assertRedirect();
        $this->assertNotNull($profile->fresh()->brochure_path);

        $this->actingAs($user)->post('/vendor/profile/brochure', [
            'brochure' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('brochure');
    }

    public function test_vendor_can_save_a_portfolio_video_url(): void
    {
        [$user, $profile] = $this->vendor();

        $this->actingAs($user)->put('/vendor/profile', [
            'business_name' => $profile->business_name,
            'category' => $profile->category->value,
            'video_url' => 'https://www.youtube.com/watch?v=abc123',
        ])->assertRedirect();

        $this->assertSame('https://www.youtube.com/watch?v=abc123', $profile->fresh()->video_url);
    }

    public function test_planner_can_create_a_public_listing_and_reach_the_editor(): void
    {
        $planner = User::factory()->create(['account_type' => 'planner']);

        $this->actingAs($planner)->post('/planner/listing')->assertRedirect('/vendor/profile');

        $profile = $planner->fresh()->vendorProfile;
        $this->assertNotNull($profile);
        $this->assertSame('planner', $profile->category->value);

        // The planner can now use the vendor profile editor.
        $this->actingAs($planner->fresh())->get('/vendor/profile')->assertOk();
    }
}

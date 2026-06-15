<?php

namespace Tests\Feature;

use App\Enums\InquiryStatus;
use App\Enums\OfferStatus;
use App\Enums\VendorProfileStatus;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_a_complete_demo_dataset(): void
    {
        $this->seed(DemoSeeder::class);

        // The demo couple and their wedding workspace.
        $couple = User::where('email', 'demo@example.com')->first();
        $this->assertNotNull($couple);
        $this->assertNotNull($couple->current_wedding_id);
        $this->assertSame(20, $couple->currentWedding->guests()->count());

        // Six published vendors, each with services.
        $this->assertSame(6, VendorProfile::query()->count());
        $this->assertSame(6, VendorProfile::query()->published()->count());
        $this->assertTrue(VendorService::query()->count() >= 12);

        // Two inquiries — one with a sent offer.
        $this->assertSame(2, Inquiry::query()->count());
        $this->assertSame(1, Inquiry::where('status', InquiryStatus::Offered->value)->count());
        $this->assertSame(1, Offer::where('status', OfferStatus::Sent->value)->count());
    }
}

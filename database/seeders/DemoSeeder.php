<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\VendorCategory;
use App\Models\BudgetItem;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\Task;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\VendorService;
use App\Models\Wedding;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Factory-driven demo dataset for the marketplace. Not wired into
 * DatabaseSeeder — run explicitly with:
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $wedding = $this->seedCouple();
        $vendors = $this->seedVendors();
        $this->seedInquiries($wedding, $vendors);
    }

    /** A demo couple (demo@example.com / "password") with a wedding workspace, guests, budget, and checklist. */
    protected function seedCouple(): Wedding
    {
        $couple = User::factory()->create([
            'name' => 'Demo Couple',
            'email' => 'demo@example.com',
        ]);

        $wedding = Wedding::factory()->create([
            'owner_id' => $couple->id,
            'name' => 'Demi & Devon',
        ]);

        $wedding->members()->attach($couple->id, [
            'role' => Role::Owner->value,
            'accepted_at' => now(),
        ]);
        $couple->forceFill(['current_wedding_id' => $wedding->id])->save();

        Guest::factory()->count(20)->create(['wedding_id' => $wedding->id]);
        BudgetItem::factory()->count(5)->create(['wedding_id' => $wedding->id]);
        Task::factory()->count(6)->create(['wedding_id' => $wedding->id]);

        return $wedding;
    }

    /**
     * Six published marketplace vendors across categories, each with services.
     *
     * @return \Illuminate\Support\Collection<int, VendorProfile>
     */
    protected function seedVendors()
    {
        $categories = [
            VendorCategory::Venue,
            VendorCategory::Catering,
            VendorCategory::Photography,
            VendorCategory::Florist,
            VendorCategory::Music,
            VendorCategory::Bakery,
        ];

        return collect($categories)->map(function (VendorCategory $category) {
            $profile = VendorProfile::factory()->published()->create([
                'category' => $category,
                // Most demo vendors show a "responds in ~Xh" badge.
                'response_hours' => fake()->optional(0.7)->numberBetween(1, 24),
                'response_count' => fake()->numberBetween(3, 18),
            ]);

            VendorService::factory()
                ->count(fake()->numberBetween(2, 3))
                ->sequence(fn ($sequence) => ['sort_order' => $sequence->index])
                ->create(['vendor_profile_id' => $profile->id]);

            return $profile;
        });
    }

    /** Two inquiries from the demo couple — one already has a sent offer. */
    protected function seedInquiries(Wedding $wedding, $vendors): void
    {
        $base = [
            'wedding_id' => $wedding->id,
            'couple_user_id' => $wedding->owner_id,
            'event_date' => $wedding->event_date,
        ];

        // Awaiting a response from the photographer.
        Inquiry::factory()->create($base + [
            'vendor_profile_id' => $vendors->firstWhere('category', VendorCategory::Photography)->id,
        ]);

        // The florist already sent an offer.
        $offered = Inquiry::factory()->offered()->create($base + [
            'vendor_profile_id' => $vendors->firstWhere('category', VendorCategory::Florist)->id,
        ]);

        Offer::factory()->create(['inquiry_id' => $offered->id]);
    }
}

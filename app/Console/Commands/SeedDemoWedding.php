<?php

namespace App\Console\Commands;

use App\Enums\EventType;
use App\Enums\Role;
use App\Models\GuestbookEntry;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingPartyMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Creates (or purges) the public demo wedding site linked from the homepage
 * ("See a live wedding site" → /w/amelia-and-julian). The full demo lives in
 * DatabaseSeeder, which only runs locally — so on production that link 404s
 * until this idempotent, production-safe command is run once:
 *
 *     php artisan demo:wedding
 *
 * No factories/Faker (dev-only), safe to re-run, and everything hangs off a
 * reserved demo-domain owner so it can be removed cleanly with --purge.
 */
class SeedDemoWedding extends Command
{
    protected $signature = 'demo:wedding
        {--purge : Remove the demo wedding instead of creating it}';

    protected $description = 'Create (or purge) the public demo wedding shown at /w/amelia-and-julian.';

    private const SLUG = 'amelia-and-julian';

    private const OWNER_EMAIL = 'amelia-demo@demo.vownook.test';

    public function handle(): int
    {
        if ($this->option('purge')) {
            return $this->purge();
        }

        $wedding = Wedding::where('slug', self::SLUG)->first();

        if ($wedding === null) {
            $owner = User::firstOrCreate(
                ['email' => self::OWNER_EMAIL],
                ['name' => 'Amelia Hart', 'password' => 'demo-not-loginable', 'account_type' => 'couple'],
            );
            $owner->forceFill(['email_verified_at' => now()])->save();

            $wedding = Wedding::create([
                'owner_id' => $owner->id,
                'name' => 'Amelia & Julian',
                'slug' => self::SLUG,
                'event_date' => now()->addMonths(8)->toDateString(),
            ]);

            $wedding->members()->attach($owner->id, ['role' => Role::Owner->value, 'accepted_at' => now()]);
            $owner->forceFill(['current_wedding_id' => $wedding->id])->save();
        }

        // Published website — idempotent (matches the hasOne by wedding_id).
        $wedding->website()->updateOrCreate([], [
            'is_published' => true,
            'template' => 'classic',
            'headline' => 'Together with their families',
            'welcome_message' => 'We are so excited to celebrate our wedding day with the people we love most. Thank you for being part of our story.',
            'our_story' => "We met one rainy autumn evening and have been inseparable ever since. After eight wonderful years, a hike, and one very nervous proposal at the lookout, we can't wait to say \"I do\".",
            'venue_name' => 'Rosewood Estate',
            'venue_address' => '1200 Orchard Lane, Mont-Tremblant, QC',
            'ceremony_time' => '4:00 PM',
            'dress_code' => 'Garden formal',
            'travel_notes' => 'The Rosewood Estate is about 90 minutes north of Montréal. We recommend arriving the afternoon before — the drive through the hills is beautiful.',
            'show_travel_stays' => true,
            'faq_items' => [
                ['question' => 'What time should I arrive?', 'answer' => 'Please arrive by 3:30 PM so we can begin the ceremony promptly at 4:00 PM.'],
                ['question' => 'Is there a dress code?', 'answer' => 'Garden formal — think florals, linens and comfortable shoes for the lawn.'],
                ['question' => 'Can I bring a plus-one?', 'answer' => 'Your invitation will note the number of seats reserved for you. Please RSVP for everyone in your party.'],
            ],
        ]);

        $this->seedSchedule($wedding);
        $this->seedParty($wedding);
        $this->seedGuestbook($wedding);

        $this->info('Demo wedding ready — visit /w/'.self::SLUG);

        return self::SUCCESS;
    }

    /** The day-of "Order of the Day" schedule (only if none exist yet). */
    private function seedSchedule(Wedding $wedding): void
    {
        if (TimelineEvent::where('wedding_id', $wedding->id)->exists()) {
            return;
        }

        $day = $wedding->event_date?->copy() ?? now()->addMonths(8);

        // [title, type, hour, minute, durationMinutes (null = no end)]
        $events = [
            ['Ceremony', EventType::Ceremony, 16, 0, 45],
            ['Cocktail hour', EventType::Cocktails, 17, 0, 60],
            ['Reception & dinner', EventType::Reception, 18, 0, 120],
            ['First dance & party', EventType::Party, 20, 30, null],
        ];

        foreach ($events as [$title, $type, $hour, $minute, $duration]) {
            $start = $day->copy()->setTime($hour, $minute);
            TimelineEvent::create([
                'wedding_id' => $wedding->id,
                'title' => $title,
                'type' => $type,
                'starts_at' => $start,
                'ends_at' => $duration !== null ? $start->copy()->addMinutes($duration) : null,
                'location' => 'Rosewood Estate',
            ]);
        }
    }

    /** A small wedding party so the public "party" section isn't empty. */
    private function seedParty(Wedding $wedding): void
    {
        if (WeddingPartyMember::where('wedding_id', $wedding->id)->exists()) {
            return;
        }

        // [name, role, side, bio]
        $party = [
            ['Sophie Tremblay', 'Maid of Honour', 'partner_a', 'Amelia’s sister and lifelong partner in crime.'],
            ['Olivia Gagnon', 'Bridesmaid', 'partner_a', 'College roommate and keeper of every good secret.'],
            ['Daniel Roy', 'Best Man', 'partner_b', 'Julian’s brother, chief speech-giver and ring guardian.'],
            ['Liam Bouchard', 'Groomsman', 'partner_b', 'Julian’s oldest friend from the hockey days.'],
        ];

        foreach ($party as $i => [$name, $role, $side, $bio]) {
            WeddingPartyMember::create([
                'wedding_id' => $wedding->id,
                'name' => $name,
                'role' => $role,
                'side' => $side,
                'bio' => $bio,
                'sort_order' => $i,
            ]);
        }
    }

    /** One approved well-wish so the guestbook shows a real message. */
    private function seedGuestbook(Wedding $wedding): void
    {
        if (GuestbookEntry::where('wedding_id', $wedding->id)->exists()) {
            return;
        }

        GuestbookEntry::create([
            'wedding_id' => $wedding->id,
            'name' => 'Eleanor Hart',
            'message' => 'So overjoyed for you both. We have watched your love grow and cannot wait to celebrate this day with you. All our love.',
            'approved_at' => now(),
        ]);
    }

    private function purge(): int
    {
        $wedding = Wedding::where('slug', self::SLUG)->first();

        DB::transaction(function () use ($wedding) {
            if ($wedding !== null) {
                // Cascade the public content, then the wedding itself.
                TimelineEvent::where('wedding_id', $wedding->id)->delete();
                WeddingPartyMember::where('wedding_id', $wedding->id)->delete();
                GuestbookEntry::where('wedding_id', $wedding->id)->delete();
                $wedding->website()->delete();
                $wedding->forceDelete();
            }

            // Only remove the reserved demo owner, never a real account.
            User::where('email', self::OWNER_EMAIL)->delete();
        });

        $this->info('Demo wedding purged.');

        return self::SUCCESS;
    }
}

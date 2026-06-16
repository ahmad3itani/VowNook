<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingInvitation;
use App\Notifications\WeddingInvitationNotification;
use App\Support\CollaboratorAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CollaboratorInvitationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    protected function ownerWithWedding(array $userAttrs = []): array
    {
        $owner = User::factory()->plan('premium')->create($userAttrs); // collaborators are a paid feature
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $wedding->members()->attach($owner->id, ['role' => Role::Owner->value, 'accepted_at' => now()]);
        $owner->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$owner, $wedding];
    }

    protected function invite(Wedding $wedding, string $email, string $role = 'collaborator', ?array $permissions = null, array $attrs = []): WeddingInvitation
    {
        return WeddingInvitation::create(array_merge([
            'wedding_id' => $wedding->id,
            'email' => $email,
            'role' => $role,
            'permissions' => $permissions,
            'invited_by' => $wedding->owner_id,
        ], $attrs));
    }

    // ── Inviting ─────────────────────────────────────────────────────────────

    public function test_inviting_emails_the_invitee(): void
    {
        Notification::fake();
        [$owner, $wedding] = $this->ownerWithWedding();

        $this->actingAs($owner)->post('/collaborators', [
            'email' => 'planner@example.com',
            'role' => 'planner',
        ])->assertRedirect();

        $this->assertDatabaseHas('wedding_invitations', [
            'wedding_id' => $wedding->id,
            'email' => 'planner@example.com',
            'role' => 'planner',
        ]);
        Notification::assertSentOnDemand(WeddingInvitationNotification::class);
    }

    public function test_existing_member_cannot_be_invited(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();
        $member = User::factory()->create(['email' => 'taken@example.com']);
        $wedding->members()->attach($member->id, ['role' => Role::Viewer->value, 'accepted_at' => now()]);

        $this->actingAs($owner)->post('/collaborators', [
            'email' => 'taken@example.com', 'role' => 'collaborator',
        ])->assertSessionHasErrors('email');
    }

    public function test_duplicate_pending_invite_is_rejected(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();
        $this->invite($wedding, 'dupe@example.com');

        $this->actingAs($owner)->post('/collaborators', [
            'email' => 'dupe@example.com', 'role' => 'collaborator',
        ])->assertSessionHasErrors('email');
    }

    public function test_plan_collaborator_limit_is_enforced(): void
    {
        // Free plan allows 1 collaborator.
        [$owner, $wedding] = $this->ownerWithWedding(['plan' => 'free']);
        $member = User::factory()->create();
        $wedding->members()->attach($member->id, ['role' => Role::Viewer->value, 'accepted_at' => now()]);

        $this->actingAs($owner)->post('/collaborators', [
            'email' => 'overflow@example.com', 'role' => 'collaborator',
        ])->assertSessionHasErrors('email');
    }

    // ── Granular access ──────────────────────────────────────────────────────

    public function test_invite_stores_only_the_sparse_override(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();
        // Collaborator default: budget=read. Set budget=write (a diff), keep guests=write (same).
        $desired = CollaboratorAccess::effective(Role::Collaborator, null);
        $desired['budget'] = 'write';

        $this->actingAs($owner)->post('/collaborators', [
            'email' => 'x@example.com', 'role' => 'collaborator', 'permissions' => $desired,
        ])->assertRedirect();

        $invitation = WeddingInvitation::where('email', 'x@example.com')->firstOrFail();
        $this->assertSame(['budget' => 'write'], $invitation->permissions); // only the diff
    }

    public function test_section_override_grants_and_revokes_access_through_middleware(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();
        $member = User::factory()->create(['current_wedding_id' => $wedding->id]);
        $wedding->members()->attach($member->id, ['role' => Role::Viewer->value, 'accepted_at' => now()]);

        // Viewer can read seating but not budget.
        $this->actingAs($member)->get('/seating')->assertOk();
        $this->actingAs($member)->get('/budget')->assertForbidden();

        // Owner flips it: grant budget (read), revoke seating.
        $desired = CollaboratorAccess::effective(Role::Viewer, null);
        $desired['budget'] = 'read';
        $desired['seating'] = 'none';

        $this->actingAs($owner)->put("/collaborators/{$member->id}", [
            'role' => 'viewer', 'permissions' => $desired,
        ])->assertRedirect();

        $this->assertDatabaseHas('wedding_user', [
            'wedding_id' => $wedding->id, 'user_id' => $member->id,
        ]);
        $override = $wedding->members()->find($member->id)->pivot->permissions;
        $this->assertSame(['budget' => 'read', 'seating' => 'none'], json_decode($override, true));

        // The override now drives EnsurePermission both ways. Re-fetch the user so
        // we don't reuse Eloquent relations cached before the update (each real
        // HTTP request is a fresh process).
        $member = $member->fresh();
        $this->actingAs($member)->get('/budget')->assertOk();
        $this->actingAs($member)->get('/seating')->assertForbidden();
    }

    // ── Accepting ────────────────────────────────────────────────────────────

    public function test_matching_user_can_accept_and_joins_with_role_and_override(): void
    {
        [, $wedding] = $this->ownerWithWedding();
        $invitation = $this->invite($wedding, 'join@example.com', 'collaborator', ['budget' => 'write']);
        $invitee = User::factory()->create(['email' => 'join@example.com', 'current_wedding_id' => null]);

        $this->actingAs($invitee)
            ->post("/invitations/{$invitation->token}/accept")
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('wedding_user', [
            'wedding_id' => $wedding->id, 'user_id' => $invitee->id, 'role' => 'collaborator',
        ]);
        $pivot = $wedding->members()->find($invitee->id)->pivot;
        $this->assertSame(['budget' => 'write'], json_decode($pivot->permissions, true));
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertSame($wedding->id, $invitee->fresh()->current_wedding_id);
    }

    public function test_cannot_accept_an_invitation_for_a_different_email(): void
    {
        [, $wedding] = $this->ownerWithWedding();
        $invitation = $this->invite($wedding, 'right@example.com');
        $other = User::factory()->create(['email' => 'wrong@example.com']);

        $this->actingAs($other)
            ->post("/invitations/{$invitation->token}/accept")
            ->assertForbidden();

        $this->assertDatabaseMissing('wedding_user', [
            'wedding_id' => $wedding->id, 'user_id' => $other->id,
        ]);
    }

    public function test_cannot_accept_an_expired_invitation(): void
    {
        [, $wedding] = $this->ownerWithWedding();
        $invitation = $this->invite($wedding, 'late@example.com', attrs: ['expires_at' => now()->subDay()]);
        $invitee = User::factory()->create(['email' => 'late@example.com']);

        $this->actingAs($invitee)
            ->post("/invitations/{$invitation->token}/accept")
            ->assertStatus(410);
    }

    public function test_invitation_landing_page_is_public(): void
    {
        [, $wedding] = $this->ownerWithWedding();
        $invitation = $this->invite($wedding, 'guest@example.com');

        $this->get("/invitations/{$invitation->token}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('invitations/accept')
                ->where('invitation.email', 'guest@example.com')
                ->where('email_matches', false));
    }

    // ── Resend / revoke ──────────────────────────────────────────────────────

    public function test_resend_regenerates_the_token(): void
    {
        Notification::fake();
        [$owner, $wedding] = $this->ownerWithWedding();
        $invitation = $this->invite($wedding, 'again@example.com');
        $original = $invitation->token;

        $this->actingAs($owner)
            ->post("/collaborators/invitations/{$invitation->id}/resend")
            ->assertRedirect();

        $this->assertNotSame($original, $invitation->fresh()->token);
        Notification::assertSentOnDemand(WeddingInvitationNotification::class);
    }

    public function test_revoke_deletes_the_invitation(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();
        $invitation = $this->invite($wedding, 'bye@example.com');

        $this->actingAs($owner)
            ->delete("/collaborators/invitations/{$invitation->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('wedding_invitations', ['id' => $invitation->id]);
    }

    public function test_cannot_revoke_an_invitation_from_another_wedding(): void
    {
        [$owner] = $this->ownerWithWedding();
        $otherOwner = User::factory()->create();
        $otherWedding = Wedding::factory()->create(['owner_id' => $otherOwner->id]);
        $foreign = $this->invite($otherWedding, 'foreign@example.com');

        $this->actingAs($owner)
            ->delete("/collaborators/invitations/{$foreign->id}")
            ->assertNotFound();
    }
}

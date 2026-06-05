<?php

namespace Tests\Feature;

use App\Enums\PermissionLevel;
use App\Enums\Role;
use App\Enums\Section;
use App\Models\User;
use App\Models\Wedding;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function service(): PermissionService
    {
        return app(PermissionService::class);
    }

    public function test_owner_has_write_on_every_section(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        foreach (Section::cases() as $section) {
            $this->assertTrue(
                $this->service()->canWrite($owner, $wedding, $section),
                "Owner should write {$section->value}",
            );
        }
    }

    public function test_viewer_cannot_write_budget_and_cannot_read_vendors(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $wedding->load('members');

        $this->assertFalse($this->service()->canWrite($viewer, $wedding, Section::Budget));
        $this->assertFalse($this->service()->canRead($viewer, $wedding, Section::Vendors));
        $this->assertTrue($this->service()->canRead($viewer, $wedding, Section::Gallery));
    }

    public function test_per_user_override_beats_role_default(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        // Collaborator default for Budget is read; override to write.
        $wedding->members()->attach($collaborator->id, [
            'role' => Role::Collaborator->value,
            'permissions' => json_encode([Section::Budget->value => PermissionLevel::Write->value]),
        ]);
        $wedding->load('members');

        $this->assertTrue($this->service()->canWrite($collaborator, $wedding, Section::Budget));
    }

    public function test_non_member_has_no_access(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($this->service()->canRead($stranger, $wedding, Section::Overview));
    }

    public function test_admin_has_write_everywhere(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($this->service()->canWrite($admin, $wedding, Section::Settings));
    }
}

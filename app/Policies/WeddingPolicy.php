<?php

namespace App\Policies;

use App\Enums\PermissionLevel;
use App\Enums\Section;
use App\Models\User;
use App\Models\Wedding;
use App\Services\PermissionService;

class WeddingPolicy
{
    public function __construct(protected PermissionService $permissions) {}

    public function before(User $user, string $ability): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function view(User $user, Wedding $wedding): bool
    {
        return $wedding->roleFor($user) !== null;
    }

    /**
     * Enforce the plan's wedding cap server-side.
     */
    public function create(User $user): bool
    {
        $max = $user->planLimit('max_weddings');

        if ($max === null) {
            return true; // unlimited
        }

        return $user->ownedWeddings()->count() < $max;
    }

    public function update(User $user, Wedding $wedding): bool
    {
        return $this->permissions->canWrite($user, $wedding, Section::Settings);
    }

    public function delete(User $user, Wedding $wedding): bool
    {
        return $user->id === $wedding->owner_id;
    }

    public function manageCollaborators(User $user, Wedding $wedding): bool
    {
        return $this->permissions->canWrite($user, $wedding, Section::Collaborators);
    }
}

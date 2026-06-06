<?php

namespace App\Support;

use App\Models\GalleryPhoto;
use App\Models\Guest;
use App\Models\Wedding;
use Illuminate\Validation\ValidationException;

/**
 * Server-side enforcement of subscription plan caps. A wedding's limits are
 * governed by its owner's plan (config/plans.php). A `null` limit means
 * unlimited.
 */
class PlanLimits
{
    /** The owner's configured cap for a key, or null when unlimited. */
    public function limit(Wedding $wedding, string $key): ?int
    {
        return $wedding->owner?->planLimit($key);
    }

    public function guestCount(Wedding $wedding): int
    {
        return Guest::query()->forWedding($wedding->id)->count();
    }

    public function galleryCount(Wedding $wedding): int
    {
        return GalleryPhoto::query()->forWedding($wedding->id)->count();
    }

    /** Members with access excluding the owner. */
    public function collaboratorCount(Wedding $wedding): int
    {
        return $wedding->members()
            ->where('users.id', '!=', $wedding->owner_id)
            ->count();
    }

    public function remaining(Wedding $wedding, string $key, int $used): ?int
    {
        $limit = $this->limit($wedding, $key);

        return $limit === null ? null : max(0, $limit - $used);
    }

    /**
     * Throw a validation error on $field if adding one more would exceed the cap.
     */
    public function enforce(Wedding $wedding, string $key, int $used, string $field, string $resource): void
    {
        $limit = $this->limit($wedding, $key);

        if ($limit !== null && $used >= $limit) {
            throw ValidationException::withMessages([
                $field => "Your plan is limited to {$limit} {$resource}. Upgrade to add more.",
            ]);
        }
    }

    public function enforceGuests(Wedding $wedding): void
    {
        $this->enforce($wedding, 'max_guests_per_wedding', $this->guestCount($wedding), 'first_name', 'guests');
    }

    public function enforceGallery(Wedding $wedding): void
    {
        $this->enforce($wedding, 'max_gallery_photos', $this->galleryCount($wedding), 'photo', 'photos');
    }

    public function enforceCollaborators(Wedding $wedding): void
    {
        $this->enforce($wedding, 'max_collaborators_per_wedding', $this->collaboratorCount($wedding), 'email', 'collaborators');
    }
}

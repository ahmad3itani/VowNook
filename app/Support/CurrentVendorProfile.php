<?php

namespace App\Support;

use App\Models\VendorProfile;

/**
 * Request-scoped holder for the active vendor profile (the marketplace business
 * owned by a vendor account). Mirrors CurrentWedding; populated by the
 * SetCurrentVendorProfile middleware.
 */
class CurrentVendorProfile
{
    protected ?VendorProfile $profile = null;

    public function set(?VendorProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function get(): ?VendorProfile
    {
        return $this->profile;
    }

    public function exists(): bool
    {
        return $this->profile !== null;
    }

    public function id(): ?int
    {
        return $this->profile?->id;
    }
}

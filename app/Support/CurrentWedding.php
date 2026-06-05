<?php

namespace App\Support;

use App\Models\Wedding;

/**
 * Request-scoped holder for the active wedding. Bound as a singleton in the
 * container and populated by the SetCurrentWedding middleware.
 */
class CurrentWedding
{
    protected ?Wedding $wedding = null;

    public function set(?Wedding $wedding): void
    {
        $this->wedding = $wedding;
    }

    public function get(): ?Wedding
    {
        return $this->wedding;
    }

    public function exists(): bool
    {
        return $this->wedding !== null;
    }

    public function id(): ?int
    {
        return $this->wedding?->id;
    }
}

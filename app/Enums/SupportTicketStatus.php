<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case Open    = 'open';
    case Pending = 'pending';
    case Closed  = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open    => 'Open',
            self::Pending => 'Awaiting reply',
            self::Closed  => 'Closed',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Closed;
    }
}

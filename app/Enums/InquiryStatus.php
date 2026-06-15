<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case Requested = 'requested';
    case Offered   = 'offered';
    case Accepted  = 'accepted';
    case Declined  = 'declined';
    case Closed    = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Awaiting response',
            self::Offered   => 'Offer received',
            self::Accepted  => 'Accepted',
            self::Declined  => 'Declined',
            self::Closed    => 'Closed',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Requested, self::Offered]);
    }
}

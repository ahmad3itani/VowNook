<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Sent      = 'sent';
    case Accepted  = 'accepted';
    case Declined  = 'declined';
    case Expired   = 'expired';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Sent      => 'Pending response',
            self::Accepted  => 'Accepted',
            self::Declined  => 'Declined',
            self::Expired   => 'Expired',
            self::Withdrawn => 'Withdrawn',
        };
    }
}

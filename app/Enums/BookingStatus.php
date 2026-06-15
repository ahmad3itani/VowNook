<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PendingPayment = 'pending_payment';
    case DepositPaid    = 'deposit_paid';
    case PaidInFull     = 'paid_in_full';
    case Completed      = 'completed';
    case Cancelled      = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending payment',
            self::DepositPaid    => 'Deposit paid',
            self::PaidInFull     => 'Paid in full',
            self::Completed      => 'Completed',
            self::Cancelled      => 'Cancelled',
        };
    }
}

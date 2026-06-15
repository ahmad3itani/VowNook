<?php

namespace Tests\Unit;

use App\Support\PlatformFee;
use Tests\TestCase;

class PlatformFeeTest extends TestCase
{
    public function test_charges_base_rate_under_the_threshold(): void
    {
        // $3,000 booking → 8% = $240
        $this->assertSame(24000, PlatformFee::for(300000));
    }

    public function test_charges_reduced_rate_above_the_threshold(): void
    {
        // $10,000 booking → 8% of $5,000 + 5% of $5,000 = $400 + $250 = $650
        $this->assertSame(65000, PlatformFee::for(1000000));
    }

    public function test_fee_is_capped(): void
    {
        // $30,000 venue booking → uncapped would be $1,650; capped at $1,000.
        $this->assertSame(100000, PlatformFee::for(3000000));
    }
}

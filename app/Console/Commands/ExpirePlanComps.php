<?php

namespace App\Console\Commands;

use App\Support\PlanComp;
use Illuminate\Console\Command;

class ExpirePlanComps extends Command
{
    protected $signature = 'plans:expire-comps';

    protected $description = 'Revert comped plans back to free once their window has passed';

    public function handle(): int
    {
        $reverted = PlanComp::expireOverdue();
        $this->info("Reverted {$reverted} expired plan comp(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An email captured by the shop's budget-cheat-sheet opt-in. `consented_at`
 * records the express CASL consent for the content they requested.
 */
class ShopLead extends Model
{
    protected $fillable = ['email', 'source', 'consented_at'];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime'];
    }
}

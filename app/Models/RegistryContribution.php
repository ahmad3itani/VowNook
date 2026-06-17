<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistryContribution extends Model
{
    protected $fillable = [
        'registry_fund_id',
        'contributor_name',
        'contributor_email',
        'amount_cents',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
        ];
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(RegistryFund::class, 'registry_fund_id');
    }
}

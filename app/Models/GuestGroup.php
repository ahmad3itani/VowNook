<?php

namespace App\Models;

use Database\Factories\GuestGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestGroup extends Model
{
    /** @use HasFactory<GuestGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'name',
        'notes',
    ];

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'group_id');
    }
}

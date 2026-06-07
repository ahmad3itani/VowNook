<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatingLayout extends Model
{
    /** @use HasFactory<\Database\Factories\SeatingLayoutFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'room_width',
        'room_height',
    ];

    protected function casts(): array
    {
        return [
            'room_width' => 'integer',
            'room_height' => 'integer',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}

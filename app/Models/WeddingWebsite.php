<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingWebsite extends Model
{
    /** @use HasFactory<\Database\Factories\WeddingWebsiteFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'is_published',
        'headline',
        'welcome_message',
        'our_story',
        'venue_name',
        'venue_address',
        'ceremony_time',
        'dress_code',
        'hero_image_url',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }
}

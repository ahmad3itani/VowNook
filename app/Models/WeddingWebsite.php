<?php

namespace App\Models;

use Database\Factories\WeddingWebsiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeddingWebsite extends Model
{
    /** @use HasFactory<WeddingWebsiteFactory> */
    use HasFactory;

    protected $fillable = [
        'wedding_id',
        'is_published',
        'subdomain',
        'template',
        'headline',
        'welcome_message',
        'our_story',
        'venue_name',
        'venue_address',
        'ceremony_time',
        'dress_code',
        'hero_image_url',
        'hero_image_path',
        'hero_video_url',
        'story_image_path',
        'timeline_items',
        'video_url',
        'music_path',
        'music_title',
        'travel_notes',
        'show_travel_stays',
        'nearest_airport',
        'faq_items',
        'local_recommendations',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_travel_stays' => 'boolean',
            'timeline_items' => 'array',
            'faq_items' => 'array',
            'local_recommendations' => 'array',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WeddingWebsitePhoto::class)->orderBy('sort_order');
    }
}

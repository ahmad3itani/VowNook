<?php

namespace App\Models;

use App\Enums\BlogCategory;
use App\Support\Markdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image_path',
        'cover_alt',
        'category',
        'author_name',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => BlogCategory::class,
            'published_at' => 'datetime',
        ];
    }

    /** Live posts: published and not scheduled for the future. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function renderedBody(): string
    {
        return Markdown::toHtml($this->body ?? '');
    }

    /** Estimated reading time in whole minutes (~200 wpm, min 1). */
    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags($this->body ?? ''));

        return max(1, (int) ceil($words / 200));
    }

    public function coverUrl(): ?string
    {
        if (blank($this->cover_image_path)) {
            return null;
        }

        // Committed marketing/blog imagery under public/images/ serves directly.
        if (str_starts_with($this->cover_image_path, 'images/')) {
            return '/'.$this->cover_image_path;
        }

        if (! Storage::exists($this->cover_image_path)) {
            return null;
        }

        return route('blog.media', basename($this->cover_image_path));
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

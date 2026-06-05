<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Wedding extends Model
{
    /** @use HasFactory<\Database\Factories\WeddingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'event_date',
        'timezone',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Wedding $wedding) {
            if (blank($wedding->slug)) {
                $wedding->slug = static::uniqueSlug($wedding->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'wedding';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wedding_user')
            ->withPivot(['role', 'permissions', 'invited_at', 'accepted_at'])
            ->withTimestamps();
    }

    /** The membership role for a given user, or null if not a member. */
    public function roleFor(User $user): ?Role
    {
        if ($user->id === $this->owner_id) {
            return Role::Owner;
        }

        $member = $this->members->firstWhere('id', $user->id)
            ?? $this->members()->find($user->id);

        $value = $member?->pivot?->role;

        return $value ? Role::from($value) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\WeddingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Wedding extends Model
{
    /** @use HasFactory<WeddingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'event_date',
        'timezone',
        'settings',
        'total_budget_cents',
        'city',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'settings' => 'array',
            'total_budget_cents' => 'integer',
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

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function guestGroups(): HasMany
    {
        return $this->hasMany(GuestGroup::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(WeddingEvent::class);
    }

    public function accommodations(): HasMany
    {
        return $this->hasMany(WeddingAccommodation::class);
    }

    public function guestBroadcasts(): HasMany
    {
        return $this->hasMany(GuestBroadcast::class);
    }

    public function guestSends(): HasMany
    {
        return $this->hasMany(GuestSend::class);
    }

    public function gifts(): HasMany
    {
        return $this->hasMany(Gift::class);
    }

    public function registryFunds(): HasMany
    {
        return $this->hasMany(RegistryFund::class);
    }

    public function registryItems(): HasMany
    {
        return $this->hasMany(RegistryItem::class);
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function budgetCategories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class);
    }

    public function seatingTables(): HasMany
    {
        return $this->hasMany(SeatingTable::class);
    }

    public function website(): HasOne
    {
        return $this->hasOne(WeddingWebsite::class);
    }

    public function seatingElements(): HasMany
    {
        return $this->hasMany(SeatingElement::class);
    }

    public function seatingLayout(): HasOne
    {
        return $this->hasOne(SeatingLayout::class);
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

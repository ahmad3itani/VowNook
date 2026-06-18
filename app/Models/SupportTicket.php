<?php

namespace App\Models;

use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const CATEGORIES = ['general', 'billing', 'technical', 'vendor', 'abuse', 'other'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'last_reply_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class)->orderBy('created_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', SupportTicketStatus::Closed->value);
    }
}

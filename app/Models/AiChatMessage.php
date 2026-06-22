<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One turn in a couple's conversation with the AI wedding planner. Persisted so
 * the chat is an ongoing planning partner (it remembers context between visits),
 * scoped to the wedding so collaborators share the same thread.
 */
class AiChatMessage extends Model
{
    public const ROLES = ['user', 'assistant'];

    protected $fillable = ['wedding_id', 'role', 'content'];

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    /** @param  Builder<AiChatMessage>  $query */
    public function scopeForWedding(Builder $query, int $weddingId): Builder
    {
        return $query->where('wedding_id', $weddingId);
    }

    /** @param  Builder<AiChatMessage>  $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('id');
    }
}

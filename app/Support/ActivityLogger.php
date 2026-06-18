<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records an audit-trail entry. By default the actor is the authenticated user
 * and the IP comes from the current request; both can be overridden (e.g. for
 * console/system events). Never throws into the caller — auditing must not break
 * the action being audited.
 */
class ActivityLogger
{
    public static function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
        ?string $description = null,
    ): ?ActivityLog {
        try {
            $actor ??= auth()->user();

            return ActivityLog::create([
                'actor_id' => $actor?->getKey(),
                'action' => $action,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'properties' => $properties ?: null,
                'ip_address' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}

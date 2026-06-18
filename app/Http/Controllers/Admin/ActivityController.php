<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform-wide audit trail: every admin action and key user event, newest
 * first, filterable by action type.
 */
class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $action = $request->query('action');

        $logs = ActivityLog::query()
            ->with('actor:id,name,email')
            ->when($action, fn ($q) => $q->where('action', $action))
            ->latest()
            ->paginate(40)
            ->withQueryString()
            ->through(fn (ActivityLog $a) => [
                'id' => $a->id,
                'action' => $a->action,
                'actor' => $a->actor ? ['id' => $a->actor->id, 'name' => $a->actor->name] : null,
                'subject_type' => $a->subject_type ? class_basename($a->subject_type) : null,
                'subject_id' => $a->subject_id,
                'description' => $a->description,
                'properties' => $a->properties,
                'ip' => $a->ip_address,
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/activity', [
            'logs' => $logs,
            'actions' => ActivityLog::query()
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'filter' => ['action' => $action],
        ]);
    }
}

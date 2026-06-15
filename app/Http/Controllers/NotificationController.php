<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return Inertia::render('notifications/index', [
            'notifications' => [
                'data' => collect($notifications->items())->map(fn ($n) => [
                    'id' => $n->id,
                    'read' => $n->read_at !== null,
                    'title' => $n->data['title'] ?? 'Notification',
                    'body' => $n->data['body'] ?? null,
                    'url' => $n->data['url'] ?? null,
                    'created_at' => $n->created_at?->toIso8601String(),
                ])->values(),
                'next_page_url' => $notifications->nextPageUrl(),
            ],
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->delete();

        return back();
    }
}

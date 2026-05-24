<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $notifications = $this->visibleNotificationsQuery((int) $user->id)
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (AppNotification $notification) => $this->payload($notification));

        $unreadCount = $this->visibleNotificationsQuery((int) $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $this->visibleNotificationsQuery((int) $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'ok' => true,
            'unread_count' => 0,
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()?->id, 403);

        if (! $notification->read_at) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $unreadCount = $this->visibleNotificationsQuery((int) $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'ok' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    private function payload(AppNotification $notification): array
    {
        return [
            'id' => (int) $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'url' => $notification->url,
            'data' => $notification->data ?? [],
            'is_read' => (bool) $notification->read_at,
            'time' => $notification->created_at?->diffForHumans() ?? '',
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    private function visibleNotificationsQuery(int $userId)
    {
        return AppNotification::query()
            ->where('user_id', $userId)
            ->where('type', '!=', 'chat.message');
    }
}

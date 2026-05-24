<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AppNotification $notification,
        public int $unreadCount
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.user.' . $this->notification->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'notification' => $this->payload(),
            'unread_count' => $this->unreadCount,
        ];
    }

    private function payload(): array
    {
        return [
            'id' => (int) $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'url' => $this->notification->url,
            'data' => $this->notification->data ?? [],
            'is_read' => (bool) $this->notification->read_at,
            'time' => $this->notification->created_at?->diffForHumans() ?? '',
            'created_at' => $this->notification->created_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Events;

use App\Models\ChatThread;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatThreadUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $thread;

    public function __construct(ChatThread $thread)
    {
        $thread->loadMissing(['provider', 'providerUser', 'branchUser', 'closer']);

        $this->thread = [
            'id' => (int) $thread->id,
            'conversation_type' => $thread->conversation_type ?: 'provider_admin',
            'ticket_status' => $thread->ticket_status ?: 'none',
            'status' => $thread->status ?: 'open',
            'ticket_rejection_reason' => $thread->ticket_rejection_reason,
            'closed_at' => $thread->closed_at?->toIso8601String(),
            'closed_by' => $thread->closer?->name,
            'last_admin_read_at' => $thread->last_admin_read_at?->toIso8601String(),
            'last_provider_read_at' => $thread->last_provider_read_at?->toIso8601String(),
            'last_branch_read_at' => $thread->last_branch_read_at?->toIso8601String(),
            'read_receipts' => [
                'last_admin_read_at' => $thread->last_admin_read_at?->toIso8601String(),
                'last_provider_read_at' => $thread->last_provider_read_at?->toIso8601String(),
                'last_branch_read_at' => $thread->last_branch_read_at?->toIso8601String(),
            ],
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.thread.' . $this->thread['id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'thread.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'thread' => $this->thread,
        ];
    }
}

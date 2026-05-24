<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Support\ChatMessagePresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $message;

    public function __construct(ChatMessage $message)
    {
        $message->loadMissing(['sender', 'thread']);

        $this->message = ChatMessagePresenter::make($message, null, $message->thread);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.thread.' . $this->message['thread_id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}

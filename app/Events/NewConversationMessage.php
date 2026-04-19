<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ConversationMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewConversationMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ConversationMessage $message) {}

    public function broadcastOn(): Channel
    {
        return new Channel('organization.' . $this->message->conversation->organization_id);
    }

    public function broadcastAs(): string
    {
        return 'message.new';
    }

    public function broadcastWith(): array
    {
        $msg = $this->message;
        $conv = $msg->conversation;

        return [
            'message' => [
                'id'              => $msg->id,
                'conversation_id' => $msg->conversation_id,
                'body'            => $msg->body,
                'direction'       => $msg->direction,
                'user_id'         => $msg->user_id,
                'sent_at'         => $msg->sent_at->toISOString(),
            ],
            'conversation' => [
                'id'              => $conv->id,
                'status'          => $conv->status,
                'unread'          => true,
                'last_message'    => $msg->body,
                'last_message_at' => $msg->sent_at->toISOString(),
            ],
        ];
    }
}

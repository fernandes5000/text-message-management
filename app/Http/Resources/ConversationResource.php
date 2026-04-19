<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status,
            'unread'          => $this->unread,
            'number'          => $this->number,
            'last_message'    => $this->last_message,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'subscriber'      => $this->whenLoaded('subscriber', fn () => [
                'id'         => $this->subscriber->id,
                'first_name' => $this->subscriber->first_name,
                'last_name'  => $this->subscriber->last_name,
                'phone'      => $this->subscriber->phone,
            ]),
            'messages'        => $this->whenLoaded('messages', fn () =>
                $this->messages->map(fn ($m) => [
                    'id'              => $m->id,
                    'conversation_id' => $m->conversation_id,
                    'body'            => $m->body,
                    'direction'       => $m->direction,
                    'user_id'         => $m->user_id,
                    'sent_at'         => $m->sent_at->toISOString(),
                ])->values()
            ),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'body'            => $this->body,
            'status'          => $this->status,
            'send_type'       => $this->send_type,
            'scheduled_at'    => $this->scheduled_at?->toISOString(),
            'recurrence'      => $this->recurrence,
            'from_number'     => $this->from_number,
            'use_header'      => $this->use_header,
            'header'          => $this->header,
            'media_url'       => $this->media_url,
            'recipient_count' => $this->recipient_count,
            'credits_used'    => $this->credits_used,
            'sent_at'         => $this->sent_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),
            'lists'           => $this->whenLoaded('lists', fn () => SubscriberListResource::collection($this->lists)->resolve()),
        ];
    }
}

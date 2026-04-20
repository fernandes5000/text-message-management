<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'source' => $this->source,
            'created_at' => $this->created_at,
            'list_ids' => $this->whenLoaded('lists', fn () => $this->lists->pluck('id')),
        ];
    }
}

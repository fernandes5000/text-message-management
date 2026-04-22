<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'sync_source' => $this->sync_source,
            'last_synced_at' => $this->last_synced_at,
            'subscribers_count' => $this->subscribers_count ?? $this->subscribers()->count(),
            'created_at' => $this->created_at,
        ];
    }
}

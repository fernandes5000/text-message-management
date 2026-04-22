<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KeywordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'number'        => $this->number,
            'status'        => $this->status,
            'aliases'       => $this->aliases ?? [],
            'workflow'      => $this->workflow ?? [],
            'uses_count'    => $this->uses_count,
            'opt_ins_count' => $this->opt_ins_count,
            'created_at'    => $this->created_at->toISOString(),
            'lists'         => $this->whenLoaded('lists', fn () => SubscriberListResource::collection($this->lists)->resolve()),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $responseCounts = [];
        if ($this->relationLoaded('responses')) {
            foreach ($this->responses as $response) {
                $responseCounts[$response->option_index] = ($responseCounts[$response->option_index] ?? 0) + 1;
            }
        }

        return [
            'id'              => $this->id,
            'question'        => $this->question,
            'options'         => $this->options,
            'active'          => $this->active,
            'message_id'      => $this->message_id,
            'response_counts' => $responseCounts,
            'total_responses' => $this->whenLoaded('responses', fn () => $this->responses->count(), 0),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}

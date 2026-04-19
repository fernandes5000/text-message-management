<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'default_number' => $this->default_number,
            'credits' => $this->credits,
            'parent_id' => $this->parent_id,
            'is_sub_account' => $this->isSubAccount(),
        ];
    }
}

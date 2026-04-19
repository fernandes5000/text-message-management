<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKeywordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['sometimes', 'string', 'max:50', 'regex:/^\S+$/'],
            'number'            => ['nullable', 'string', 'max:20'],
            'status'            => ['sometimes', 'in:active,archived'],
            'aliases'           => ['nullable', 'array'],
            'aliases.*'         => ['string', 'max:50', 'regex:/^\S+$/'],
            'workflow'          => ['nullable', 'array'],
            'workflow.*.type'   => ['required', 'string', 'in:send_message,add_to_list,collect_info'],
            'workflow.*.config' => ['required', 'array'],
            'list_ids'          => ['nullable', 'array'],
            'list_ids.*'        => ['integer', 'exists:lists,id'],
        ];
    }
}

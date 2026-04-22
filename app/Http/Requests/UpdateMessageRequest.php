<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:255'],
            'body'         => ['sometimes', 'string', 'max:1600'],
            'send_type'    => ['sometimes', 'in:now,scheduled,recurring'],
            'scheduled_at' => ['required_if:send_type,scheduled', 'nullable', 'date', 'after:now'],
            'recurrence'   => ['required_if:send_type,recurring', 'nullable', 'in:daily,weekly,monthly'],
            'from_number'  => ['nullable', 'string', 'max:20'],
            'use_header'   => ['boolean'],
            'header'       => ['nullable', 'string', 'max:100'],
            'list_ids'     => ['nullable', 'array'],
            'list_ids.*'   => ['integer', 'exists:lists,id'],
        ];
    }
}

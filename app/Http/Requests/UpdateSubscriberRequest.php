<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orgId = $this->user()->activeOrganization()?->id;
        $subscriberId = (int) $this->route('subscriber');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => [
                'required',
                'string',
                'max:30',
                Rule::unique('subscribers')->where('organization_id', $orgId)->ignore($subscriberId),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', Rule::in(['opted_in', 'opted_out'])],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }
}

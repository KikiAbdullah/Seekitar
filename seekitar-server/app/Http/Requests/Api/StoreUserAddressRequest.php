<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'label'          => ['required', 'string', 'max:50'],
            'address'        => ['required', 'string', 'max:1000'],
            'latitude'       => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'      => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'regency'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'regency_code'   => ['sometimes', 'nullable', 'string', 'max:10'],
            'recipient_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'recipient_phone'=> ['sometimes', 'nullable', 'string', 'max:20'],
            'is_default'     => ['sometimes', 'boolean'],
        ];
    }
}

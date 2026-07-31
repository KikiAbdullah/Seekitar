<?php

namespace App\Http\Requests\Api;

class UpdateCustomerRequestRequest extends StoreCustomerRequestRequest
{
    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'min:5', 'max:200'],
            'description' => ['sometimes', 'string', 'min:10', 'max:2000'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],

            'budget_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'budget_max' => ['sometimes', 'nullable', 'integer', 'min:0', 'gte:budget_min'],

            'latitude'  => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],

            'radius_km' => ['sometimes', 'numeric', 'min:1', 'max:25'],

            'images'   => ['sometimes', 'nullable', 'array', 'max:3'],
            'images.*' => ['url', 'max:500'],

            'required_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
        ];
    }
}

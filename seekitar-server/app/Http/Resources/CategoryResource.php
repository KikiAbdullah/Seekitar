<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'parent_id'  => $this->parent_id,
            'icon'       => $this->icon,
            'sort_order' => (int) $this->sort_order,
            'children'   => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}

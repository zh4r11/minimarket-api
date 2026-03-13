<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'parent_id'  => $this->parent_id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'description' => $this->description,
            'is_active'  => $this->is_active,
            'parent'     => $this->whenLoaded('parent', fn () => new CategoryResource($this->parent)),
            'children'   => $this->whenLoaded('children', fn () => CategoryResource::collection($this->children)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

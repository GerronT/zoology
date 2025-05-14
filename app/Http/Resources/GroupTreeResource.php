<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_root' => $this->parent_group_id == null,
            'name' => $this->name,
            'description' => $this->description,
            'classification' => $this->classification->name ?? null,
            'level' => $this->level->name ?? null,
            'animals' => $this->animals->map(function ($animal) {
                return [
                    'id' => $animal->id,
                    'name' => $animal->name,
                    'alt_name' => $animal->alt_name,
                    'description' => $animal->description,
                ];
            }),
            'children' => GroupTreeResource::collection($this->children),
        ];
    }
}

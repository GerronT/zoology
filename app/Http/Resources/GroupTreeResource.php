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
            'has_children' => $this->children()->exists(),
            'name' => $this->name,
            'description' => $this->description,
            'classification_id' => $this->classification_id,
            'classification' => $this->classification->name ?? null,
            'level_id' => $this->level_id,
            'level' => $this->level->name ?? null,
            'parent_group_id' => $this->parent_group_id,
            'animals' => $this->animals->map(function ($animal) {
                return [
                    'id' => $animal->id,
                    'name' => $animal->name,
                    'alt_name' => $animal->alt_name,
                    'description' => $animal->description,
                ];
            }),
            'isRanked' => $this->isRanked(),
            'primary_class' => strtolower($this->classification->name ?? 'none'),
        ];
    }
}

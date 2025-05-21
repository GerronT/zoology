<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $youngest_ranked_ancestor = $this->getYoungestRankedAncestor();
        $best_ranked_descendant = $this->getBestRankedDescendant();

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
            'yra_classification_id' => $youngest_ranked_ancestor?->classification_id,
            'brd_classification_id' => $best_ranked_descendant?->classification_id,
            'yra_level_id' => $youngest_ranked_ancestor?->level_id,
            'brd_level_id' => $best_ranked_descendant?->level_id,
            'primary_class' => strtolower($this->classification->name ?? 'none'),
        ];
    }
}

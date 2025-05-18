<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $youngest_ranked_ancestor = $this->getYoungestRankedAncestor();
        $primary_class = $this->fetchGroupClassName($youngest_ranked_ancestor);
        $youngest_ranked_ancestor_exclusive = $this->getYoungestRankedAncestor(false);

        $best_ranked_descendant = $this->getBestRankedDescendant();
        $secondary_class = $this->fetchGroupClassName($best_ranked_descendant);
        $best_ranked_descendant_exclusive = $this->getBestRankedDescendant(false);

        return [
            'id' => $this->id,
            'is_root' => $this->parent_group_id == null,
            'has_children' => $this->children()->exists(),
            'name' => $this->name,
            'description' => $this->description,
            'classification_id' => $this->classification_id,
            'classification' => $this->classification->name ?? null,
            'level' => $this->level->name ?? null,
            'level_id' => $this->level_id,
            'parent_group_id' => $this->parent_group_id,
            'animals' => $this->animals->map(function ($animal) {
                return [
                    'id' => $animal->id,
                    'name' => $animal->name,
                    'alt_name' => $animal->alt_name,
                    'description' => $animal->description,
                ];
            }),
            'youngest_ranked_ancestor' => $youngest_ranked_ancestor,
            'primary_class' => $primary_class,
            'youngest_ranked_ancestor_exclusive' => $youngest_ranked_ancestor_exclusive,
            'best_ranked_descendant' => $best_ranked_descendant,
            'secondary_class' => $secondary_class,
            'best_ranked_descendant_exclusive' => $best_ranked_descendant_exclusive,
        ];
    }

    private function fetchGroupClassName($group) {
        if ($group) {
            $group->load('classification');

            return strtolower($group->classification->name);
        }

        return 'none';
    }
}

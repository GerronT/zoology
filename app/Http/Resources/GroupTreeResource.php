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
            'primary_class' => $this->fetchPrimaryClass(),
            'secondary_class' => $this->fetchSecondaryClass(),
            'children' => GroupTreeResource::collection($this->children),
        ];
    }

    private function fetchPrimaryClass() {
        $group = $this->getYoungestRankedAncestor();

        if ($group) {
            $group->load('classification');
            return strtolower($group->classification->name);
        }

        return 'none';
    }

    private function fetchSecondaryClass() {
        $group = $this->getBestRankedDescendant();

        if ($group) {
            $group->load('classification');
            return strtolower($group->classification->name);
        }

        return 'none';
    }
}

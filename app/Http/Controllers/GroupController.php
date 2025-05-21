<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\Level;
use App\Models\Group;
use App\Http\Resources\GroupTreeResource;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index()
    {
        //
    }

    public function create(Group $group)
    {
        return Inertia::render('Groups/AddGroupForm', [
            ...$this->sharedGroupData(),
            'parentGroup' => new GroupTreeResource($group)
        ]);
    }

    public function store(Request $request)
    {
        $group = Group::create($request->only([
            'name',
            'classification_id',
            'level_id',
            'description',
            'parent_group_id'
        ]));

        return response()->json($group, 201);
    }

    public function show(Group $group)
    {
        return new GroupTreeResource(
            $group->load(['classification', 'level', 'animals'])
        );
    }

    public function edit(Group $group)
    {
        return Inertia::render('Groups/EditGroupForm', [
            ...$this->sharedGroupData(),
            'data' => new GroupTreeResource($group)
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group)
    {
        $group->update($request->only([
            'name',
            'classification_id',
            'level_id',
            'description'
        ]));

        return response()->json($group);
    }

    public function destroy(Request $request, Group $group)
    {
        if ($request->boolean('exterminate')) {
            $this->deleteAllGroupDescendants($group);
        } else {

            $previousParent = $group->parent;

            if ($previousParent->isRanked()) {
                $previousParentValidClassification =  $previousParent->getClassificationRank();
            } else {
                $previousParentYoungestRankedAncestor = $previousParent->getYoungestRankedAncestor();
                $previousParentValidClassification = $previousParentYoungestRankedAncestor?->getClassificationRank();
            }
            
            foreach ($group->children as $grandChild) {
                if ($grandChild->isRanked()) {
                    $grandChildValidClassification =  $grandChild->getClassificationRank();
                } else {
                    $grandChildBestRankedDescendant = $grandChild->getBestRankedDescendant();
                    $grandChildValidClassification = $grandChildBestRankedDescendant?->getClassificationRank();
                }

                if ($grandChildValidClassification && $previousParentValidClassification) {
                    if (abs($grandChildValidClassification - $previousParentValidClassification) >= 2) {
                        return response()->json(['message' => 'Rank classification gap is too large for the previous parent to adopt some if not all of reincarnating group\'s current children'], 422);
                    }
                }
            }

            $group->children()->update(['parent_group_id' => $group->parent_group_id]);
            $group->delete();
        }

        return response()->json(true);
    }

    // --- Tree + Hierarchy Related ---

    public function groupTree(?int $group_root_id = null)
    {
        $groups = Group::query()
            ->when($group_root_id, fn($q) => $q->where('id', $group_root_id), fn($q) => $q->whereNull('parent_group_id'))
            ->with(['classification', 'level', 'animals'])
            ->get();

        return Inertia::render('Groups/tree', [
            ...$this->sharedGroupData(),
            'rootGroups' => GroupTreeResource::collection($groups)
        ]);
    }

    public function children(Group $group)
    {
        return GroupTreeResource::collection(
            $group->children()->with(['classification', 'level', 'animals'])->get()
        );
    }

    public function move(Request $request)
    {
        $validated = $request->validate([
            'child_id' => 'required|integer|exists:groups,id',
            'new_parent_id' => 'required|integer|exists:groups,id',
        ]);

        $child = Group::findOrFail($validated['child_id']);
        $newParent = Group::findOrFail($validated['new_parent_id']);

        if ($child->id === $newParent->id) {
            return response()->json(['message' => 'A group cannot be its own parent.'], 422);
        }

        if ($child->parent_group_id === $newParent->id) {
            return response()->json(['message' => 'Group already assigned to that parent'], 422);
        }

        $isDescendant = $this->isDescendant($child->id, $newParent);

        if ($child->isRanked()) {
            $childValidClassificationRank = $child->getClassificationRank();
            $childValidComboRank = $child->getComboRank();
            $reincarnatingUnrankedGroup = false;
        } else {
            $childBestRankDescendant = $child->getBestRankedDescendant();
            $childValidClassificationRank = $childBestRankDescendant?->getClassificationRank();
            $childValidComboRank = $childBestRankDescendant?->getComboRank();
            $reincarnatingUnrankedGroup = !!$isDescendant;
        }

        if ($newParent->isRanked()) {
            $newParentValidClassificationRank = $newParent->getClassificationRank();
            $newParentValidComboRank = $newParent->getComboRank();
        } else {
            $newParentYoungestRankedAncestor = $newParent->getYoungestRankedAncestor();
            if ($newParentYoungestRankedAncestor?->id === $child->id) {
                $newParentYoungestRankedAncestor = $child->getYoungestRankedAncestor();
            }
            $newParentValidClassificationRank = $newParentYoungestRankedAncestor?->getClassificationRank();
            $newParentValidComboRank = $newParentYoungestRankedAncestor?->getComboRank();
        }

        if (!$reincarnatingUnrankedGroup) {
            if ($childValidClassificationRank && $newParentValidClassificationRank) {
                if (abs($childValidClassificationRank - $newParentValidClassificationRank) >= 2) {
                    return response()->json(['message' => 'Rank classification gap is too large between group and new parent.'], 422);
                }
            }

            if ($childValidComboRank && $newParentValidComboRank) {
                if ($childValidComboRank <= $newParentValidComboRank) {
                    return response()->json(['message' => 'A better-ranked group cannot be assigned below a worse-ranked one.'], 422);
                }
            }
        }

        if ($isDescendant) {
            $child->load('parent', 'children');
            
            $previousParent = $child->parent;

            if ($previousParent->isRanked()) {
                $previousParentValidClassification =  $previousParent->getClassificationRank();
            } else {
                $previousParentYoungestRankedAncestor = $previousParent->getYoungestRankedAncestor();
                $previousParentValidClassification = $previousParentYoungestRankedAncestor?->getClassificationRank();
            }
            
            foreach ($child->children as $grandChild) {
                if ($grandChild->isRanked()) {
                    $grandChildValidClassification =  $grandChild->getClassificationRank();
                } else {
                    $grandChildBestRankedDescendant = $grandChild->getBestRankedDescendant();
                    $grandChildValidClassification = $grandChildBestRankedDescendant?->getClassificationRank();
                }

                if ($grandChildValidClassification && $previousParentValidClassification) {
                    if (abs($grandChildValidClassification - $previousParentValidClassification) >= 2) {
                        return response()->json(['message' => 'Rank classification gap is too large for the previous parent to adopt some if not all of reincarnating group\'s current children'], 422);
                    }
                }
            }

            $child->children()->update(['parent_group_id' => $child->parent_group_id]);
        }

        $child->parent_group_id = $newParent->id;
        $child->save();

        return response()->json([
            'message' => 'Parent updated successfully.',
            'nodesToOpen' => $isDescendant ? array_reverse($isDescendant) : false,
        ]);
    }

    // --- Mass Group Create ---

    public function massCreate()
    {
        return Inertia::render('Groups/create', $this->sharedGroupData());
    }

    public function massStore(StoreGroupRequest $request)
    {
        $request->validate([
            'groupings' => ['required', 'array', 'min:1'],
            'groupings.*' => ['array']
        ]);

        $parentId = null;

        foreach ($request->groupings as $groupData) {
            $group = !$groupData['useNewGroup'] && $groupData['id']
                ? Group::find($groupData['id'])
                : Group::firstOrCreate(
                    [
                        'name' => $groupData['name'],
                        'classification_id' => $groupData['classification_id'],
                        'level_id' => $groupData['level_id'],
                    ],
                    ['description' => $groupData['description']]
                );

            if ($parentId) {
                $group->parent_group_id = $parentId;
                $group->save();
            }

            $parentId = $group->id;
        }

        return response()->json(['message' => 'Groups created successfully.'], 201);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $groups = Group::where('name', 'like', '%' . $query . '%')
            ->limit(10)
            ->get();

        return response()->json($groups);
    }

    public function youngestRankedAncestor(Group $group)
    {
        return $group->getYoungestRankedAncestor();
    }

    public function childrenRaw(Group $group)
    {
        return response()->json($group->children);
    }

    // --- Utilities / Helpers ---
    private function deleteAllGroupDescendants(Group $group)
    {
        foreach ($group->children as $child) {
            $this->deleteAllGroupDescendants($child);
        }

        $group->delete();
    }

    private function isDescendant($childId, Group $potentialParent)
    {
        $parent = $potentialParent;
        $nodesToOpen = [];

        while ($parent) {
            $nodesToOpen[] = $parent->id;
            if ($parent->id == $childId) {
                array_pop($nodesToOpen);
                return $nodesToOpen;
            }
            $parent = $parent->parent;
        }

        return false;
    }

    private function sharedGroupData(): array
    {
        return [
            'classifications' => Classification::all(),
            'levels' => Level::all(),
        ];
    }
}

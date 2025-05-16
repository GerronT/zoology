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
use Illuminate\Database\Eloquent\Collection;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Groups/create', [
            'classifications' => Classification::all(),
            'levels' => Level::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGroupRequest $request)
    {
        $request->validate([
            'groupings' => ['array', 'min:1'],
            'groupings.*' => ['array']
        ]);

        // validate group fields data (id or data (clade, then level and class can be null))
        // check that first ite

        $group_parent_id = null;

        foreach ($request->groupings as $group) {
            $insertedOrFetchedGroup = null;

            if (!$group['useNewGroup'] && $group['id']) {
                $insertedOrFetchedGroup = Group::find($group['id']);
            } else {
                $insertedOrFetchedGroup = Group::firstOrCreate(
                    ['name' => $group['name'], 'classification_id' => $group['classification_id'], 'level_id' => $group['level_id']],
                    ['description' => $group['description']
                ]);
            }

            if ($group_parent_id /*&& $insertedOrFetchedGroup->wasRecentlyCreated*/) {
                $insertedOrFetchedGroup->parent_group_id = $group_parent_id;
                $insertedOrFetchedGroup->save();
            }

            $group_parent_id = $insertedOrFetchedGroup->id;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Group $group)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Group $group)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGroupRequest $request, Group $group)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group)
    {
        //
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $groups = Group::where('name', 'like', '%' . $query . '%')
            ->limit(10) // optional: limit results
            ->get();

        return response()->json($groups);
    }

    public function childgroups(Group $group)
    {
        return response()->json($group->childrenOnly);
    }

     public function indexTree(Request $request, ?int $group_root_id = null)
     {
        return Inertia::render('Groups/tree', [
            'classifications' => Classification::all(),
            'levels' => Level::all(),
            'group_root_id' => $group_root_id, 
            'open_nodes' => $request->has('openNodes'),
        ]);
     }

    public function tree(Request $request)
    {
        $groupRootId = $request->query('group_root_id');
        
        $rootGroups = Group::where(function($q) use ($groupRootId) {
            return $groupRootId ? $q->where('id', $groupRootId) : $q->whereNull('parent_group_id');
        })
        ->with(['children', 'classification', 'level', 'animals'])
        ->get();

        return GroupTreeResource::collection($rootGroups);
    }

    public function youngestRankedAncestor(Group $group)
    {
        return $group?->parent->getYoungestRankedAncestor();
    }

    public function moveGroup(Request $request)
    {
        $validated = $request->validate([
            'child_id' => 'required|integer|exists:groups,id',
            'new_parent_id' => 'required|integer|exists:groups,id',
        ]);

        $child = Group::findOrFail($validated['child_id']);
        $newParent = Group::findOrFail($validated['new_parent_id']);

        // Prevent assigning to self
        if ($child->id === $newParent->id) {
            return response()->json(['message' => 'A group cannot be its own parent.'], 422);
        }

        // Prevent assigning to current parent
        if ($child->parent_group_id === $newParent->id) {
            return response()->json(['message' => 'Group already assigned to that parent'], 422);
        }

        $childSelfOrBestRankedDescendant = $child->getBestRankedDescendant();
        $newParentSelfOrYoungestRankedAncestor = $newParent->getYoungestRankedAncestor();

        if ($childSelfOrBestRankedDescendant && $newParentSelfOrYoungestRankedAncestor) {
            if (abs($childSelfOrBestRankedDescendant->getClassificationRank() - $newParentSelfOrYoungestRankedAncestor->getClassificationRank()) >= 2) {
                return response()->json(['message' => 'A group (or its best ranked descendant if unranked) cannot skip a classification between itself and its to be assigned next ranked predecessor'], 422);
            }

            if ($childSelfOrBestRankedDescendant->getComboRank() <= $newParentSelfOrYoungestRankedAncestor->getComboRank()) {
                return response()->json(['message' => 'A group (or its best ranked descendant if unranked) cannot be better ranked than its to be assigned next ranked predecessor'], 422);
            }
        }

        // Prevent circular parenting (check if new parent is a descendant of the child)
        if ($this->isDescendant($child->id, $newParent)) {
            // Move all direct children of the child group to its previous parent
            $child->children()->update(['parent_group_id' => $child->parent_group_id]);
        }

        $child->parent_group_id = $newParent->id;
        $child->save();

        return response()->json(['message' => 'Parent updated successfully.']);
    }

    // Helper method to detect circular parenting
    private function isDescendant($childId, $potentialParent)
    {
        $parent = $potentialParent;
        while ($parent) {
            if ($parent->id == $childId) {
                return true;
            }
            $parent = $parent->parent; // Assuming you have a relationship defined like: parent() { return $this->belongsTo(Group::class, 'parent_group_id'); }
        }

        return false;
    }
}

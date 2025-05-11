<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Http\Resources\GroupTreeResource;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            'classifications' => \App\Models\Classification::all(),
            'levels' => \App\Models\Level::all(),
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

     public function indexTree()
     {
        return Inertia::render('Groups/tree');
     }

    public function tree()
    {
        $rootGroups = Group::whereNull('parent_group_id')
            ->with(['children', 'classification', 'level', 'animals'])
            ->get();

        return GroupTreeResource::collection($rootGroups);
    }

    public function youngestRankedAncestor(Group $group)
    {
        $ancestor = $group->parent;

        while ($ancestor) {
            if ($ancestor->classification_id && $ancestor->level_id) {
                return $ancestor;
            }

            $ancestor = $ancestor->parent;
        }

        return null;
    }
}

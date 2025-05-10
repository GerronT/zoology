<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Group;
use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateAnimalRequest;
use Dotenv\Exception\ValidationException;
use Inertia\Inertia;

class AnimalController extends Controller
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
        return Inertia::render('Animals/index', [
            'classifications' => \App\Models\Classification::all(),
            'levels' => \App\Models\Level::all(),
            'groups' => \App\Models\Group::all(),
            'animals' => \App\Models\Animal::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAnimalRequest $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:App\Models\Animal'],
            'alt_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'groupings' => ['array', 'min:1'],
            'groupings.*' => ['array']
        ]);

        $group_parent_id = null;

        foreach ($request->groupings as $group) {
            $insertedOrFetchedGroup = null;

            if (isset($group['id']) && $group['id']) {
                $insertedOrFetchedGroup = Group::find($group['id']);
            }

            if (!$insertedOrFetchedGroup) {
                $insertedOrFetchedGroup = Group::firstOrCreate(
                    ['name' => $group['name'], 'classification_id' => $group['classification_id'], 'level_id' => $group['level_id']],
                    ['description' => $group['description']
                ]);
            }

            if ($group_parent_id && $insertedOrFetchedGroup->wasRecentlyCreated) {
                $insertedOrFetchedGroup->parent_group_id = $group_parent_id;
                $insertedOrFetchedGroup->save();
            }

            $group_parent_id = $insertedOrFetchedGroup->id;
        }

        return Animal::create([
            'name' => $request->name,
            'alt_name' => $request->alt_name,
            'description' => $request->description,
            'group_id' => $group_parent_id
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Animal $animal)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Animal $animal)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAnimalRequest $request, Animal $animal)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Animal $animal)
    {
        //
    }
}

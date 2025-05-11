<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Classification;
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
        // Fetch the "Kingdom" classification
        $kingdom = Classification::where('name', 'Kingdom')->first();

        if ($kingdom) {
            $classifications = [];
            $current = $kingdom;
            while ($current) {
                $classifications[] = $current; // Add the current item to the result
                $current = Classification::find($current->succeeded_by_id);
            }
        } else {
            $classifications = Classification::all();
        }

        return Inertia::render('Animals/create', [
            'classifications' => $classifications,
            'levels' => \App\Models\Level::all(),
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

        // check last group is a species
        // check last group not attached to animal
        // validate group fields data (id or data (clade, then level and class can be null))

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

        $existingAnimal = Animal::where('group_id', $group_parent_id)->first();

        if (!$existingAnimal) {
            // No record with this field value exists, so create the new record
            return Animal::create([
                'name' => $request->name,
                'alt_name' => $request->alt_name,
                'description' => $request->description,
                'group_id' => $group_parent_id
            ]);
        } else {
            throw new \Exception("Animal already assigned to the selected species.");
        }
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

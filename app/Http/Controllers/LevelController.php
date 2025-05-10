<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Http\Requests\StoreLevelRequest;
use App\Http\Requests\UpdateLevelRequest;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $levels = Level::all();

        return $levels;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLevelRequest $request)
    {
        $request->validate([
            'name' => ['string', 'required', 'max:255'],
            'alt_name' => ['string', 'nullable', 'max:255'],
            'preceeded_by_id' => ['integer', 'nullable', 'exists:App\Models\Level,id', 'unique:App\Models\Level'],
            'succeeded_by_id' => ['integer', 'nullable', 'exists:App\Models\Level,id', 'unique:App\Models\Level']
        ]);

        $level = Level::create($request->all());

        return $level;
    }

    /**
     * Display the specified resource.
     */
    public function show(Level $level)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Level $level)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLevelRequest $request, Level $level)
    {
        $request->validate([
            'name' => ['string', 'required', 'max:255'],
            'alt_name' => ['string', 'nullable', 'max:255'],
        ]);

        $level->name = $request->name;
        $level->alt_name = $request->alt_name;
        $level->save();

        return $level;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Level $level)
    {
        $prev = Level::find($level->preceded_by_id);

        if ($prev) {
            $prev->succeeded_by_id = $level->succeeded_by_id;
            $prev->save();
        }

        $next = Level::find($level->succeeded_by_id);

        if ($next) {
            $next->preceded_by_id = $level->preceded_by_id;
            $next->save();
        }

        $level->delete();
    }

    /**
     * Reorder
     */
    public function reorder(Request $request) {
        $request->validate([
            'order' => ['array', 'size:'.Level::count()],
            'order.*' => ['integer']
        ]);

        $this->perform_reorder($request->order);
    }

    /**
     * Chunk Reorder
     */
    public function chunk_reorder(Request $request) {
        $request->validate([
            'previous_order' => ['array', 'min:2'],
            'new_order' => ['array', 'min:2'],
            'previous_order.*' => ['integer'],
            'new_order.*' => ['integer']
        ]);

        $prev_start = Level::find($request->previous_order[0]); // get preceded_by_id
        $prev_end = Level::find($request->previous_order[count($request->previous_order) - 1]); // get succeeded_by_id

        $this->perform_reorder($request->new_order, $prev_start->preceded_by_id, $prev_end->succeeded_by_id);
    }

    /**
     * Re-order function
     */
    private function perform_reorder($order, $preceded_by_id = null, $succeeded_by_id = null) {
        $previous_id = null;

        foreach($order as $id) {
            $level = Level::find($id);

            if ($level) {
                $level->preceded_by_id = $preceded_by_id;
                $level->succeeded_by_id = $succeeded_by_id;

                $previous = Level::find($previous_id);

                if ($previous) {
                    $level->preceded_by_id = $previous->id;
                    $previous->succeeded_by_id = $level->id;
                    $previous->save();
                }

                $level->save();
                $previous_id = $level->id;
            }
        }
    }
}

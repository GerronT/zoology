<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Http\Requests\StoreClassificationRequest;
use App\Http\Requests\UpdateClassificationRequest;
use Illuminate\Http\Request;

class ClassificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classifications = Classification::all();

        return $classifications;
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
    public function store(StoreClassificationRequest $request)
    {
        $request->validate([
            'name' => ['string', 'required', 'max:255'],
            'description' => ['string', 'nullable'],
            'preceeded_by_id' => ['integer', 'nullable', 'exists:App\Models\Classification,id', 'unique:App\Models\Classification'],
            'succeeded_by_id' => ['integer', 'nullable', 'exists:App\Models\Classification,id', 'unique:App\Models\Classification']
        ]);

        $classification = Classification::create($request->all());

        return $classification;
    }

    /**
     * Display the specified resource.
     */
    public function show(Classification $classification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Classification $classification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClassificationRequest $request, Classification $classification)
    {
        $request->validate([
            'name' => ['string', 'required', 'max:255'],
            'description' => ['string', 'nullable'],
        ]);

        $classification->name = $request->name;
        $classification->description = $request->description;
        $classification->save();

        return $classification;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Classification $classification)
    {
        $prev = Classification::find($classification->preceded_by_id);

        if ($prev) {
            $prev->succeeded_by_id = $classification->succeeded_by_id;
            $prev->save();
        }

        $next = Classification::find($classification->succeeded_by_id);

        if ($next) {
            $next->preceded_by_id = $classification->preceded_by_id;
            $next->save();
        }

        $classification->delete();
    }

    /**
     * Reorder
     */
    public function reorder(Request $request) {
        $request->validate([
            'order' => ['array', 'size:'.Classification::count()],
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

        $prev_start = Classification::find($request->previous_order[0]); // get preceded_by_id
        $prev_end = Classification::find($request->previous_order[count($request->previous_order) - 1]); // get succeeded_by_id

        $this->perform_reorder($request->new_order, $prev_start->preceded_by_id, $prev_end->succeeded_by_id);
    }

    /**
     * Re-order function
     */
    private function perform_reorder($order, $preceded_by_id = null, $succeeded_by_id = null) {
        $previous_id = null;

        foreach($order as $id) {
            $classification = Classification::find($id);

            if ($classification) {
                $classification->preceded_by_id = $preceded_by_id;
                $classification->succeeded_by_id = $succeeded_by_id;

                $previous = Classification::find($previous_id);

                if ($previous) {
                    $classification->preceded_by_id = $previous->id;
                    $previous->succeeded_by_id = $classification->id;
                    $previous->save();
                }

                $classification->save();
                $previous_id = $classification->id;
            }
        }
    }
}

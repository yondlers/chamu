<?php

namespace App\Http\Controllers;

use App\Helpers\Constants;
use App\Helpers\HtmlForm;
//use App\Http\Requests\UnitRequest;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Http\Request;
use App\Helpers\HtmlError;


class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $user = auth()->user();
        $team = $user->team;

        $units = Unit::where('team_id', $user->current_team_id)->get();

        return view('units.index', [
            'units' => $units,
            'user' => $user,
            'team' => $team
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        $properties = Property::where('team_id', $user->current_team_id)->get();
        if ($properties->isEmpty()) {
            return view('units.create', [
                'unitHtml' => HtmlError::htmlTemplate('Something is Missing', 'You do not have any active property in the system to create a unit list. Please ensure Properties are captured in the system to associate an Unit list with a Property.')
            ]);
        }

        return view('units.create',
            [
                'unitHtml' => Unit::htmlForm(new Unit())
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request['unit_type_id'] == 1)
        {
            return back()->withErrors(['You cannot select the unit type you selected.'])->withInput();
        }

        if (!$request['unit_type_id'] )

        $user = auth()->user();

        $unit = new Unit();

        $unit->unit_number = $request['unit_number'];

        $unit->property_id = $request['property_id'];
        $unit->unit_type_id = $request['unit_type_id'];

        $unit->monthly_rent = intval($request['monthly_rent']);

        $unit->active = true;
        $unit->team_id = intval($user->current_team_id);

        $validator = Unit::validator($unit);

        if ($validator == true)
        {
            $unit->save();
            return redirect()->route('units.index')->with('success', 'Unit created successfully!');
        }
        else {
            return back()->withErrors([$validator])->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        //
        return view('units.edit', [
            'unitHtml' => Unit::htmlForm($unit),
            'unit' => $unit
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $user = auth()->user();

        $unit->unit_number = $request['unit_number'];

        $unit->property_id = $request['property_id'];
        $unit->unit_type_id = $request['unit_type_id'];

        $unit->monthly_rent = floatval($request['monthly_rent'] ?? 0.00);

        $unit->active = true;
        $unit->team_id = intval($user->current_team_id);

        $validator = Unit::validator($unit);

        $unit->save();

        return redirect()->route('units.index')->with('success', 'Unit updated successfully!');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

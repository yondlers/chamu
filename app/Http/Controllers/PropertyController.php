<?php

namespace App\Http\Controllers;

use App\Helpers\Constants;
use App\Http\Requests\PropertyRequest;
use App\Http\Requests\StorePropertyRequest;
use App\Models\AuditLog;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Http\Request;
use App\Helpers\HtmlError;


class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $team = $user->currentTeam;

        $properties = Property::where('team_id', $user->current_team_id)->get();

        return view('properties.index', [
            'properties' => $properties,
            'user' => $user,
            'team' => $team
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('properties.create',
            [
                'propertyHtml' => Property::htmlForm(new Property()),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $property = Property::create([
            'unit_number' => $request['unit_number'],

            'address_line_1' => $request['address_line_1'],
            'address_line_2' => $request['address_line_2'],

            'suburb_id' => $request['suburb_id'],
            'city_id' => $request['city_id'],
            'province_id' => $request['province_id'],
            'country_id' => $request['country_id'],

            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],

            'postal_code' => $request['postal_code'],

            'property_type_id' => intval($request['property_type_id']),
            'rental_type' => $request['rental_type'],
            'property_policies' => $request['property_policies'],

            'active' => true,
            'team_id' => intval($user->current_team_id),
        ]);

        if ($request['rental_type'] == 'Whole')
        {
            Unit::create([
                'monthly_rent' => $request['monthly_rent'],
                'unit_number' => $request['unit_number'],
                'unit_type_id' => 1,
                'team_id' => intval($user->current_team_id),
                'property_id' => $property->id,
                'active' => true,
            ]);
        }


        AuditLog::log('Captured Property', 'Property named: ' . $request['name'] . ', description: ' . $request['description']);

        // Redirect with success message
        return redirect()->route('properties.index')->with('success', 'Property created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property)
    {
        //
        return view('properties.show', [
            'propertyHtml' => Property::htmlShow($property),
            'property' => $property,
        ]);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Property $property)
    {
        return view('properties.edit',
            [
                'propertyHtml' => Property::htmlForm($property),
                'property' => $property,
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyRequest $request, Property $property)
    {
        //
        $user = auth()->user();

        $property->unit_number = $request['unit_number'];

        $property->address_line_1 = $request['address_line_1'];
        $property->address_line_2 = $request['address_line_2'];

        $property->suburb_id = $request['suburb_id'];
        $property->city_id = $request['city_id'];
        $property->province_id = $request['province_id'];
        $property->country_id = $request['country_id'];

        $property->postal_code = $request['postal_code'];

        $property->property_type_id = intval($request['property_type_id']);
        $property->rental_type = $request['rental_type'];

        $property->pet_friendly = intval($request['pet_friendly']);


//        $property->active = true;

        $property->save();

        return redirect()->route('properties.index')->with('success', 'Property updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

    }
}

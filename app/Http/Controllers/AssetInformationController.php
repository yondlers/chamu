<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\Request;

// use App\Models\CheckInOut;
use App\Helpers\HtmlError;
use App\Models\AssetInformation;

class AssetInformationController extends Controller
{
    //
    public function index()
    {
        $user = auth()->user();

        $assets = AssetInformation::where('team_id', $user->current_team_id)->get();

        return view('asset_informations.index', [
            'assets' => $assets,
        ]);
    }

    public function createView($property_id, $unit_id) {
//        $user = auth()->user();

        $asset_information = new AssetInformation();

        if ($property_id !== 0)
        {
            $property = Property::where('id', $property_id)->first();
            $unit = null;
        }
        else if ($unit_id !== 0)
        {
            $unit = Unit::where('id', $unit_id)->first();
            $property = null;
        }

        return view('asset_informations.create_view', [
            'assetInformationHtml' => AssetInformation::htmlForm($asset_information),
            'property' => $property,
            'unit' => $unit,
        ]);
    }

    public function store(Request $request)
    {

        // Create a new Tenant instance nemo
        $tenant = new Tenant();
        $tenant->name = $request->input('name');
        $tenant->id_number = $request->input('id_number');
        $tenant->date_of_birth = $request->input('date_of_birth');
        $tenant->email = $request->input('email');
        $tenant->contact_number = $request->input('contact_number');
        $tenant->gender = $request->input('gender');
        $tenant->marital_status = $request->input('marital_status');
        $tenant->ethnicity = $request->input('ethnicity');
        $tenant->nationality = $request->input('nationality');
        $tenant->preferred_language = $request->input('preferred_language');
        $tenant->address_line_1 = $request->input('address_line_1');
        $tenant->address_line_2 = $request->input('address_line_2');
        $tenant->suburb = $request->input('suburb');
        $tenant->city = $request->input('city');
        $tenant->province = $request->input('province');
        $tenant->postal_code = $request->input('postal_code');
        $tenant->occupation = $request->input('occupation');
        $tenant->monthly_income = $request->input('monthly_income');
        $tenant->employer = $request->input('employer');
        $tenant->number_of_occupancies = $request->input('number_of_occupancies');
        $tenant->has_pets = $request->input('has_pets');
        $tenant->special_requirements = $request->input('special_requirements');
        $tenant->emergency_name = $request->input('emergency_name');
        $tenant->emergency_relationship = $request->input('emergency_relationship');
        $tenant->emergency_number = $request->input('emergency_number');

        $tenant->active = true;

        $password = $request->input('password');
        $confirm_password = $request->input('confirm_password');


        // Generate a unique file prefix for document storage
        $filePrefix = 'tenants/' . uniqid('tenant_');
        $tenant->file_prefix = $filePrefix;

        // Save ID Document
        if ($request->hasFile('id_document_path')) {
            $idDocumentPath = $request->file('id_document_path')->store($filePrefix, 'public');
            $tenant->id_document_file_name = $idDocumentPath;
        }

        // Save Bank Statements
        if ($request->hasFile('bank_statements_path')) {
            $bankStatementsPath = $request->file('bank_statements_path')->store($filePrefix, 'public');
            $tenant->bank_statements_file_name = $bankStatementsPath;
        }

        // Save Proof of Income
        if ($request->hasFile('proof_of_income_path')) {
            $proofOfIncomePath = $request->file('proof_of_income_path')->store($filePrefix, 'public');
            $tenant->proof_of_income_file_name = $proofOfIncomePath;
        }

        if ($password) {
            if ($password == $confirm_password)
            {
                $user = new User();
                $user->first_name = $request->input('first_name');
                $user->last_name = $request->input('last_name');
                $user->email = $request->input('email');
                $user->password = Hash::make($request->input('password'));

                $tenant->save();

                $user->save();

                $tenant->user_id = $user->id;

                return view('dashboard');

            } else {
                return redirect()->route('tenants.index')->with('error', 'Password do not match!');
            }
        }

        // Save the tenant to the database
        $tenant->save();

        return redirect()->route('tenants.index')->with('success', 'Tenant created successfully!');
    }

}

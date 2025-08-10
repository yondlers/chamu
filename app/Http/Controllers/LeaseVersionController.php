<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaseVersionRequest;
use App\Models\LeaseVersion;
use App\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\HtmlError;

class LeaseVersionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $user = auth()->user();

        $lease_versions = LeaseVersion::where('team_id', $user->current_team_id)->get();

        return view('lease_versions.index', [
            'lease_versions' => $lease_versions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        $leases = Lease::where('team_id', $user->current_team_id)->get();
        if ($leases->isEmpty()) {
            return view('lease_versions.create', [
                'leaseVersionHtml' => HtmlError::htmlTemplate('Something is Missing', 'You do not have any active lease in the system to generate an Inspection list. Please ensure Leases are registerd in the system to associate an Inspection list with a lease.')
            ]);
        }

        return view('lease_versions.create', [
            'leaseVersionHtml' => LeaseVersion::htmlForm(new LeaseVersion()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LeaseVersionRequest $request)
    {
        //

        $user = auth()->user();

        LeaseVersion::create([
            'active' => true,

            'lease_id' => $request['lease_id'],

            'lease_template_id' => $request['lease_template_id'],

            'signature_authorization_id' => $request['signature_authorization_id'],

            'debit_date' => $request['debit_date'],
            'rent_amount' => $request['rent_amount'],
            'deposit_amount' => $request['deposit_amount'],
            'type' => 'initial',
            'name' => $request['name'],
            'url_code' => Str::upper(Str::random(7)), // Generates a 7-character alphanumeric string

            'start_date' => $request['start_date'],
            'end_date' => $request['end_date'],

            'status' => true,
            'team_id' => intval($user->current_team_id),
        ]);

        AuditLog::log('Captured Lease', 'Lease Subject: ' . $request['name'] . ', with rent amount R' . $request['rent_amount']);


        return redirect()->route('lease_versions.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaseVersion $lease_version)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaseVersion $lease_version)
    {
        //

        return view('lease_versions.create', [
            'leaseVersionHtml' => LeaseVersion::htmlForm($lease_version),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LeaseVersionRequest $request, LeaseVersion $lease_version)
    {
        //

        $lease_version->active = true;
        $lease_version->lease_id = $request['lease_id'];

        $lease_version->lease_template_id = $request['lease_template_id'];
        $lease_version->signature_authorization_id = $request['signature_authorization_id'];

        $lease_version->debit_date = $request['debit_date'];
        $lease_version->rent_amount = $request['rent_amount'];
        $lease_version->deposit_amount = $request['deposit_amount'];
        $lease_version->type = 'initial';
        $lease_version->name = $request['name'];

        $lease_version->start_date = $request['start_date'];
        $lease_version->end_date = $request['end_date'];

        $lease_version->save();

        return redirect()->route('lease_versions.index')->with('success', 'Lease version updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaseVersion $lease_version)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CheckInOut;
use App\Helpers\HtmlError;
use App\Models\Lease;


use Illuminate\Http\Request;

class CheckInOutController extends Controller
{
    //

    public function index()
    {
        $user = auth()->user();

        $check_in_outs = CheckInOut::where('team_id', $user->current_team_id)->get();

        return view('check_in_outs.index', [
            'check_in_outs' => $check_in_outs,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $leases = Lease::where('team_id', $user->current_team_id)->get();
        
        if ($leases->isEmpty()) {
            return view('check_in_outs.create', [
                'checkInOutHtml' => HtmlError::htmlTemplate('Something is Missing', 'You do not have any active lease in the system to generate an Inspection list. Please ensure Leases are registerd in the system to associate an Inspection list with a lease.')
            ]);
        }

        return view('check_in_outs.create', [
            'checkInOutHtml' => CheckInOut::htmlForm(new CheckInOut()),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        CheckInOut::create([
            'name' => $request['name'],
            'initial_checkin_list' => $request['initial_checkin_list'],
            'requires_checkout' => $request['requires_checkout'],
            'team_id' => $user->current_team_id,
        ]);

        AuditLog::log('Captured Check In/Out', 'Record: ' . $request['name']);

        return redirect()->route('check_in_outs.index');
    }

    public function show(CheckInOut $check_in_out)
    {

    }

    public function edit(CheckInOut $check_in_out)
    {
        return view('check_in_outs.edit', [
            'checkInOutHtml' => CheckInOut::htmlForm($check_in_out),
        ]);
    }

    public function update(Request $request, CheckInOut $check_in_out)
    {
        $user = auth()->user();

        $check_in_out->name = $request['name'];
        $check_in_out->initial_checkin_list = $request['initial_checkin_list'];
        $check_in_out->requires_checkout = $request['requires_checkout'];
        $check_in_out->updated_by = $user->id;

        $check_in_out->save();

        AuditLog::log('Updated Check In/Out', 'Record: ' . $request['name']);


        return redirect()->route('check_in_outs.index');
    }

    public function destroy(CheckInOut $check_in_out)
    {

    }
}

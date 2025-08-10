<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceLogRequest;
use App\Models\MaintenanceLog;
use App\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\HtmlError;


class MaintenanceLogController extends Controller
{
    //
    public function index() {
        $user = auth()->user();

        $maintenance_logs = MaintenanceLog::where('team_id', $user->current_team_id)->orderBy('created_at', 'desc')->get();

        return view('maintenance_logs.index', [
            'logs' => $maintenance_logs,
        ]);
    }

    public function create() {
        $user = auth()->user();
        $leases = Lease::where('team_id', $user->current_team_id)->get();
        if ($leases->isEmpty()) {
            return view('maintenance_logs.create', [
                'maintenanceLogHtml' => HtmlError::htmlTemplate('Something is Missing', 'You do not have any active lease in the system to generate an Inspection list. Please ensure Leases are registerd in the system to associate an Inspection list with a lease.')
            ]);
        }

        return view('maintenance_logs.create', [
            'maintenanceLogHtml' => MaintenanceLog::HtmlForm(new MaintenanceLog()),
        ]);
    }

    public function store(MaintenanceLogRequest $request) {

        $user = auth()->user();

        $material_cost = floatval($request['material_cost']);
        $labour_cost = floatval($request['labour_cost']);
        $total_cost = $material_cost + $labour_cost;

        // Check if 'before_image' is present in the request
        if ($request->hasFile('before_image')) {
            // Get the file and encode it to Base64
            $before_image = base64_encode(file_get_contents($request->file('before_image')->getRealPath()));
        } else {
            $before_image = null; // Handle cases where no image is uploaded
        }

        // Check if 'after_image' is present in the request
        if ($request->hasFile('after_image')) {
            // Get the file and encode it to Base64
            $after_image = base64_encode(file_get_contents($request->file('after_image')->getRealPath()));
        } else {
            $after_image = null; // Handle cases where no image is uploaded
        }

        MaintenanceLog::create([
            'active' => true,
            // 'url_code' => Str::upper(Str::random(7)), // Generates a 7-character alphanumeric string

            'name' => $request['name'],
            'lease_id' => $request['lease_id'],
            'maintenance_type_id' => $request['maintenance_type_id'],
            'description' => $request['description'],

            'material_cost' => $material_cost,
            'labour_cost' => $labour_cost,
            'total_cost' => $total_cost,

            'before_image' => $before_image,
            'after_image' => $after_image,

            'performed_by' => $request['performed_by'],
            'performed_at' => $request['performed_at'],

            'team_id' => $user->current_team_id,
        ]);

        AuditLog::log('Captured Maintenance Log', 'Maintenance log named: ' . $request['name'] . ', description: ' . $request['description']);

        return redirect()->route('maintenance_logs.index');
    }

    public function show(MaintenanceLog $log) {
        return view('maintenance_logs.show', [
            'log' => $log
        ]);
    }

    public function edit(MaintenanceLog $log) {
        return view('maintenance_logs.edit', [
            'log' => $log
        ]);
    }

    public function update(MaintenanceLogRequest $request, MaintenanceLog $log) 
    {

        $log->name = $request['name'];
        $log->lease_id = $request['lease_id'];
        $log->maintenance_type_id = $request['maintenance_type_id'];
        $log->description = $request['description'];
        $log->material_cost = $request['material_cost'];
        $log->labour_cost = $request['labour_cost'];
        $log->before_image = $request['before_image'];
        $log->after_image = $request['after_image'];
        $log->performed_by = $request['performed_by'];
        $log->performed_at = $request['performed_at'];
        
        $log->save();

        return redirect()->route('maintenance_logs.index');
    }

}

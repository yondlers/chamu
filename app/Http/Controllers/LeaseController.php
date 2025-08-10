<?php

namespace App\Http\Controllers;

use App\Helpers\HtmlForm;
use App\Helpers\Template;
use App\Http\Requests\LeaseRequest;
use App\Models\AuditLog;
use App\Models\Lease;
use App\Models\LeaseTemplate;
use App\Models\Property;
use App\Models\SignatureAuthorization;
use App\Models\Tenant;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Jetstream\Agent;
use function Laravel\Prompts\error;
use Illuminate\Contracts\Validation\Validator;
use App\Helpers\HtmlError;



class LeaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        $leases = Lease::where('team_id', $user->current_team_id)->get();

        return view('leases.index', [
            'leases' => $leases
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('leases.create', [
            'leaseHtml' => Lease::htmlForm(new Lease())
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $user = auth()->user();

        $lease = new Lease();

        $lease->active = true;
        $lease->unit_id = $request['unit_id'];
        $lease->tenant_id = $request['tenant_id'];
        $lease->lease_template_id = $request['lease_template_id'];
        $lease->signature_authorization_id = $request['signature_authorization_id'];
        $lease->debit_date = $request['debit_date'];
        $lease->rent_amount = $request['rent_amount'];
        $lease->type = 'initial';
        $lease->name = $request['name'];
        $lease->url_code = Str::upper(Str::random(7)); // Generates a 7-character alphanumeric string
        $lease->late_fee = $request['late_fee'];
        $lease->late_fee_days = $request['late_fee_days'];
        $lease->utility_payer = $request['utility_payer'];
        $lease->notice_period = $request['notice_period'];
        $lease->start_date = $request['start_date'];
        $lease->end_date = $request['end_date'];
        $lease->team_id = intval($user->current_team_id);

        //            'file_prefix' => null,
//            'lease_contract_file' => null,
//            'tenant_signature' => null,
//            'tenant_signed_at' => null,
//            'tenant_signed_ip_address' => null,
//            'tenant_signed_device' => null,
//            'contract' => null,

        // Generate a unique file prefix for document storage
        $filePrefix = 'leases/' . uniqid('lease_');

        // Save ID Document
        if ($request->hasFile('lease_contract_file')) {
            $leasePath = $request->file('lease_contract_file')->store($filePrefix, 'public');

            $lease->file_prefix = $filePrefix;
            $lease->lease_contract_file = $leasePath;
        }

        $lease->save();

        AuditLog::log('Captured Lease', 'Lease Subject: ' . $request['name'] . ', with rent amount R' . $request['rent_amount']);

        return redirect()->route('leases.index')->with('success', 'Lease Created.');

    }

    /**
     * Display the specified resource.
     */
    public function show(Lease $lease)
    {
        //
        return view('leases.show', [
            'lease' => $lease
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lease $lease)
    {
        //
        return view('leases.edit', [
            'leaseHtml' => Lease::htmlForm($lease),
            'lease' => $lease
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lease $lease)
    {
        //
        // Validate the base rules
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        // Call the custom validation logic
        $request->withValidator($validator);

        $property_id = $request['property_id'];
        $unit_it = $request['unit_it'];
        if ($property_id && $unit_it) {
            return error('Please select only one');
        }

        $user = auth()->user();

        $lease = Lease::where('id', $lease->id)->update([
            'active' => true,

            'property_id' => $request['property_id'],
            'unit_id' => $request['unit_id'],

            'tenant_id' => $request['tenant_id'],
            'lease_template_id' => $request['lease_template_id'],
            'signature_authorization_id' => $request['signature_authorization_id'],

            'debit_date' => $request['debit_date'],
            'rent_amount' => $request['rent_amount'],
            'deposit_amount' => $request['deposit_amount'],
            'type' => 'initial',
            'name' => $request['name'],

            'start_date' => $request['start_date'],
            'end_date' => $request['end_date'],

            'updated_by' => intval($user->id),
        ]);

        $tenant_id = $lease->tenant_id;

        // Generate a unique file prefix for document storage
        $filePrefix = 'leases/' . uniqid('lease_');

        // Save ID Document
        if ($request->hasFile('lease_contract_file')) {
            $leasePath = $request->file('lease_contract_file')->store($filePrefix, 'public');


            Lease::where('id', $lease->id)->update([
                'file_prefix' => $filePrefix,
                'lease_contract_file' => $leasePath,
            ]);
        }

        return redirect()->route('leases.index')->with('success', 'Lease updated.');
    }

    public function tenant($id)
    {
        $lease = Lease::find($id);

        return view('leases.tenant', [
            'lease' => $lease,
            'signature' => HtmlForm::signature(),
            'uploadFile' => HtmlForm::generateInput('lease_contract_file', 'file', null, false),
        ]);
    }

    public function sign($id, Request $request)
    {
        $lease = Lease::where('id', $id)->first();

        $index = 0;
        $user = auth()->user();

        $agent = new Agent(); // Detect device details

        // Capture data
        $lease->tenant_signed_at = $request->ip();
        $lease->tenant_signed_ip_address  = Carbon::now();
//        $lease->tenant_signed_device  = $agent->device();

        $lease->tenant_signature = $request['signature_image'];

        // Save Document, if tenant uploaded a signed contract
        if ($request->hasFile('lease_contract_file')) {
            $index = 1;

            // Generate a unique file prefix for document storage
            $filePrefix = 'leases/' . uniqid('lease_');

            $leasePath = $request->file('lease_contract_file')->store($filePrefix, 'public');


            $lease->file_prefix = $filePrefix;
            $lease->lease_contract_file = $leasePath;

        } else if ($lease->tenant_signature) {
            $index = 2;
            $template = Template::generateContract($lease);
            $lease->contract = $template;
        } else {
            return error('Please select Upload Contract');
        }

        //Update status :-)

        $lease->update();

        if ($index = 1) {
            return back()->with('success', 'Lease signed.');
        } else if ($index = 2) {
            // Generate PDF
            $pdf = Pdf::loadHTML($template);
            return $pdf->download('lease_agreement.pdf');
        } else {
            return error('Please select Upload Contract');
        }

    }

    public function download(Lease $lease)
    {
        $template = $lease->contract;

        $lease_template = LeaseTemplate::where('id', $lease->lease_template_id)->first();


        // Check if the lease contract file exists in storage
        if ($lease->lease_contract_file && Storage::disk('public')->exists($lease->lease_contract_file)) {
            return Storage::disk('public')->download($lease->lease_contract_file, 'lease_agreement.pdf');
        }
        else if (!$template)
        {
            $template = Template::generateContract($lease);
            $lease->contract = $template;
            $lease->update();
        }
        else if ($lease_template)
        {
            if ($lease_template->lease_template_file && Storage::disk('public')->exists($lease_template->lease_template_file)) {
                return Storage::disk('public')->download($lease_template->lease_template_file, 'lease_agreement.pdf');
            }
            else {
                return error('Nothing to download.');
            }
        }
        else {
            return error('Nothing to download.');
        }


        // Generate PDF
        $pdf = Pdf::loadHTML($template);

        // Stream the PDF to the browser for download
        return $pdf->download('lease_agreement.pdf');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lease $lease)
    {
        //

    }



}

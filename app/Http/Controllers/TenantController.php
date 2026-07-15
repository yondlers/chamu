<?php

namespace App\Http\Controllers;

use App\Helpers\Constants;
use App\Helpers\HtmlForm;
use App\Helpers\LookUp;
use App\Http\Requests\TenantRequest;
use App\Models\Team;
use App\Models\TeamTenant;
use App\Models\Tenant;
// use App\Models\TeamTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantController extends Controller
{
    //
//    public function index() {
//        $tenants = Tenant::all();
//    }

    public function index()
    {
        $user = auth()->user();
        $team = $user->team;

        $tenants = TeamTenant::where('team_id', $user->current_team_id)
            ->get();

        return view('tenants.index', [
            'tenants' => $tenants,
            'user' => $user,
            'team' => $team
        ]);
    }


//    public function applicant() {
//        $user = auth()->user();
//        $team = $user->team;
//
//        $tenants = Tenant::where('team_id', $user->current_team_id)->get();
//
//        return view('tenants.tenant_approval', [
//            'tenants' => $tenants,
//            'user' => $user,
//            'team' => $team
//        ]);
//    }

    public function application() {

        $userForm = '
            <!-- Password -->
            ' . HtmlForm::generateInput('password', 'password', $tenant->password, true) . '
            <!-- Confirm Password -->
            ' . HtmlForm::generateInput('confirm_password', 'password', $tenant->password, true) . '
        ';

        return view('tenants.tenant_create', [
            'userForm' => $userForm,
            'tenantForm' => Tenant::htmlForm(new Tenant()),
        ]);
    }

    public function create() {
        $user = auth()->user();

        return view('tenants.create', [
            'tenantForm' => Tenant::htmlForm(new Tenant()),
        ]);
    }

    public function user() {
        $user = auth()->user();

        $tenant = new Tenant();
        $tenant->email = $user->email;

        list($tenant->first_name, $tenant->last_name) = explode(' ', $user->name, 2);

        return view('tenants.user', [
            'tenantForm' => Tenant::htmlForm($tenant),
            'tenant' => $tenant,

        ]);
    }

    public function store(Request $request)
    {

        // Create a new Tenant instance nemo
        $tenant = new Tenant();

        $tenant->first_name = $request->input('first_name');
        $tenant->middle_name = $request->input('middle_name');
        $tenant->last_name = $request->input('last_name');

        $tenant->id_number = $request->input('id_number');
        $tenant->passport_number = $request->input('passport_number');

        $tenant->date_of_birth = $request->input('date_of_birth');//format
        $tenant->email = $request->input('email');
        $tenant->contact_number = $request->input('contact_number');
        $tenant->work_number = $request->input('work_number');

        $tenant->gender_id = $request->input('gender_id');
        $tenant->marital_status_id = $request->input('marital_status_id');
        $tenant->ethnicity_id = $request->input('ethnicity_id');

        $tenant->language_id = $request->input('language_id');

        $tenant->address_line_1 = $request->input('address_line_1');
        $tenant->address_line_2 = $request->input('address_line_2');

        $tenant->suburb_id = $request->input('suburb_id');
        $tenant->city_id = $request->input('city_id');
        $tenant->province_id = $request->input('province_id');
        $tenant->country_id = $request->input('country_id');

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

        if ($request->input('user_id'))
        {
            $tenant->user_id = $request->input('user_id');

            // Save the tenant to the database
            $tenant->save();

            return redirect()->back();

        }
        else {
            // Save the tenant to the database
            $tenant->save();

            $user = auth()->user();

            $team_tenant = new TeamTenant();

            $team_tenant->active = 1;
            $team_tenant->team_id = $user->current_team_id;
            $team_tenant->tenant_id = $tenant->id;

            $team_tenant->save();

            return redirect()->route('tenants.index')->with('success', 'Tenant created successfully!');

        }


    }


    public function show(Tenant $tenant) {

        return view('tenants.show', [
            'tenant' => $tenant,
            'tenantShow' => Tenant::htmlShow($tenant),
        ]);
    }

    public function edit(Tenant $tenant) {

        return view('tenants.edit', [
            'tenant' => $tenant,
            'tenantForm' => Tenant::htmlForm($tenant),

            'genders' => LookUp::GENDERS_OPTIONS,
            'marital_statuses' => LookUp::MARITAL_STATUS,
            'ethnicities' => LookUp::ETHNICITY_TYPES,
            'languages' => LookUp::LANGAUGE_TYPES,
        ]);
    }

//    public function approveApplication(Tenant $tenant) {
//        $user = auth()->user();
//
//
//        $tenant->updated_by = intval($user->id);
//
//        $tenant->update();
//
//        return redirect()->route('tenants.index')->with('success', 'Application approved successfully!');
//    }

    public function update(Request $request, Tenant $tenant)
    {
        $user = auth()->user();

        // Update tenant details
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
        $tenant->employer = $request->input('employer');
        $tenant->monthly_income = $request->input('monthly_income');
        $tenant->number_of_occupancies = $request->input('number_of_occupancies');
        $tenant->has_pets = $request->input('has_pets');
        $tenant->special_requirements = $request->input('special_requirements');
        $tenant->emergency_name = $request->input('emergency_name');
        $tenant->emergency_relationship = $request->input('emergency_relationship');
        $tenant->emergency_number = $request->input('emergency_number');

        $tenant->active = true;

        $tenant->team_id = intval($user->current_team_id);

        // Handle file uploads
        $filePrefix = 'tenants/' . uniqid('tenant_');

        // ID Document
        if ($request->hasFile('id_document_path')) {
            $this->deleteFileAndDirectory($tenant->id_document_file_name);
            $tenant->id_document_file_name = $request->file('id_document_path')->store($filePrefix, 'public');
        }

        // Bank Statements
        if ($request->hasFile('bank_statements_path')) {
            $this->deleteFileAndDirectory($tenant->bank_statements_file_name);
            $tenant->bank_statements_file_name = $request->file('bank_statements_path')->store($filePrefix, 'public');
        }

        // Proof of Income
        if ($request->hasFile('proof_of_income_path')) {
            $this->deleteFileAndDirectory($tenant->proof_of_income_file_name);
            $tenant->proof_of_income_file_name = $request->file('proof_of_income_path')->store($filePrefix, 'public');
        }

        // Save the tenant
        $tenant->save();

        return redirect()->route('tenants.index')->with('success', 'Tenant updated successfully!');
    }

    /**
     * Delete a file and its directory if the directory is empty.
     */
    private function deleteFileAndDirectory($filePath)
    {
        if ($filePath) {
            // Delete the file
            Storage::disk('public')->delete($filePath);

            // Get the directory from the file path
            $directory = dirname($filePath);

            // Check if the directory is empty and delete it
            if (Storage::disk('public')->exists($directory) && empty(Storage::disk('public')->allFiles($directory))) {
                Storage::disk('public')->deleteDirectory($directory);
            }
        }
    }

    public function destroy(Tenant $tenant) {

        $this->deleteFileAndDirectory($tenant->id_document_file_name);
        $this->deleteFileAndDirectory($tenant->bank_statements_file_name);
        $this->deleteFileAndDirectory($tenant->proof_of_income_file_name);

        $tenant->delete();
        return redirect()->route('tenants.index')->with('success', 'Tenant deleted successfully!');
    }

}

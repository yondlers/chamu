<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Helpers\HtmlForm;
use App\Helpers\LeaseTemplates;
use App\Http\Requests\LeaseTemplateRequest;
use App\Models\LeaseTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Helpers\HtmlError;


class LeaseTemplateController extends Controller
{
    //
    public function index() {
        $user = auth()->user();
        $team = $user->team;

        $lease_templates = LeaseTemplate::where('team_id', $user->current_team_id)->get();

        return view('lease_templates.index', [
            'lease_templates' => $lease_templates,
        ]);
    }

    public function create() {

        $lease_template = new LeaseTemplate();

        return view('lease_templates.create', [
            'leaseTemplateForm' => LeaseTemplate::htmlForm($lease_template),
        ]);
    }

    public function store(Request $request) {
        $user = auth()->user();

//        $filePrefix = null;
//        $leasePath = null;

        $template = $request['template_html'];

//        if ($request->hasFile('lease_template_file')) {
            // Generate a unique file prefix for document storage
//            $filePrefix = 'lease_template/' . uniqid('lease_');
//            $leasePath = $request->file('lease_template_file')->store($filePrefix, 'public');
//            $template = null;
//        }

        $lease_template = new LeaseTemplate();

        $lease_template->name = $request['name'];
        $lease_template->slug = $request['slug'];
        $lease_template->template_html = $template;
        $lease_template->active = true;
//        $lease_template->file_prefix = $filePrefix;
//        $lease_template->lease_template_file = $leasePath;
        $lease_template->team_id = intval($user->current_team_id);

        $lease_template->save();

        AuditLog::log('Captured Lease Template', 'Lease Template: ' . $request['name'] . ', with slug ' . $request['slug']);

        return redirect()->route('lease_templates.index')->with('success', 'Lease template created successfully!');
    }

    public function show(LeaseTemplate $lease_template) {

        return view('lease_templates.show', [
            'lease_template' => $lease_template,
            'leaseTemplateHtml' => LeaseTemplate::htmlShow($lease_template),
        ]);
    }

    public function edit(LeaseTemplate $lease_template) {

        return view('lease_templates.edit', [
            'leaseTemplate' => $lease_template,
            'leaseTemplateForm' => LeaseTemplate::htmlForm($lease_template)
        ]);

    }

    public function update(Request $request, LeaseTemplate $lease_template)
    {
        $user = auth()->user();

        $lease_template->name = $request['name'];
        $lease_template->slug = $request['slug'];
        $lease_template->template_html = $request['template_html'];
        $lease_template->active = true;

        //  Document
//        if ($request->hasFile('lease_template_file')) {
//            $filePrefix = $lease_template->file_prefix;
//
//            $this->deleteFileAndDirectory($lease_template->lease_template_file);
//            $lease_template->lease_template_file = $request->file('lease_template_file')->store($filePrefix, 'public');
//
//            $lease_template->template_html = null;
//        } else if ($lease_template->lease_template_file) {
//            $lease_template->template_html = null;
//        }

        $lease_template->update();

        return redirect()->route('lease_templates.index')->with('success', 'Lease template updated successfully!');
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

    public function download(LeaseTemplate $lease_template) {

        $template = $lease_template->template_html;

        $data = [
            '[[TODAY_DATE]]' => date('Y-m-d'),
            '[[LANDLORD_NAME]]' => 'John Doe',
            '[[LANDLORD_ADDRESS]]' => '123 Main Street, Johannesburg, South Africa',
            '[[TENANT_NAME]]' => 'Jane Smith',
            '[[TENANT_ADDRESS]]' => '456 Elm Street, Cape Town, South Africa',
        ];

        foreach ($data as $placeholder => $value) {
            $template = str_replace($placeholder, $value, $template);
        }

        // Generate PDF
        $pdf = Pdf::loadHTML($template);

        // Stream the PDF to the browser for download
        return $pdf->download('lease_agreement.pdf');
    }

    public function destroy(LeaseTemplate $leaseTemplate) {

    }
}

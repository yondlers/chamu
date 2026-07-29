<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeToChamu;
use App\Models\AuditLog;
use App\Models\Bursary;
use App\Models\BursaryDocumentRequirement;
use App\Models\SiteVisit;
use App\Models\SocialPost;
use App\Models\SocialPostResponse;
use App\Models\User;
use App\Models\UserApplicationDocument;
use App\Models\UserApplicationProfile;
use App\Models\UserSubjectResult;
use App\Support\Social\FacebookGraph;
use App\Support\Social\InstagramGraph;
use App\Support\Social\LinkedInGraph;
use App\Support\Social\SocialImageStorage;
use App\Support\Social\SocialMediaConfig;
use App\Support\Social\ThreadsGraph;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('bursary_applications'), 404);

        $applications = DB::table('bursary_applications')
            ->leftJoin('bursaries', 'bursaries.id', '=', 'bursary_applications.bursary_id')
            ->leftJoin('companies', 'companies.id', '=', 'bursaries.company_id')
            ->where('bursary_applications.user_id', $request->user()->id)
            ->select(
                'bursary_applications.id',
                'bursary_applications.status',
                'bursary_applications.delivery_type',
                'bursary_applications.provider_email',
                'bursary_applications.provider_postal_address',
                'bursary_applications.applicant_email',
                'bursary_applications.submitted_at',
                'bursary_applications.receipt_sent_at',
                'bursary_applications.created_at',
                'bursaries.id as bursary_id',
                'bursaries.title as bursary_title',
                'bursaries.source_url',
                'bursaries.closing_date_label',
                'companies.name as company_name',
            )
            ->selectSub(function ($query) {
                $query
                    ->from('bursary_application_documents')
                    ->selectRaw('count(*)')
                    ->whereColumn('bursary_application_documents.bursary_application_id', 'bursary_applications.id');
            }, 'documents_count')
            ->latest('bursary_applications.created_at')
            ->paginate(10)
            ->withQueryString();

        return view('applications.index', [
            'applications' => $applications,
        ]);
            
    }

    public function postalPack(Request $request, int $application)
    {
        abort_unless(Schema::hasTable('bursary_applications') && Schema::hasTable('bursary_application_documents'), 404);

        $application = DB::table('bursary_applications')
            ->leftJoin('bursaries', 'bursaries.id', '=', 'bursary_applications.bursary_id')
            ->leftJoin('companies', 'companies.id', '=', 'bursaries.company_id')
            ->where('bursary_applications.id', $application)
            ->where('bursary_applications.user_id', $request->user()->id)
            ->where('bursary_applications.delivery_type', 'postal')
            ->select(
                'bursary_applications.*',
                'bursaries.title as bursary_title',
                'bursaries.source_url',
                'bursaries.closing_date_label',
                'companies.name as company_name',
            )
            ->first();

        abort_if($application === null, 404);

        $documents = DB::table('bursary_application_documents')
            ->leftJoin('bursary_document_requirements', 'bursary_document_requirements.id', '=', 'bursary_application_documents.bursary_document_requirement_id')
            ->where('bursary_application_documents.bursary_application_id', $application->id)
            ->select(
                'bursary_application_documents.*',
                'bursary_document_requirements.label as requirement_label',
            )
            ->orderBy('bursary_application_documents.id')
            ->get();

        return view('applications.postal-pack', [
            'application' => $application,
            'documents' => $documents,
        ]);
            
    }
}

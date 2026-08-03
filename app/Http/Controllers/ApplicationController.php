<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeToChamu;
use App\Models\AuditLog;
use App\Models\Bursary;
use App\Models\BursaryApplication;
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

        $applications = BursaryApplication::with([
            'bursary.company',
            'emailLogs' => fn ($emailLogs) => $emailLogs
                ->whereIn('email_type', ['bursary_application_provider', 'bursary_application_receipt'])
                ->latest(),
        ])
            ->withCount('documents')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('applications.index', [
            'applications' => $applications,
        ]);
            
    }

    public function postalPack(Request $request, int $application)
    {
        abort_unless(Schema::hasTable('bursary_applications') && Schema::hasTable('bursary_application_documents'), 404);

        $application = BursaryApplication::with(['bursary.company', 'documents.requirement'])
            ->where('id', $application)
            ->where('user_id', $request->user()->id)
            ->where('delivery_type', 'postal')
            ->first();

        abort_if($application === null, 404);

        return view('applications.postal-pack', [
            'application' => $application,
            'documents' => $application->documents->sortBy('id')->values(),
        ]);
            
    }
}

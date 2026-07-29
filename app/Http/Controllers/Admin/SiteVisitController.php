<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

class SiteVisitController extends Controller
{
    public function index()
    {
        $siteVisits = SiteVisit::with('user')
            ->latest('visited_at')
            ->paginate(50);

        return view('admin.site-visits.index', [
            'siteVisits' => $siteVisits,
            'totalVisits' => SiteVisit::count(),
            'guestVisits' => SiteVisit::whereNull('user_id')->count(),
            'userVisits' => SiteVisit::whereNotNull('user_id')->count(),
        ]);
            
    }

    public function show(SiteVisit $siteVisit)
    {
        $siteVisit->load('user');

        return view('admin.site-visits.show', [
            'siteVisit' => $siteVisit,
        ]);
            
    }
}

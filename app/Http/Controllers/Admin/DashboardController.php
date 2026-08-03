<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeToChamu;
use App\Models\AuditLog;
use App\Models\Bursary;
use App\Models\BursaryDocumentRequirement;
use App\Models\EmailLog;
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

class DashboardController extends Controller
{
    public function index()
    {
        $socialChannels = SocialMediaConfig::adminPlatforms();

        $activeWindow = now()->subMinutes(10);
        $activeVisits = SiteVisit::with('user')
            ->where('visited_at', '>=', $activeWindow)
            ->latest('visited_at')
            ->limit(500)
            ->get()
            ->unique(fn (SiteVisit $visit) => $visit->session_id ?: $visit->ip_address.'|'.$visit->user_agent)
            ->values();
        $activeVisitorCount = $activeVisits->count();
        $activeVisits = $activeVisits->take(5)->values();
        $recentVisits = SiteVisit::with('user')
            ->latest('visited_at')
            ->limit(5)
            ->get();
        $markAuditLogs = AuditLog::with('user')
            ->latest()
            ->limit(5)
            ->get();
        $totalAccounts = User::count();
        $totalVisits = SiteVisit::count();
        $totalAuditLogs = AuditLog::count();
        $totalEmailLogs = EmailLog::count();
        $totalSocialChannels = count($socialChannels);
        $accounts = User::query()
            ->with(['userType', 'curriculum', 'grade', 'province'])
            ->withCount([
                'userSubjectPreferences as subjects_count',
                'userSubjectResults as marks_count' => fn ($query) => $query->whereNotNull('mark'),
            ])
            ->withMax('siteVisits as last_seen_at', 'visited_at')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.index', [
            'activeWindow' => $activeWindow,
            'activeVisits' => $activeVisits,
            'activeVisitorCount' => $activeVisitorCount,
            'recentVisits' => $recentVisits,
            'markAuditLogs' => $markAuditLogs,
            'totalAccounts' => $totalAccounts,
            'totalVisits' => $totalVisits,
            'totalAuditLogs' => $totalAuditLogs,
            'totalEmailLogs' => $totalEmailLogs,
            'totalSocialChannels' => $totalSocialChannels,
            'socialChannels' => $socialChannels,
            'accounts' => $accounts,
        ]);
            
    }
}

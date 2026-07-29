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

class ActivityLogController extends Controller
{
    public function index()
    {
        $siteVisitItems = SiteVisit::with('user')
            ->latest('visited_at')
            ->limit(80)
            ->get()
            ->map(function (SiteVisit $visit) {
                return [
                    'type' => 'Site visit',
                    'icon' => 'mouse-pointer-click',
                    'title' => $visit->pageLabel(),
                    'actor' => $visit->user?->name ?? 'Guest visitor',
                    'context' => trim(($visit->method ?? 'GET').' '.($visit->route_name ?? 'No route')),
                    'meta' => ($visit->device_type ?? 'Unknown device').' - '.($visit->browser ?? 'Unknown browser'),
                    'occurred_at' => $visit->visited_at,
                    'href' => route('admin.site-visits.show', $visit),
                    'tone' => 'border-sky-200 bg-sky-50 text-sky-700',
                ];
            });

        $auditItems = AuditLog::with('user')
            ->latest()
            ->limit(80)
            ->get()
            ->map(function (AuditLog $log) {
                return [
                    'type' => 'Audit',
                    'icon' => 'file-search',
                    'title' => $log->name,
                    'actor' => $log->user?->name ?? 'System',
                    'context' => $log->event ?? 'No event key',
                    'meta' => class_basename($log->auditable_type ?? '') ?: 'No auditable model',
                    'occurred_at' => $log->created_at,
                    'href' => route('admin.audit-logs.show', $log),
                    'tone' => 'border-violet-200 bg-violet-50 text-violet-700',
                ];
            });

        $activityLogs = $siteVisitItems
            ->merge($auditItems)
            ->sortByDesc(fn (array $item) => $item['occurred_at']?->timestamp ?? 0)
            ->take(100)
            ->values();

        return view('admin.activity-logs.index', [
            'activityLogs' => $activityLogs,
            'totalActivities' => SiteVisit::count() + AuditLog::count(),
            'totalVisits' => SiteVisit::count(),
            'totalAuditLogs' => AuditLog::count(),
            'activeVisitors' => SiteVisit::where('visited_at', '>=', now()->subMinutes(10))
                ->get()
                ->unique(fn (SiteVisit $visit) => $visit->session_id ?: $visit->ip_address.'|'.$visit->user_agent)
                ->count(),
        ]);
            
    }
}

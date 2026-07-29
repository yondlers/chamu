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

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $attempts = DB::table('exam_sessions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
            ->where('exam_sessions.user_id', $request->user()->id)
            ->select(
                'exam_sessions.id',
                'exam_sessions.title',
                'exam_sessions.quiz_type',
                'exam_sessions.source',
                'exam_sessions.score',
                'exam_sessions.total_marks',
                'exam_sessions.percentage',
                'exam_sessions.started_at',
                'exam_sessions.completed_at',
                'exam_sessions.updated_at',
                'subjects.name as subject_name',
            )
            ->latest('exam_sessions.updated_at')
            ->get();

        $completedAttempts = $attempts->filter(fn ($attempt) => $attempt->completed_at !== null);

        return view('progress.index', [
            'attempts' => $attempts,
            'completedAttempts' => $completedAttempts,
            'averagePercentage' => $completedAttempts->isNotEmpty()
                ? round($completedAttempts->avg(fn ($attempt) => (float) $attempt->percentage), 1)
                : null,
        ]);
            
    }
}

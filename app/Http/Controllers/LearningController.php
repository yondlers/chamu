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

class LearningController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $curriculums = collect();
        $allGrades = collect();
        $allSubjects = collect();
        $papers = collect();
        $pendingQuizzes = collect();

        if (Schema::hasTable('curriculums')) {
            $curriculums = DB::table('curriculums')
                ->select('id', 'name', 'abbreviation')
                ->when(Schema::hasColumn('curriculums', 'is_live'), fn ($query) => $query->where('is_live', true))
                ->orderBy('abbreviation')
                ->get();
        }

        $defaultCurriculum = $user !== null
            ? $curriculums->firstWhere('id', $user->curriculum_id)
            : ($curriculums->firstWhere('abbreviation', 'CAPS') ?? $curriculums->first());

        if (Schema::hasTable('grades')) {
            $allGrades = DB::table('grades')
                ->select('id', 'curriculum_id', 'name', 'sort_order')
                ->orderBy('sort_order')
                ->get();
        }

        $grades = $defaultCurriculum === null
            ? collect()
            : $allGrades->where('curriculum_id', $defaultCurriculum->id)->values();

        $defaultGrade = $user !== null && $user->grade_id !== null
            ? $grades->firstWhere('id', $user->grade_id)
            : ($grades->firstWhere('name', 'Grade 12') ?? $grades->first());

        if (Schema::hasTable('subjects')) {
            $allSubjects = DB::table('subjects')
                ->select('id', 'curriculum_id', 'grade_id', 'name', 'code', 'abbreviation', 'colour', 'icon', 'sort_order')
                ->when(Schema::hasColumn('subjects', 'is_live'), fn ($query) => $query->where('is_live', true))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        $subjects = collect();

        if ($defaultCurriculum !== null && $defaultGrade !== null) {
            $subjects = $allSubjects
                ->where('curriculum_id', $defaultCurriculum->id)
                ->where('grade_id', $defaultGrade->id)
                ->values();

            if ($user !== null && Schema::hasTable('user_subject_preferences')) {
                $preferredSubjectIds = DB::table('user_subject_preferences')
                    ->where('user_id', $user->id)
                    ->where('grade_id', $defaultGrade->id)
                    ->pluck('subject_id')
                    ->map(fn ($id) => (int) $id);

                if ($preferredSubjectIds->isNotEmpty()) {
                    $subjects = $subjects
                        ->whereIn('id', $preferredSubjectIds->all())
                        ->values();
                }
            }
        }

        if (Schema::hasTable('papers')) {
            $papers = DB::table('papers')
                ->select('id', 'number')
                ->orderBy('number')
                ->get();
        }

        $stats = [
            'curriculums' => $curriculums->count(),
            'grades' => $grades->count(),
            'subjects' => $subjects->count(),
            'papers' => $papers->count(),
        ];

        if ($user !== null && Schema::hasTable('exam_sessions')) {
            $pendingQuizzes = DB::table('exam_sessions')
                ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
                ->where('exam_sessions.user_id', $user->id)
                ->whereNull('exam_sessions.completed_at')
                ->select(
                    'exam_sessions.id',
                    'exam_sessions.title',
                    'exam_sessions.quiz_type',
                    'exam_sessions.source',
                    'exam_sessions.started_at',
                    'subjects.name as subject_name',
                )
                ->latest('exam_sessions.updated_at')
                ->limit(3)
                ->get();
        }

        return view('welcome', [
            'curriculums' => $curriculums,
            'grades' => $grades,
            'allGrades' => $allGrades,
            'subjects' => $subjects,
            'allSubjects' => $allSubjects,
            'papers' => $papers,
            'defaultCurriculum' => $defaultCurriculum,
            'defaultGrade' => $defaultGrade,
            'stats' => $stats,
            'pendingQuizzes' => $pendingQuizzes,
        ]);
    }
}

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

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $paperId = $request->input('paper_id', 'all');
        $mode = $request->input('mode', 'all');

        $curriculumId = $user?->curriculum_id ?? $request->integer('curriculum_id');
        $gradeId = $user?->grade_id ?? $request->integer('grade_id');
        $subjectId = $request->integer('subject_id');

        $subject = null;
        $paper = null;
        $questions = collect();
        $subQuestions = collect();
        $sources = collect();
        $availableSubjects = collect();
        $contentStats = [
            'subjects' => 0,
            'questions' => 0,
            'papers' => 0,
            'curriculums' => 0,
        ];

        if ($subjectId !== 0 && Schema::hasTable('subjects')) {
            $subject = DB::table('subjects')
                ->leftJoin('grades', 'grades.id', '=', 'subjects.grade_id')
                ->leftJoin('curriculums', 'curriculums.id', '=', 'subjects.curriculum_id')
                ->where('subjects.id', $subjectId)
                ->when($curriculumId !== 0, fn ($query) => $query->where('subjects.curriculum_id', $curriculumId))
                ->when($gradeId !== 0, fn ($query) => $query->where('subjects.grade_id', $gradeId))
                ->select(
                    'subjects.id',
                    'subjects.name',
                    'subjects.code',
                    'subjects.colour',
                    'subjects.icon',
                    'grades.name as grade_name',
                    'curriculums.abbreviation as curriculum_abbreviation',
                )
                ->first();

            if ($user !== null && $subject !== null && Schema::hasTable('user_subject_preferences')) {
                $preferredSubjectIds = DB::table('user_subject_preferences')
                    ->where('user_id', $user->id)
                    ->where('grade_id', $gradeId)
                    ->pluck('subject_id')
                    ->map(fn ($id) => (int) $id);

                if ($preferredSubjectIds->isNotEmpty() && ! $preferredSubjectIds->contains((int) $subject->id)) {
                    $subject = null;
                }
            }
        }

        if ($paperId !== 'all' && Schema::hasTable('papers')) {
            $paper = DB::table('papers')
                ->where('id', (int) $paperId)
                ->first(['id', 'number']);
        }

        if ($subject !== null && Schema::hasTable('questions')) {
            $questions = DB::table('questions')
                ->leftJoin('topics', 'topics.id', '=', 'questions.topic_id')
                ->leftJoin('papers', 'papers.id', '=', 'questions.paper_id')
                ->where('questions.subject_id', $subject->id)
                ->when($paperId !== 'all', fn ($query) => $query->where('questions.paper_id', (int) $paperId))
                ->select(
                    'questions.id',
                    'questions.question_number',
                    'questions.title',
                    'questions.instructions',
                    'questions.image',
                    'questions.hint',
                    'questions.source',
                    'questions.difficulty',
                    'questions.sort_order',
                    'topics.name as topic_name',
                    'papers.number as paper_number',
                )
                ->orderBy('questions.sort_order')
                ->orderBy('questions.question_number')
                ->get();

            if ($questions->isNotEmpty() && Schema::hasTable('sub_questions')) {
                $subQuestions = DB::table('sub_questions')
                    ->leftJoin('answers', 'answers.id', '=', 'sub_questions.answer_id')
                    ->whereIn('sub_questions.question_id', $questions->pluck('id'))
                    ->select(
                        'sub_questions.question_id',
                        'sub_questions.sub_question_number',
                        'sub_questions.question',
                        'sub_questions.hint',
                        'sub_questions.question_type',
                        'sub_questions.answer_type',
                        'sub_questions.options',
                        'sub_questions.sort_order',
                        'answers.correct_answer',
                        'answers.accepted_answers',
                        'answers.explanation',
                    )
                    ->orderBy('sub_questions.sort_order')
                    ->orderBy('sub_questions.sub_question_number')
                    ->get()
                    ->groupBy('question_id');
            }

            $sources = $questions
                ->pluck('source')
                ->filter()
                ->unique()
                ->values();
        }

        if (Schema::hasTable('subjects')) {
            $subjectQuery = DB::table('subjects')
                ->leftJoin('grades', 'grades.id', '=', 'subjects.grade_id')
                ->leftJoin('curriculums', 'curriculums.id', '=', 'subjects.curriculum_id')
                ->when(Schema::hasColumn('subjects', 'is_live'), fn ($query) => $query->where('subjects.is_live', true));

            if (Schema::hasTable('questions')) {
                $subjectQuery
                    ->leftJoin('questions', 'questions.subject_id', '=', 'subjects.id')
                    ->select(
                        'subjects.id',
                        'subjects.name',
                        'subjects.code',
                        'subjects.abbreviation',
                        'subjects.colour',
                        'subjects.icon',
                        'grades.name as grade_name',
                        'curriculums.abbreviation as curriculum_abbreviation',
                        DB::raw('COUNT(questions.id) as question_count'),
                    )
                    ->groupBy(
                        'subjects.id',
                        'subjects.name',
                        'subjects.code',
                        'subjects.abbreviation',
                        'subjects.colour',
                        'subjects.icon',
                        'grades.name',
                        'curriculums.abbreviation',
                    )
                    ->orderByDesc('question_count');
            } else {
                $subjectQuery
                    ->select(
                        'subjects.id',
                        'subjects.name',
                        'subjects.code',
                        'subjects.abbreviation',
                        'subjects.colour',
                        'subjects.icon',
                        'grades.name as grade_name',
                        'curriculums.abbreviation as curriculum_abbreviation',
                        DB::raw('0 as question_count'),
                    )
                    ->orderBy('subjects.name');
            }

            $availableSubjects = $subjectQuery
                ->orderBy('subjects.name')
                ->limit(12)
                ->get();

            $contentStats['subjects'] = DB::table('subjects')
                ->when(Schema::hasColumn('subjects', 'is_live'), fn ($query) => $query->where('is_live', true))
                ->count();
        }

        if (Schema::hasTable('questions')) {
            $contentStats['questions'] = DB::table('questions')->count();
        }

        if (Schema::hasTable('papers')) {
            $contentStats['papers'] = DB::table('papers')->count();
        }

        if (Schema::hasTable('curriculums')) {
            $contentStats['curriculums'] = DB::table('curriculums')->count();
        }

        return view('content.index', [
            'subject' => $subject,
            'paper' => $paper,
            'paperId' => $paperId,
            'mode' => $mode,
            'questions' => $questions,
            'subQuestions' => $subQuestions,
            'sources' => $sources,
            'availableSubjects' => $availableSubjects,
            'contentStats' => $contentStats,
        ]);
    }
}

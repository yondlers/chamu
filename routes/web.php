<?php

use App\Http\Controllers\BursaryApplicationController;
use App\Http\Controllers\Public\QualificationController as PublicQualificationController;
use App\Http\Controllers\Public\UniversityController as PublicUniversityController;
use App\Http\Controllers\SitemapController;
use App\Models\AuditLog;
use App\Models\Bursary;
use App\Models\BursaryDocumentRequirement;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\UserSubjectResult;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

Route::get('/', fn () => redirect()->route('aps.index'))->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/learn', function () {
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
})->name('learn.index');

Route::get('/content', function (Request $request) {
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

    return view('content.index', [
        'subject' => $subject,
        'paper' => $paper,
        'paperId' => $paperId,
        'mode' => $mode,
        'questions' => $questions,
        'subQuestions' => $subQuestions,
        'sources' => $sources,
    ]);
})->name('content.index');

Route::middleware('auth')->group(function () {
    Route::get('/practice/setup', function (Request $request) {
        $user = $request->user();
        $subjectId = $request->integer('subject_id');
        $paperId = $request->input('paper_id', 'all');
        $topicId = $request->input('topic_id', 'all');
        $quizType = $request->input('quiz_type', 'random');
        $source = $request->input('source');

        $subject = DB::table('subjects')
            ->where('id', $subjectId)
            ->where('curriculum_id', $user->curriculum_id)
            ->where('grade_id', $user->grade_id)
            ->first(['id', 'name', 'curriculum_id', 'grade_id']);

        if ($subject === null) {
            return redirect('/')->withErrors(['subject_id' => 'Choose one of your subjects.']);
        }

        $papers = DB::table('papers')->orderBy('number')->get(['id', 'number']);
        $topics = DB::table('topics')
            ->where('subject_id', $subject->id)
            ->orderBy('name')
            ->get(['id', 'name', 'paper_id']);

        $questionBase = fn () => DB::table('questions')
            ->where('subject_id', $subject->id)
            ->when($paperId !== 'all', fn ($query) => $query->where('paper_id', (int) $paperId))
            ->when($topicId !== 'all', fn ($query) => $query->where('topic_id', (int) $topicId));

        $availableQuestionCount = $questionBase()->count();
        $sources = $questionBase()
            ->select('source')
            ->whereNotNull('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        $sourceQuestionCount = $source
            ? $questionBase()->where('source', $source)->count()
            : 0;

        $pastQuestionCount = DB::table('past_paper_questions')
            ->where('subject_id', $subject->id)
            ->when($paperId !== 'all', fn ($query) => $query->where('paper_id', (int) $paperId))
            ->when($topicId !== 'all', fn ($query) => $query->where('topic_id', (int) $topicId))
            ->count();

        $selectedAvailableCount = match ($quizType) {
            'source' => $sourceQuestionCount,
            'past' => $pastQuestionCount,
            default => $availableQuestionCount,
        };

        return view('practice.setup', [
            'subject' => $subject,
            'papers' => $papers,
            'topics' => $topics,
            'sources' => $sources,
            'paperId' => $paperId,
            'topicId' => $topicId,
            'quizType' => $quizType,
            'source' => $source,
            'availableQuestionCount' => $availableQuestionCount,
            'sourceQuestionCount' => $sourceQuestionCount,
            'pastQuestionCount' => $pastQuestionCount,
            'selectedAvailableCount' => $selectedAvailableCount,
        ]);
    })->name('practice.setup');

    Route::post('/practice', function (Request $request) {
        $user = $request->user();
        $data = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'paper_id' => ['nullable'],
            'topic_id' => ['nullable'],
            'quiz_type' => ['required', 'in:random,source,past'],
            'source' => ['nullable', 'string'],
            'question_count' => ['required', 'integer', 'min:1', 'max:100'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
        ]);

        $subject = DB::table('subjects')
            ->where('id', $data['subject_id'])
            ->where('curriculum_id', $user->curriculum_id)
            ->where('grade_id', $user->grade_id)
            ->first(['id', 'name', 'curriculum_id']);

        if ($subject === null) {
            return back()->withErrors(['subject_id' => 'Choose one of your subjects.']);
        }

        $query = DB::table('questions')
            ->where('subject_id', $subject->id)
            ->when(($data['paper_id'] ?? 'all') !== 'all', fn ($query) => $query->where('paper_id', (int) $data['paper_id']))
            ->when(($data['topic_id'] ?? 'all') !== 'all', fn ($query) => $query->where('topic_id', (int) $data['topic_id']));

        if ($data['quiz_type'] === 'source') {
            $query->where('source', $data['source']);
        }

        $pastQuestions = collect();
        $questions = collect();

        if ($data['quiz_type'] === 'past') {
            $pastQuestions = DB::table('past_paper_questions')
                ->where('subject_id', $subject->id)
                ->when(($data['paper_id'] ?? 'all') !== 'all', fn ($query) => $query->where('paper_id', (int) $data['paper_id']))
                ->when(($data['topic_id'] ?? 'all') !== 'all', fn ($query) => $query->where('topic_id', (int) $data['topic_id']))
                ->inRandomOrder()
                ->limit((int) $data['question_count'])
                ->get(['id', 'question_number', 'marks']);

            if ($pastQuestions->isEmpty()) {
                return back()->withInput()->withErrors(['subject_id' => 'No past-paper questions are available for that selection.']);
            }
        } else {
            $questions = $data['quiz_type'] === 'random'
                ? $query->inRandomOrder()->limit((int) $data['question_count'])->get(['id', 'question_number'])
                : $query->orderBy('sort_order')->orderBy('question_number')->limit((int) $data['question_count'])->get(['id', 'question_number']);

            if ($questions->isEmpty()) {
                return back()->withInput()->withErrors(['subject_id' => 'No questions are available for that selection.']);
            }
        }

        $title = match ($data['quiz_type']) {
            'source' => "{$subject->name} source practice",
            'past' => "{$subject->name} past questions",
            default => "{$subject->name} random practice",
        };

        $totalSubQuestions = $data['quiz_type'] === 'past'
            ? $pastQuestions->count()
            : DB::table('sub_questions')->whereIn('question_id', $questions->pluck('id'))->count();

        $sessionId = DB::table('exam_sessions')->insertGetId([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'curriculum_id' => $subject->curriculum_id,
            'title' => $title,
            'mode' => 'practice',
            'paper_type' => ($data['paper_id'] ?? 'all') === 'all' ? 'all' : (string) $data['paper_id'],
            'quiz_type' => $data['quiz_type'],
            'source' => $data['quiz_type'] === 'source' ? $data['source'] : null,
            'time_limit_minutes' => $data['duration_minutes'] ?? null,
            'total_marks' => $totalSubQuestions,
            'randomize_questions' => $data['quiz_type'] === 'random',
            'show_answers_immediately' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($data['quiz_type'] === 'past') {
            foreach ($pastQuestions as $index => $question) {
                DB::table('exam_session_questions')->insert([
                    'exam_session_id' => $sessionId,
                    'past_paper_question_id' => $question->id,
                    'question_order' => $index + 1,
                    'marks' => $question->marks ?: 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            foreach ($questions as $index => $question) {
                DB::table('exam_session_questions')->insert([
                    'exam_session_id' => $sessionId,
                    'question_id' => $question->id,
                    'question_order' => $index + 1,
                    'marks' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('practice.show', $sessionId);
    })->name('practice.store');

    Route::get('/practice/{session}', function (Request $request, int $session) {
        $quiz = DB::table('exam_sessions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
            ->where('exam_sessions.id', $session)
            ->where('exam_sessions.user_id', $request->user()->id)
            ->select('exam_sessions.*', 'subjects.name as subject_name')
            ->first();

        abort_if($quiz === null, 404);

        if ($quiz->completed_at !== null) {
            return redirect()->route('practice.results', $quiz->id);
        }

        $questionCount = $quiz->quiz_type === 'past'
            ? DB::table('exam_session_questions')->where('exam_session_id', $quiz->id)->count()
            : DB::table('exam_session_questions')
                ->join('sub_questions', 'sub_questions.question_id', '=', 'exam_session_questions.question_id')
                ->where('exam_session_id', $quiz->id)
                ->count();

        return view('practice.show', [
            'quiz' => $quiz,
            'questionCount' => $questionCount,
        ]);
    })->name('practice.show');

    Route::post('/practice/{session}/begin', function (Request $request, int $session) {
        DB::table('exam_sessions')
            ->where('id', $session)
            ->where('user_id', $request->user()->id)
            ->whereNull('completed_at')
            ->update([
                'started_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()->route('practice.take', $session);
    })->name('practice.begin');

    Route::get('/practice/{session}/take', function (Request $request, int $session) {
        $quiz = DB::table('exam_sessions')
            ->where('id', $session)
            ->where('user_id', $request->user()->id)
            ->whereNull('completed_at')
            ->first();

        abort_if($quiz === null, 404);

        if ($quiz->quiz_type === 'past') {
            $subQuestion = DB::table('exam_session_questions')
                ->join('past_paper_questions', 'past_paper_questions.id', '=', 'exam_session_questions.past_paper_question_id')
                ->where('exam_session_questions.exam_session_id', $quiz->id)
                ->whereNull('exam_session_questions.selected_answer')
                ->select(
                    'past_paper_questions.question',
                    'past_paper_questions.answer as correct_answer',
                    'past_paper_questions.options',
                    'past_paper_questions.question_type',
                    'past_paper_questions.answer_type',
                    'past_paper_questions.question_number',
                    'exam_session_questions.id as session_question_id',
                    'exam_session_questions.question_order',
                )
                ->orderBy('exam_session_questions.question_order')
                ->first();

            if ($subQuestion !== null) {
                $subQuestion->id = null;
                $subQuestion->sub_question_number = $subQuestion->question_number;
                $subQuestion->instructions = null;
                $subQuestion->image = null;
                $subQuestion->accepted_answers = null;
                $subQuestion->explanation = null;
            }

            $totalSubQuestions = DB::table('exam_session_questions')
                ->where('exam_session_id', $quiz->id)
                ->count();

            $answeredCount = DB::table('exam_session_questions')
                ->where('exam_session_id', $quiz->id)
                ->whereNotNull('selected_answer')
                ->count();
        } else {
            $answeredSubQuestionIds = DB::table('exam_session_answers')
                ->where('exam_session_id', $quiz->id)
                ->whereNotNull('selected_answer')
                ->pluck('sub_question_id')
                ->filter()
                ->map(fn ($id) => (int) $id);

            $subQuestion = DB::table('exam_session_questions')
                ->join('questions', 'questions.id', '=', 'exam_session_questions.question_id')
                ->join('sub_questions', 'sub_questions.question_id', '=', 'questions.id')
                ->leftJoin('answers', 'answers.id', '=', 'sub_questions.answer_id')
                ->where('exam_session_questions.exam_session_id', $quiz->id)
                ->when($answeredSubQuestionIds->isNotEmpty(), fn ($query) => $query->whereNotIn('sub_questions.id', $answeredSubQuestionIds))
                ->select(
                    'sub_questions.*',
                    'answers.correct_answer',
                    'answers.accepted_answers',
                    'answers.explanation',
                    'questions.question_number',
                    'questions.instructions',
                    'questions.image',
                    'exam_session_questions.question_order',
                )
                ->orderBy('exam_session_questions.question_order')
                ->orderBy('sub_questions.sort_order')
                ->first();

            $totalSubQuestions = DB::table('exam_session_questions')
                ->join('sub_questions', 'sub_questions.question_id', '=', 'exam_session_questions.question_id')
                ->where('exam_session_questions.exam_session_id', $quiz->id)
                ->count();

            $answeredCount = $answeredSubQuestionIds->count();
        }

        if ($subQuestion === null) {
            return redirect('/')->with('status', 'Quiz already answered.');
        }

        $answerFields = [];

        if ($subQuestion->answer_type === 'json') {
            $acceptedAnswerMap = json_decode($subQuestion->accepted_answers ?? '[]', true);
            $correctAnswerMap = json_decode($subQuestion->correct_answer ?? '[]', true);
            $answerFields = collect(is_array($acceptedAnswerMap) ? array_keys($acceptedAnswerMap) : [])
                ->merge(is_array($correctAnswerMap) ? array_keys($correctAnswerMap) : [])
                ->unique()
                ->values()
                ->all();
        }

        return view('practice.take', [
            'quiz' => $quiz,
            'subQuestion' => $subQuestion,
            'answeredCount' => $answeredCount,
            'totalSubQuestions' => $totalSubQuestions,
            'answerFields' => $answerFields,
        ]);
    })->name('practice.take');

    Route::put('/practice/{session}', function (Request $request, int $session) {
        $quiz = DB::table('exam_sessions')
            ->where('id', $session)
            ->where('user_id', $request->user()->id)
            ->whereNull('completed_at')
            ->first();

        abort_if($quiz === null, 404);

        if ($request->isMethod('put')) {
            $data = $request->validate([
                'sub_question_id' => ['nullable', 'integer', 'exists:sub_questions,id'],
                'session_question_id' => ['nullable', 'integer', 'exists:exam_session_questions,id'],
                'answer' => ['required'],
            ], [
                'answer.required' => 'Answer this question before continuing.',
            ]);

            if (is_string($data['answer']) && trim($data['answer']) === '') {
                return back()
                    ->withInput()
                    ->withErrors(['answer' => 'Answer this question before continuing.']);
            }

            if ($quiz->quiz_type === 'past') {
                $selectedAnswer = is_array($data['answer']) ? json_encode($data['answer']) : trim((string) $data['answer']);

                DB::table('exam_session_questions')
                    ->where('id', $data['session_question_id'] ?? 0)
                    ->where('exam_session_id', $quiz->id)
                    ->update([
                        'selected_answer' => $selectedAnswer,
                        'updated_at' => now(),
                    ]);

                DB::table('exam_sessions')
                    ->where('id', $quiz->id)
                    ->update(['updated_at' => now()]);

                $totalSubQuestions = DB::table('exam_session_questions')
                    ->where('exam_session_id', $quiz->id)
                    ->count();

                $answeredCount = DB::table('exam_session_questions')
                    ->where('exam_session_id', $quiz->id)
                    ->whereNotNull('selected_answer')
                    ->count();

                if ($answeredCount < $totalSubQuestions) {
                    return redirect()->route('practice.take', $quiz->id);
                }
            } else {
                if (empty($data['sub_question_id'])) {
                    return back()->withInput()->withErrors(['answer' => 'Answer this question before continuing.']);
                }

                $subQuestion = DB::table('exam_session_questions')
                    ->join('sub_questions', 'sub_questions.question_id', '=', 'exam_session_questions.question_id')
                    ->where('exam_session_questions.exam_session_id', $quiz->id)
                    ->where('sub_questions.id', $data['sub_question_id'])
                    ->leftJoin('answers', 'answers.id', '=', 'sub_questions.answer_id')
                    ->select('sub_questions.id', 'sub_questions.question_id', 'sub_questions.answer_type', 'answers.correct_answer', 'answers.accepted_answers')
                    ->first();

                abort_if($subQuestion === null, 404);

                if ($subQuestion->answer_type === 'json') {
                $acceptedAnswerMap = json_decode($subQuestion->accepted_answers ?? '[]', true);
                $correctAnswerMap = json_decode($subQuestion->correct_answer ?? '[]', true);
                $answerFields = collect(is_array($acceptedAnswerMap) ? array_keys($acceptedAnswerMap) : [])
                    ->merge(is_array($correctAnswerMap) ? array_keys($correctAnswerMap) : [])
                    ->unique()
                    ->values();

                $answers = collect($request->input('answer', []))
                    ->map(fn ($value) => trim((string) $value))
                    ->only($answerFields)
                    ->all();

                $missingField = $answerFields->first(fn ($field) => ($answers[$field] ?? '') === '');
                if ($missingField !== null) {
                    return back()
                        ->withInput()
                        ->withErrors(['answer' => "Answer label {$missingField} before continuing."]);
                }

                $selectedAnswer = json_encode($answers);
                } else {
                if (! is_string($data['answer'])) {
                    return back()
                        ->withInput()
                        ->withErrors(['answer' => 'Answer this question before continuing.']);
                }

                $selectedAnswer = trim($data['answer']);
                }

                DB::table('exam_session_answers')->updateOrInsert(
                [
                    'exam_session_id' => $quiz->id,
                    'sub_question_id' => $subQuestion->id,
                ],
                [
                    'question_id' => $subQuestion->question_id,
                    'selected_answer' => $selectedAnswer,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                );

                DB::table('exam_sessions')
                ->where('id', $quiz->id)
                ->update(['updated_at' => now()]);

                $totalSubQuestions = DB::table('exam_session_questions')
                ->join('sub_questions', 'sub_questions.question_id', '=', 'exam_session_questions.question_id')
                ->where('exam_session_questions.exam_session_id', $quiz->id)
                ->count();

                $answeredCount = DB::table('exam_session_answers')
                ->where('exam_session_id', $quiz->id)
                ->whereNotNull('selected_answer')
                ->count();

                if ($answeredCount < $totalSubQuestions) {
                    return redirect()->route('practice.take', $quiz->id);
                }
            }
        }

        if ($quiz->quiz_type === 'past') {
            $rows = DB::table('exam_session_questions')
                ->join('past_paper_questions', 'past_paper_questions.id', '=', 'exam_session_questions.past_paper_question_id')
                ->where('exam_session_questions.exam_session_id', $quiz->id)
                ->select(
                    'past_paper_questions.question_number as sub_question_number',
                    'past_paper_questions.question',
                    'past_paper_questions.answer as correct_answer',
                    'exam_session_questions.selected_answer'
                )
                ->get();
        } else {
            $rows = DB::table('exam_session_questions')
            ->join('sub_questions', 'sub_questions.question_id', '=', 'exam_session_questions.question_id')
            ->leftJoin('answers', 'answers.id', '=', 'sub_questions.answer_id')
            ->leftJoin('exam_session_answers', function ($join) use ($quiz) {
                $join->on('exam_session_answers.sub_question_id', '=', 'sub_questions.id')
                    ->where('exam_session_answers.exam_session_id', '=', $quiz->id);
            })
            ->where('exam_session_questions.exam_session_id', $quiz->id)
            ->select(
                'sub_questions.question_id',
                'sub_questions.id as sub_question_id',
                'exam_session_answers.selected_answer',
                'sub_questions.sub_question_number',
                'sub_questions.question',
                'answers.correct_answer',
                'answers.accepted_answers',
            )
            ->get();
        }

        $normalize = fn ($value): string => strtolower(trim((string) $value));
        $formatAnswer = function ($value): string {
            $decoded = json_decode((string) $value, true);

            if (is_array($decoded) && ! array_is_list($decoded)) {
                return collect($decoded)
                    ->map(fn ($answer, $key) => "{$key}: {$answer}")
                    ->implode(', ');
            }

            return (string) $value;
        };
        $score = 0;
        $results = [];

        foreach ($rows as $row) {
            $accepted = json_decode($row->accepted_answers ?? '[]', true);
            $selected = json_decode($row->selected_answer ?? '', true);

            if (is_array($accepted) && ! array_is_list($accepted)) {
                $isCorrect = is_array($selected) && collect($accepted)->every(function ($acceptedValues, $key) use ($selected, $normalize) {
                    $values = is_array($acceptedValues) ? $acceptedValues : [$acceptedValues];

                    return array_key_exists($key, $selected)
                        && in_array($normalize($selected[$key]), array_map($normalize, $values), true);
                });
            } else {
                $acceptedValues = is_array($accepted) && array_is_list($accepted)
                    ? $accepted
                    : [$row->correct_answer];

                $isCorrect = in_array($normalize($row->selected_answer), array_map(fn ($value) => $normalize(is_scalar($value) ? (string) $value : json_encode($value)), $acceptedValues), true);
            }
            $score += $isCorrect ? 1 : 0;

            $results[] = [
                'number' => $row->sub_question_number,
                'question' => $row->question,
                'selected_answer' => $formatAnswer($row->selected_answer),
                'correct_answer' => $formatAnswer($row->correct_answer),
                'is_correct' => $isCorrect,
            ];
        }

        $total = max($rows->count(), 1);
        $percentage = round(($score / $total) * 100, 2);

        DB::table('exam_sessions')
            ->where('id', $quiz->id)
            ->update([
                'completed_at' => now(),
                'score' => $score,
                'total_marks' => $total,
                'percentage' => $percentage,
                'updated_at' => now(),
            ]);

        return redirect()->route('practice.results', $quiz->id);
    })->name('practice.update');

    Route::get('/practice/{session}/results', function (Request $request, int $session) {
        $quiz = DB::table('exam_sessions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
            ->where('exam_sessions.id', $session)
            ->where('exam_sessions.user_id', $request->user()->id)
            ->select('exam_sessions.*', 'subjects.name as subject_name')
            ->first();

        abort_if($quiz === null, 404);

        if ($quiz->completed_at === null) {
            return redirect()->route('practice.show', $quiz->id);
        }

        if ($quiz->quiz_type === 'past') {
            $rows = DB::table('exam_session_questions')
                ->join('past_paper_questions', 'past_paper_questions.id', '=', 'exam_session_questions.past_paper_question_id')
                ->where('exam_session_questions.exam_session_id', $quiz->id)
                ->select(
                    'past_paper_questions.id as sub_question_id',
                    'exam_session_questions.selected_answer',
                    'past_paper_questions.question_number as sub_question_number',
                    'past_paper_questions.question',
                    'past_paper_questions.answer as correct_answer',
                )
                ->orderBy('exam_session_questions.question_order')
                ->get();
        } else {
            $rows = DB::table('exam_session_questions')
                ->join('sub_questions', 'sub_questions.question_id', '=', 'exam_session_questions.question_id')
                ->leftJoin('answers', 'answers.id', '=', 'sub_questions.answer_id')
                ->leftJoin('exam_session_answers', function ($join) use ($quiz) {
                    $join->on('exam_session_answers.sub_question_id', '=', 'sub_questions.id')
                        ->where('exam_session_answers.exam_session_id', '=', $quiz->id);
                })
                ->where('exam_session_questions.exam_session_id', $quiz->id)
                ->select(
                    'sub_questions.id as sub_question_id',
                    'exam_session_answers.selected_answer',
                    'sub_questions.sub_question_number',
                    'sub_questions.question',
                    'answers.correct_answer',
                    'answers.accepted_answers',
                )
                ->orderBy('exam_session_questions.question_order')
                ->orderBy('sub_questions.sort_order')
                ->get();
        }

        $normalize = fn ($value): string => strtolower(trim((string) $value));
        $formatAnswer = function ($value): string {
            $decoded = json_decode((string) $value, true);

            if (is_array($decoded) && ! array_is_list($decoded)) {
                return collect($decoded)
                    ->map(fn ($answer, $key) => "{$key}: {$answer}")
                    ->implode(', ');
            }

            return (string) $value;
        };

        $results = $rows->map(function ($row) use ($normalize, $formatAnswer) {
            $accepted = json_decode($row->accepted_answers ?? '[]', true);
            $selected = json_decode($row->selected_answer ?? '', true);

            if (is_array($accepted) && ! array_is_list($accepted)) {
                $isCorrect = is_array($selected) && collect($accepted)->every(function ($acceptedValues, $key) use ($selected, $normalize) {
                    $values = is_array($acceptedValues) ? $acceptedValues : [$acceptedValues];

                    return array_key_exists($key, $selected)
                        && in_array($normalize($selected[$key]), array_map($normalize, $values), true);
                });
            } else {
                $acceptedValues = is_array($accepted) && array_is_list($accepted)
                    ? $accepted
                    : [$row->correct_answer];

                $isCorrect = in_array($normalize($row->selected_answer), array_map(fn ($value) => $normalize(is_scalar($value) ? (string) $value : json_encode($value)), $acceptedValues), true);
            }

            return [
                'number' => $row->sub_question_number,
                'question' => $row->question,
                'selected_answer' => $formatAnswer($row->selected_answer),
                'correct_answer' => $formatAnswer($row->correct_answer),
                'is_correct' => $isCorrect,
            ];
        });

        return view('practice.results', [
            'quiz' => $quiz,
            'results' => $results,
        ]);
    })->name('practice.results');

    Route::get('/progress', function (Request $request) {
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
    })->name('progress.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $login = $data['username'];
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([
            $loginField => $login,
            'password' => $data['password'],
        ], $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'These credentials do not match our records.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        $request->user()->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('aps.index'));
    })->name('login.store');

    Route::get('/register', function () {
        $publicUserTypes = [
            'pupil' => 'High school learner account for studying, practice, notes, and exams.',
            'student' => 'University or college student account for funding and study planning.',
        ];

        if (Schema::hasTable('user_types')) {
            DB::table('user_types')->insertOrIgnore(
                collect($publicUserTypes)
                    ->map(fn ($description, $name) => [
                        'name' => $name,
                        'description' => $description,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->values()
                    ->all()
            );
        }

        $userTypes = Schema::hasTable('user_types')
            ? DB::table('user_types')
                ->select('id', 'name')
                ->whereIn('name', array_keys($publicUserTypes))
                ->orderByRaw("case name when 'pupil' then 1 when 'student' then 2 else 3 end")
                ->get()
            : collect();

        $curriculums = Schema::hasTable('curriculums')
            ? DB::table('curriculums')
                ->select('id', 'name', 'abbreviation')
                ->when(Schema::hasColumn('curriculums', 'is_live'), fn ($query) => $query->where('is_live', true))
                ->orderBy('abbreviation')
                ->get()
            : collect();

        $grades = Schema::hasTable('grades')
            ? DB::table('grades')
                ->select('id', 'curriculum_id', 'name', 'sort_order')
                ->orderBy('sort_order')
                ->get()
            : collect();

        $provinces = Schema::hasTable('provinces')
            ? DB::table('provinces')
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get()
            : collect();

        return view('auth.register', [
            'userTypes' => $userTypes,
            'curriculums' => $curriculums,
            'grades' => $grades,
            'provinces' => $provinces,
            'defaultCurriculum' => $curriculums->firstWhere('abbreviation', 'CAPS') ?? $curriculums->first(),
        ]);
    })->name('register');

    Route::post('/register', function (Request $request) {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'user_type_id' => ['required', 'exists:user_types,id'],
            'curriculum_id' => ['nullable', 'exists:curriculums,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
        ]);

        $userType = DB::table('user_types')
            ->where('id', $data['user_type_id'])
            ->whereIn('name', ['pupil', 'student'])
            ->first(['id', 'name']);
        $countryId = DB::table('countries')->where('name', 'South Africa')->value('id') ?? DB::table('countries')->value('id');

        if ($userType === null || $countryId === null) {
            return back()
                ->withErrors(['email' => 'Please run the database seeders before creating an account.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        if ($userType->name === 'pupil' && empty($data['curriculum_id'])) {
            return back()
                ->withErrors(['curriculum_id' => 'Choose your curriculum for a high school pupil account.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $user = User::create([
            'user_type_id' => $userType->id,
            'country_id' => $countryId,
            'province_id' => $data['province_id'] ?? null,
            'curriculum_id' => $userType->name === 'pupil' ? $data['curriculum_id'] : null,
            'grade_id' => $userType->name === 'pupil' ? ($data['grade_id'] ?? null) : null,
            'name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'last_login_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route($userType->name === 'pupil' ? 'subjects.index' : 'bursaries.index')
            ->with('status', $userType->name === 'pupil' ? 'Select your subjects to personalize Chamu.' : 'Your student account is ready for bursary applications.');
    })->name('register.store');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'super.admin'])->prefix('admin')->group(function () {
    Route::get('/', function () {
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
            'accounts' => $accounts,
        ]);
    })->name('admin.index');

    Route::get('/accounts', function (Request $request) {
        $accountSearch = trim((string) $request->query('account_search', ''));
        $accounts = User::query()
            ->with(['userType', 'curriculum', 'grade', 'province'])
            ->withCount([
                'userSubjectPreferences as subjects_count',
                'userSubjectResults as marks_count' => fn ($query) => $query->whereNotNull('mark'),
            ])
            ->withMax('siteVisits as last_seen_at', 'visited_at')
            ->when($accountSearch !== '', function ($query) use ($accountSearch) {
                $query->where(function ($query) use ($accountSearch) {
                    $query
                        ->where('name', 'like', "%{$accountSearch}%")
                        ->orWhere('first_name', 'like', "%{$accountSearch}%")
                        ->orWhere('last_name', 'like', "%{$accountSearch}%")
                        ->orWhere('username', 'like', "%{$accountSearch}%")
                        ->orWhere('email', 'like', "%{$accountSearch}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'accountSearch' => $accountSearch,
        ]);
    })->name('admin.accounts.index');

    Route::get('/site-visits', function () {
        $siteVisits = SiteVisit::with('user')
            ->latest('visited_at')
            ->paginate(50);

        return view('admin.site-visits.index', [
            'siteVisits' => $siteVisits,
            'totalVisits' => SiteVisit::count(),
            'guestVisits' => SiteVisit::whereNull('user_id')->count(),
            'userVisits' => SiteVisit::whereNotNull('user_id')->count(),
        ]);
    })->name('admin.site-visits.index');

    Route::get('/site-visits/{siteVisit}', function (SiteVisit $siteVisit) {
        $siteVisit->load('user');

        return view('admin.site-visits.show', [
            'siteVisit' => $siteVisit,
        ]);
    })->name('admin.site-visits.show');

    Route::get('/audit-logs', function () {
        $auditLogs = AuditLog::with('user')
            ->latest()
            ->paginate(50);

        return view('admin.audit-logs.index', [
            'auditLogs' => $auditLogs,
            'totalAuditLogs' => AuditLog::count(),
            'markAuditLogs' => AuditLog::where('event', 'marks.updated')->count(),
        ]);
    })->name('admin.audit-logs.index');

    Route::get('/audit-logs/{auditLog}', function (AuditLog $auditLog) {
        $auditLog->load(['user', 'auditable']);

        return view('admin.audit-logs.show', [
            'auditLog' => $auditLog,
        ]);
    })->name('admin.audit-logs.show');

    Route::get('/accounts/{user}', function (User $user) {
        $user->load(['userType', 'curriculum', 'grade', 'province', 'country', 'school', 'parent']);

        $selectedSubjects = $user->userSubjectPreferences()
            ->with(['subject', 'grade', 'curriculum'])
            ->orderBy('sort_order')
            ->get();

        $markResults = UserSubjectResult::query()
            ->with(['subject', 'term', 'grade'])
            ->where('user_subject_results.user_id', $user->id)
            ->whereNotNull('user_subject_results.mark')
            ->leftJoin('grades', 'grades.id', '=', 'user_subject_results.grade_id')
            ->leftJoin('terms', 'terms.id', '=', 'user_subject_results.term_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
            ->select('user_subject_results.*')
            ->orderByDesc('user_subject_results.updated_at')
            ->orderBy('subjects.name')
            ->get();

        $latestResult = $markResults->first();
        $latestMarks = collect();

        if ($latestResult !== null) {
            $latestMarks = UserSubjectResult::query()
                ->with(['subject', 'term', 'grade'])
                ->where('user_subject_results.user_id', $user->id)
                ->where('user_subject_results.grade_id', $latestResult->grade_id)
                ->where('user_subject_results.term_id', $latestResult->term_id)
                ->whereNotNull('user_subject_results.mark')
                ->leftJoin('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
                ->select('user_subject_results.*')
                ->orderBy('subjects.name')
                ->get();
        }

        $isLifeOrientation = fn (UserSubjectResult $result): bool => Str::lower((string) ($result->subject?->code ?? '')) === 'lo'
            || Str::lower((string) ($result->subject?->name ?? '')) === 'life orientation';
        $countedLatestMarks = $latestMarks->reject($isLifeOrientation);
        $latestApsTotal = (int) $countedLatestMarks->sum(fn (UserSubjectResult $result) => (int) ($result->aps_score ?? 0));
        $latestAverageMark = $countedLatestMarks->avg('mark');
        $marksByTerm = $markResults->groupBy(function (UserSubjectResult $result) {
            return ($result->grade?->name ?? 'Unknown grade').' - '.($result->term?->name ?? 'Unknown term');
        });

        $recentVisits = SiteVisit::query()
            ->where('user_id', $user->id)
            ->latest('visited_at')
            ->limit(30)
            ->get();
        $markAuditLogs = AuditLog::query()
            ->where('event', 'marks.updated')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(30)
            ->get();
        $recentExamSessions = DB::table('exam_sessions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
            ->where('exam_sessions.user_id', $user->id)
            ->select(
                'exam_sessions.id',
                'exam_sessions.title',
                'exam_sessions.quiz_type',
                'exam_sessions.source',
                'exam_sessions.score',
                'exam_sessions.total_marks',
                'exam_sessions.percentage',
                'exam_sessions.completed_at',
                'exam_sessions.updated_at',
                'subjects.name as subject_name'
            )
            ->latest('exam_sessions.updated_at')
            ->limit(10)
            ->get();

        return view('admin.accounts.show', [
            'account' => $user,
            'selectedSubjects' => $selectedSubjects,
            'markResults' => $markResults,
            'latestResult' => $latestResult,
            'latestMarks' => $latestMarks,
            'latestApsTotal' => $latestApsTotal,
            'latestAverageMark' => $latestAverageMark,
            'marksByTerm' => $marksByTerm,
            'recentVisits' => $recentVisits,
            'markAuditLogs' => $markAuditLogs,
            'recentExamSessions' => $recentExamSessions,
        ]);
    })->name('admin.accounts.show');
});

Route::get('/aps-calculator', function (Request $request) {
    $user = $request->user();

    $subjects = DB::table('subjects')
        ->join('grades', 'grades.id', '=', 'subjects.grade_id')
        ->join('curriculums', 'curriculums.id', '=', 'subjects.curriculum_id')
        ->when(
            $user?->curriculum_id && $user?->grade_id,
            fn ($query) => $query
                ->where('subjects.curriculum_id', $user->curriculum_id)
                ->where('subjects.grade_id', $user->grade_id),
            fn ($query) => $query
                ->where('curriculums.abbreviation', 'CAPS')
                ->where('grades.name', 'Grade 12'),
        )
        ->select('subjects.id', 'subjects.name', 'subjects.code', 'subjects.abbreviation')
        ->orderBy('subjects.name')
        ->get();

    $subjectById = $subjects->keyBy('id');
    $subjectIdByName = $subjects->keyBy('name');
    $submittedRows = collect($request->input('subjects', []));
    $usingSavedMarks = false;
    $savedMarksTerm = null;
    $defaultSubjectNames = [
        'English Home Language',
        'Mathematics',
        'Life Orientation',
        'Physical Sciences',
        'Life Sciences',
        'Accounting',
        'Business Studies',
    ];

    if ($submittedRows->isEmpty() && $user !== null && $user->grade_id !== null && Schema::hasTable('user_subject_preferences')) {
        $latestTerm = null;

        if (Schema::hasTable('user_subject_results')) {
            $latestTerm = DB::table('user_subject_results')
                ->join('terms', 'terms.id', '=', 'user_subject_results.term_id')
                ->where('user_subject_results.user_id', $user->id)
                ->where('user_subject_results.grade_id', $user->grade_id)
                ->whereNotNull('user_subject_results.mark')
                ->select('terms.id', 'terms.name')
                ->orderByDesc('terms.id')
                ->first();
        }

        $selectedSubjectsQuery = DB::table('user_subject_preferences')
            ->join('subjects', 'subjects.id', '=', 'user_subject_preferences.subject_id')
            ->where('user_subject_preferences.user_id', $user->id)
            ->where('user_subject_preferences.grade_id', $user->grade_id)
            ->select(
                'subjects.id as subject_id',
                'user_subject_preferences.sort_order',
            )
            ->orderBy('user_subject_preferences.sort_order');

        if ($latestTerm !== null) {
            $selectedSubjectsQuery
                ->leftJoin('user_subject_results', function ($join) use ($user, $latestTerm) {
                    $join
                        ->on('user_subject_results.subject_id', '=', 'subjects.id')
                        ->where('user_subject_results.user_id', '=', $user->id)
                        ->where('user_subject_results.grade_id', '=', $user->grade_id)
                        ->where('user_subject_results.term_id', '=', $latestTerm->id);
                })
                ->addSelect('user_subject_results.mark');
        } else {
            $selectedSubjectsQuery->addSelect(DB::raw('null as mark'));
        }

        $savedRows = $selectedSubjectsQuery->get();

        if ($savedRows->isNotEmpty()) {
            $submittedRows = $savedRows->map(fn ($row) => [
                'subject_id' => $row->subject_id,
                'mark' => $row->mark,
            ]);
            $usingSavedMarks = $savedRows->contains(fn ($row) => $row->mark !== null);
            $savedMarksTerm = $latestTerm?->name;
        }
    }

    if ($submittedRows->isEmpty()) {
        $submittedRows = collect($defaultSubjectNames)->map(fn ($subjectName) => [
            'subject_id' => $subjectIdByName->get($subjectName)?->id,
            'mark' => null,
        ]);
    }

    $isLifeOrientation = fn (?string $subjectName): bool => strcasecmp((string) $subjectName, 'Life Orientation') === 0;
    $isLearningLanguage = function (?string $subjectName): bool {
        $subjectName = (string) $subjectName;

        return str_contains($subjectName, 'English ') || str_contains($subjectName, 'Afrikaans ');
    };
    $isMathematics = fn (?string $subjectName): bool => strcasecmp((string) $subjectName, 'Mathematics') === 0;
    $isPhysicalSciences = fn (?string $subjectName): bool => strcasecmp((string) $subjectName, 'Physical Sciences') === 0;
    $levelForMark = function (float $mark): int {
        return match (true) {
            $mark >= 80 => 7,
            $mark >= 70 => 6,
            $mark >= 60 => 5,
            $mark >= 50 => 4,
            $mark >= 40 => 3,
            $mark >= 30 => 2,
            default => 1,
        };
    };
    $witsPointsFor = function (string $subjectName, float $mark): int {
        if (in_array($subjectName, ['English Home Language', 'English First Additional Language', 'Mathematics'], true)) {
            return match (true) {
                $mark >= 90 => 10,
                $mark >= 80 => 9,
                $mark >= 70 => 8,
                $mark >= 60 => 7,
                $mark >= 50 => 4,
                $mark >= 40 => 3,
                default => 0,
            };
        }

        if (strcasecmp($subjectName, 'Life Orientation') === 0) {
            return match (true) {
                $mark >= 90 => 4,
                $mark >= 80 => 3,
                $mark >= 70 => 2,
                $mark >= 60 => 1,
                default => 0,
            };
        }

        return match (true) {
            $mark >= 90 => 8,
            $mark >= 80 => 7,
            $mark >= 70 => 6,
            $mark >= 60 => 5,
            $mark >= 50 => 4,
            $mark >= 40 => 3,
            default => 0,
        };
    };
    $formatNumber = fn (?float $value, int $decimals = 1): string => $value === null
        ? 'N/A'
        : rtrim(rtrim(number_format($value, $decimals), '0'), '.');

    $rows = $submittedRows
        ->values()
        ->map(function ($row) use ($subjectById, $levelForMark, $witsPointsFor, $isLifeOrientation) {
            $subjectId = (int) ($row['subject_id'] ?? 0);
            $subject = $subjectById->get($subjectId);
            $rawMark = $row['mark'] ?? null;
            $mark = is_numeric($rawMark) ? min(max((float) $rawMark, 0), 100) : null;
            $subjectName = $subject?->name;

            return (object) [
                'subject_id' => $subject?->id,
                'subject_name' => $subjectName,
                'mark' => $mark,
                'level' => $mark === null ? null : $levelForMark($mark),
                'aps_points' => $mark === null ? null : $levelForMark($mark),
                'wits_points' => ($mark === null || $subjectName === null) ? null : $witsPointsFor($subjectName, $mark),
                'is_life_orientation' => $isLifeOrientation($subjectName),
            ];
        });

    $scoredRows = $rows
        ->filter(fn ($row) => $row->subject_id !== null && $row->mark !== null)
        ->values();
    $rowsBySubjectName = $scoredRows->keyBy('subject_name');
    $nonLoRows = $scoredRows->reject(fn ($row) => $row->is_life_orientation)->values();
    $bestSixExcludingLo = $nonLoRows
        ->sortByDesc('mark')
        ->take(6)
        ->values();
    $bestSixIds = $bestSixExcludingLo->pluck('subject_id')->all();

    $learningLanguageRow = $nonLoRows
        ->filter(fn ($row) => $isLearningLanguage($row->subject_name))
        ->sortByDesc('mark')
        ->first();
    $stellenboschOtherRows = $nonLoRows
        ->reject(fn ($row) => $learningLanguageRow !== null && $row->subject_id === $learningLanguageRow->subject_id)
        ->sortByDesc('mark')
        ->take(5)
        ->values();
    $stellenboschRows = collect($learningLanguageRow ? [$learningLanguageRow] : [])
        ->merge($stellenboschOtherRows)
        ->values();
    $stellenboschAverage = $stellenboschRows->count() >= 6
        ? (float) $stellenboschRows->avg('mark')
        : null;
    $mathRow = $rowsBySubjectName->get('Mathematics');
    $physicalSciencesRow = $rowsBySubjectName->get('Physical Sciences');
    $stellenboschSelection = ($stellenboschAverage !== null && $mathRow !== null && $physicalSciencesRow !== null)
        ? (float) $mathRow->mark + (float) $physicalSciencesRow->mark + (6 * $stellenboschAverage)
        : null;

    $disadvantageFactor = $request->has('disadvantage_factor')
        ? min(max((float) $request->query('disadvantage_factor'), 0), 100)
        : 0.0;
    $nscApsExcludingLo = (float) $nonLoRows->sum('aps_points');
    $nscApsIncludingLo = (float) $scoredRows->sum('aps_points');
    $aggregateExcludingLo = $nonLoRows->isEmpty() ? null : (float) $nonLoRows->avg('mark');
    $aggregateIncludingLo = $scoredRows->isEmpty() ? null : (float) $scoredRows->avg('mark');
    $uctFps600 = $bestSixExcludingLo->count() >= 6 ? (float) $bestSixExcludingLo->sum('mark') : null;
    $uctScienceFps = $uctFps600 === null
        ? null
        : $uctFps600 + (float) ($mathRow?->mark ?? 0) + (float) ($physicalSciencesRow?->mark ?? 0);
    $nbtScores = [
        'AL' => $request->has('nbt_al') && is_numeric($request->query('nbt_al')) ? min(max((float) $request->query('nbt_al'), 0), 100) : null,
        'QL' => $request->has('nbt_ql') && is_numeric($request->query('nbt_ql')) ? min(max((float) $request->query('nbt_ql'), 0), 100) : null,
        'MAT' => $request->has('nbt_mat') && is_numeric($request->query('nbt_mat')) ? min(max((float) $request->query('nbt_mat'), 0), 100) : null,
    ];
    $nbtTotal = collect($nbtScores)->filter(fn ($score) => $score !== null)->sum();
    $hasAllNbtScores = collect($nbtScores)->every(fn ($score) => $score !== null);
    $uctHealthFps = ($uctFps600 !== null && $hasAllNbtScores) ? $uctFps600 + $nbtTotal : null;
    $withWps = fn (?float $score): ?float => $score === null ? null : $score + ($score * ($disadvantageFactor / 100));

    $passTypeRank = ['none' => 0, 'nsc' => 1, 'higher_certificate' => 2, 'diploma' => 3, 'bachelor' => 4];
    $passTypeLabels = [
        'none' => 'Not enough marks yet',
        'nsc' => 'NSC pass',
        'higher_certificate' => 'Higher Certificate pass',
        'diploma' => 'Diploma pass',
        'bachelor' => 'Bachelor pass',
    ];
    $homeLanguageRows = $scoredRows->filter(fn ($row) => str_contains((string) $row->subject_name, 'Home Language'));
    $languageRows = $scoredRows->filter(fn ($row) => str_contains((string) $row->subject_name, 'Language'));
    $homeLanguageAt40 = $homeLanguageRows->contains(fn ($row) => $row->mark >= 40);
    $subjectsAt50ExcludingLo = $nonLoRows->where('mark', '>=', 50)->count();
    $subjectsAt40ExcludingLo = $nonLoRows->where('mark', '>=', 40)->count();
    $subjectsAt40 = $scoredRows->where('mark', '>=', 40)->count();
    $subjectsAt30 = $scoredRows->where('mark', '>=', 30)->count();
    $languageAt30 = $languageRows->contains(fn ($row) => $row->mark >= 30);
    $remainingAt30 = $scoredRows->count() >= 7 && $scoredRows->sortBy('mark')->first()?->mark >= 30;
    $passType = 'none';

    if ($homeLanguageAt40 && $subjectsAt50ExcludingLo >= 4 && $remainingAt30) {
        $passType = 'bachelor';
    } elseif ($homeLanguageAt40 && $subjectsAt40ExcludingLo >= 3 && $remainingAt30) {
        $passType = 'diploma';
    } elseif ($homeLanguageAt40 && $subjectsAt40 >= 2 && $remainingAt30) {
        $passType = 'higher_certificate';
    } elseif ($homeLanguageAt40 && $subjectsAt40 >= 2 && $subjectsAt30 >= 5) {
        $passType = 'nsc';
    }

    $seniorCertificatePass = $subjectsAt40 >= 3
        && $homeLanguageAt40
        && $subjectsAt30 >= 5
        && $languageAt30
        && ($scoredRows->count() >= 6 && $scoredRows->sortBy('mark')->take(6)->first()?->mark >= 20);

    $scoreSummaries = collect([
        [
            'label' => 'APS without LO',
            'value' => $formatNumber($nscApsExcludingLo, 0),
            'max' => '42',
            'note' => 'NSC levels summed, Life Orientation excluded.',
            'accent' => 'emerald',
        ],
        [
            'label' => 'APS with LO',
            'value' => $formatNumber($nscApsIncludingLo, 0),
            'max' => '49',
            'note' => 'NSC levels summed, Life Orientation included.',
            'accent' => 'sky',
        ],
        [
            'label' => 'Wits APS',
            'value' => $formatNumber((float) $scoredRows->sum('wits_points'), 0),
            'max' => '56',
            'note' => 'Wits boosted English/Maths scale and reduced LO scale.',
            'accent' => 'violet',
        ],
        [
            'label' => 'UCT FPS 600',
            'value' => $formatNumber($uctFps600, 0),
            'max' => '600',
            'note' => $uctFps600 === null ? 'Enter at least six non-LO subjects.' : 'Best six marks excluding Life Orientation.',
            'accent' => 'rose',
        ],
        [
            'label' => 'UCT WPS 600',
            'value' => $formatNumber($withWps($uctFps600), 0),
            'max' => '600+',
            'note' => 'UCT FPS 600 plus the disadvantage factor entered below.',
            'accent' => 'amber',
        ],
        [
            'label' => 'UCT Science FPS',
            'value' => $formatNumber($uctScienceFps, 0),
            'max' => '800',
            'note' => ($mathRow === null || $physicalSciencesRow === null) ? 'Needs Mathematics and Physical Sciences for the extra weighting.' : 'Best six excluding LO, with Mathematics and Physical Sciences added again.',
            'accent' => 'indigo',
        ],
        [
            'label' => 'UCT Health FPS',
            'value' => $formatNumber($uctHealthFps, 0),
            'max' => '900',
            'note' => $hasAllNbtScores ? 'School FPS plus NBT AL, QL and MAT.' : 'Enter NBT AL, QL and MAT to complete this score.',
            'accent' => 'teal',
        ],
        [
            'label' => 'Stellenbosch average',
            'value' => $stellenboschAverage === null ? 'N/A' : $formatNumber($stellenboschAverage, 1).'%',
            'max' => '100%',
            'note' => 'Highest English/Afrikaans plus best five other non-LO subjects.',
            'accent' => 'orange',
        ],
        [
            'label' => 'SU selection score',
            'value' => $formatNumber($stellenboschSelection, 0),
            'max' => '800',
            'note' => 'Mathematics + Physical Sciences + six times the Stellenbosch average.',
            'accent' => 'cyan',
        ],
        [
            'label' => 'Aggregate without LO',
            'value' => $aggregateExcludingLo === null ? 'N/A' : $formatNumber($aggregateExcludingLo, 1).'%',
            'max' => '100%',
            'note' => 'Average of entered non-LO marks.',
            'accent' => 'neutral',
        ],
        [
            'label' => 'Aggregate with LO',
            'value' => $aggregateIncludingLo === null ? 'N/A' : $formatNumber($aggregateIncludingLo, 1).'%',
            'max' => '100%',
            'note' => 'Average of all entered marks.',
            'accent' => 'neutral',
        ],
        [
            'label' => 'NSC pass type',
            'value' => $passTypeLabels[$passType],
            'max' => null,
            'note' => 'Estimated from the marks entered.',
            'accent' => $passTypeRank[$passType] >= 4 ? 'emerald' : 'neutral',
        ],
        [
            'label' => 'Senior Certificate',
            'value' => $seniorCertificatePass ? 'Pass' : 'Not met',
            'max' => null,
            'note' => 'Estimated Senior Certificate promotion check.',
            'accent' => $seniorCertificatePass ? 'emerald' : 'neutral',
        ],
    ]);

    $subjectBreakdown = $rows->map(function ($row) use ($bestSixIds, $stellenboschRows) {
        $isInBestSix = $row->subject_id !== null && in_array($row->subject_id, $bestSixIds, true);
        $isInStellenbosch = $row->subject_id !== null && $stellenboschRows->pluck('subject_id')->contains($row->subject_id);

        return (object) array_merge((array) $row, [
            'uct_fps_points' => $isInBestSix ? $row->mark : null,
            'stellenbosch_points' => $isInStellenbosch ? $row->mark : null,
        ]);
    });

    return view('tools.aps-calculator', [
        'subjects' => $subjects,
        'rows' => $rows,
        'subjectBreakdown' => $subjectBreakdown,
        'scoreSummaries' => $scoreSummaries,
        'disadvantageFactor' => $disadvantageFactor,
        'nbtScores' => $nbtScores,
        'formatNumber' => $formatNumber,
        'usingSavedMarks' => $usingSavedMarks,
        'savedMarksTerm' => $savedMarksTerm,
    ]);
})->name('aps-calculator.index');

Route::get('/funding', fn () => redirect()->route('bursaries.index'))->name('funding.index');

Route::scopeBindings()->group(function () {
    Route::get('/universities/{university:slug}', [PublicUniversityController::class, 'show'])
        ->name('public.universities.show');
    Route::get('/universities/{university:slug}/qualifications/{qualification:slug}', [PublicQualificationController::class, 'show'])
        ->name('public.qualifications.show');
});

Route::get('/aps', function (Request $request) {
    if ($request->user() !== null) {
        return redirect()->route('course-match.index', $request->query());
    }

    $apsScore = $request->has('aps_score') && is_numeric($request->query('aps_score'))
        ? min(max((int) $request->query('aps_score'), 0), 60)
        : null;
    $search = trim((string) $request->query('search', ''));
    $requestedUniversityIds = $request->query('university_ids', []);

    if (! is_array($requestedUniversityIds)) {
        $requestedUniversityIds = [$requestedUniversityIds];
    }

    $legacyUniversityId = $request->integer('university_id') ?: null;

    if ($legacyUniversityId !== null) {
        $requestedUniversityIds[] = $legacyUniversityId;
    }

    $universities = DB::table('universities')
        ->select('id', 'name', 'abbreviation', 'logo')
        ->orderBy('name')
        ->get();
    $validUniversityIds = $universities
        ->pluck('id')
        ->map(fn ($id) => (int) $id);
    $selectedUniversityIds = collect($requestedUniversityIds)
        ->filter(fn ($id) => is_numeric($id))
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->intersect($validUniversityIds)
        ->values();
    $qualificationCount = DB::table('qualifications')->count();
    $bursaryCount = Schema::hasTable('bursaries') ? DB::table('bursaries')->count() : 0;

    $courses = collect();
    $previewCourses = collect();

    $qualificationQuery = function () use ($selectedUniversityIds, $search) {
        return DB::table('qualifications')
            ->join('universities', 'universities.id', '=', 'qualifications.university_id')
            ->join('faculties', 'faculties.id', '=', 'qualifications.faculty_id')
            ->join('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
            ->whereNotNull('qualifications.aps_required')
            ->when($selectedUniversityIds->isNotEmpty(), fn ($query) => $query->whereIn('qualifications.university_id', $selectedUniversityIds->all()))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('qualifications.name', 'like', '%'.$search.'%')
                        ->orWhere('faculties.name', 'like', '%'.$search.'%')
                        ->orWhere('qualification_types.name', 'like', '%'.$search.'%')
                        ->orWhere('universities.name', 'like', '%'.$search.'%')
                        ->orWhere('universities.abbreviation', 'like', '%'.$search.'%');
                });
            })
            ->select(
                'qualifications.id',
                'qualifications.name',
                'qualifications.aps_required',
                'qualifications.duration_years',
                'qualifications.is_selection_programme',
                'universities.id as university_id',
                'universities.name as university_name',
                'universities.abbreviation as university_abbreviation',
                'universities.logo as university_logo',
                'faculties.name as faculty_name',
                'qualification_types.name as qualification_type_name',
            );
    };

    if ($apsScore !== null) {
        $courses = $qualificationQuery()
            ->where('qualifications.aps_required', '<=', $apsScore)
            ->orderBy('qualifications.aps_required')
            ->orderBy('universities.name')
            ->orderBy('qualifications.name')
            ->limit(80)
            ->get();
    } elseif ($selectedUniversityIds->isNotEmpty()) {
        $previewPool = $qualificationQuery()
            ->orderBy('qualifications.aps_required')
            ->orderBy('universities.name')
            ->orderBy('qualifications.name')
            ->limit(300)
            ->get();

        if ($previewPool->isNotEmpty()) {
            $lastIndex = $previewPool->count() - 1;
            $targetIndexes = collect([
                0,
                (int) floor($lastIndex * 0.25),
                (int) floor($lastIndex * 0.5),
                (int) floor($lastIndex * 0.75),
                $lastIndex,
            ]);

            $previewCourses = $targetIndexes
                ->map(fn ($index) => $previewPool->get($index))
                ->filter()
                ->unique('id')
                ->values();

            if ($previewCourses->count() < 5) {
                $previewCourses = $previewCourses
                    ->merge($previewPool)
                    ->unique('id')
                    ->take(5)
                    ->values();
            }
        }
    }

    return view('aps.index', [
        'apsScore' => $apsScore,
        'search' => $search,
        'universities' => $universities,
        'qualificationCount' => $qualificationCount,
        'bursaryCount' => $bursaryCount,
        'courses' => $courses,
        'previewCourses' => $previewCourses,
        'filters' => [
            'university_id' => $selectedUniversityIds->first(),
            'university_ids' => $selectedUniversityIds->all(),
        ],
    ]);
})->name('aps.index');

Route::get('/bursaries', function (Request $request) {
    $search = trim((string) $request->query('search', ''));
    $category = trim((string) $request->query('category', ''));
    $companyId = $request->integer('company_id') ?: null;
    $today = now()->toDateString();

    $companies = DB::table('companies')
        ->join('bursaries', 'bursaries.company_id', '=', 'companies.id')
        ->select('companies.id', 'companies.name')
        ->distinct()
        ->orderBy('companies.name')
        ->get();

    $categories = DB::table('bursaries')
        ->whereNotNull('category')
        ->distinct()
        ->orderBy('category')
        ->pluck('category');

    $requirementsByBursary = DB::table('bursary_subject_requirements')
        ->orderBy('id')
        ->get()
        ->groupBy('bursary_id');

    $latestResults = collect();
    $user = $request->user();

    if ($user !== null && $user->grade_id !== null) {
        $latestTermId = DB::table('user_subject_results')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->whereNotNull('mark')
            ->orderByDesc('term_id')
            ->value('term_id');

        if ($latestTermId !== null) {
            $latestResults = DB::table('user_subject_results')
                ->join('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
                ->where('user_subject_results.user_id', $user->id)
                ->where('user_subject_results.grade_id', $user->grade_id)
                ->where('user_subject_results.term_id', $latestTermId)
                ->whereNotNull('user_subject_results.mark')
                ->select(
                    'subjects.id as subject_id',
                    'subjects.name as subject_name',
                    'subjects.code',
                    'subjects.abbreviation',
                    'user_subject_results.mark',
                    'user_subject_results.aps_score',
                )
                ->get();
        }
    }

    $matchBursary = function (int $bursaryId) use ($requirementsByBursary, $latestResults): array {
        $requirements = $requirementsByBursary->get($bursaryId, collect());

        if ($requirements->isEmpty()) {
            return [
                'status' => 'No listed academic requirements',
                'tone' => 'neutral',
                'met' => [],
                'missing' => [],
                'requirements_count' => 0,
            ];
        }

        if ($latestResults->isEmpty()) {
            return [
                'status' => 'Add marks to match',
                'tone' => 'sky',
                'met' => [],
                'missing' => ['Marks are needed before this bursary can be checked.'],
                'requirements_count' => $requirements->count(),
            ];
        }

        $resultsBySubjectId = $latestResults->keyBy('subject_id');
        $normalise = fn (string $value): string => trim(strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $value)));
        $formatMark = fn (float $value): string => rtrim(rtrim(number_format($value, 1), '0'), '.');
        $isLifeOrientation = function (object $result): bool {
            $code = strtoupper($result->code ?? $result->abbreviation ?? '');

            return $code === 'LO' || strcasecmp((string) $result->subject_name, 'Life Orientation') === 0;
        };
        $thresholdLabel = function (object $requirement): string {
            if ($requirement->requirement_type === 'minimum_aps') {
                return 'APS '.(int) $requirement->aps_level_required;
            }

            if ($requirement->requirement_type === 'minimum_average') {
                return (int) $requirement->minimum_mark.'% average';
            }

            if ($requirement->minimum_mark !== null) {
                return (int) $requirement->minimum_mark.'%';
            }

            if ($requirement->aps_level_required !== null) {
                return 'level '.(int) $requirement->aps_level_required;
            }

            return 'required';
        };
        $matchingResult = function (object $requirement) use ($latestResults, $resultsBySubjectId, $normalise): ?object {
            if ($requirement->subject_id !== null && $resultsBySubjectId->has($requirement->subject_id)) {
                return $resultsBySubjectId->get($requirement->subject_id);
            }

            $requirementName = $normalise((string) ($requirement->subject_name ?? ''));

            if ($requirementName === '') {
                return null;
            }

            if (str_contains($requirementName, 'english')) {
                return $latestResults->first(fn ($result) => str_contains($normalise((string) $result->subject_name), 'english'));
            }

            return $latestResults->first(function ($result) use ($requirementName, $normalise) {
                $subjectName = $normalise((string) $result->subject_name);

                return $subjectName === $requirementName
                    || str_contains($requirementName, $subjectName)
                    || str_contains($subjectName, $requirementName);
            });
        };
        $requirementIsMet = function (?object $result, object $requirement): bool {
            if ($result === null) {
                return false;
            }

            if ($requirement->aps_level_required !== null) {
                return (int) $result->aps_score >= (int) $requirement->aps_level_required;
            }

            if ($requirement->minimum_mark !== null) {
                return (float) $result->mark >= (float) $requirement->minimum_mark;
            }

            return true;
        };
        $evaluateRequirement = function (object $requirement) use ($latestResults, $isLifeOrientation, $thresholdLabel, $matchingResult, $requirementIsMet, $formatMark): array {
            $label = trim(($requirement->subject_name ?? 'Required subject').' '.$thresholdLabel($requirement));

            if ($requirement->requirement_type === 'minimum_average') {
                $average = $latestResults->reject($isLifeOrientation)->avg('mark');
                $isMet = $average !== null && (float) $average >= (float) $requirement->minimum_mark;

                return [
                    'met' => $isMet,
                    'label' => $label,
                    'missing' => $isMet ? null : $label.($average === null ? '' : '; your average '.$formatMark((float) $average).'%'),
                ];
            }

            if ($requirement->requirement_type === 'minimum_aps') {
                $apsTotal = $latestResults
                    ->reject($isLifeOrientation)
                    ->sum(fn ($result) => (int) $result->aps_score);
                $isMet = $apsTotal >= (int) $requirement->aps_level_required;

                return [
                    'met' => $isMet,
                    'label' => $label,
                    'missing' => $isMet ? null : $label.'; your APS '.$apsTotal,
                ];
            }

            if ($requirement->requirement_type === 'all_other_subjects') {
                $failedSubjects = $latestResults
                    ->filter(fn ($result) => $requirement->minimum_mark !== null && (float) $result->mark < (float) $requirement->minimum_mark)
                    ->pluck('subject_name')
                    ->values();

                return [
                    'met' => $failedSubjects->isEmpty(),
                    'label' => $label,
                    'missing' => $failedSubjects->isEmpty() ? null : $label.'; below threshold: '.$failedSubjects->implode(', '),
                ];
            }

            $result = $matchingResult($requirement);
            $isMet = $requirementIsMet($result, $requirement);

            return [
                'met' => $isMet,
                'label' => $label,
                'missing' => $isMet ? null : $label.($result === null ? '' : '; your mark '.$formatMark((float) $result->mark).'%'),
            ];
        };

        $met = [];
        $missing = [];
        $optionRequirements = $requirements
            ->filter(fn ($requirement) => in_array($requirement->requirement_type, ['option_required', 'option_any_of'], true))
            ->groupBy('requirement_group');

        foreach ($requirements->where('requirement_type', 'any_of')->groupBy('requirement_group') as $group => $groupRequirements) {
            $outcomes = $groupRequirements->map($evaluateRequirement);
            $successful = $outcomes->firstWhere('met', true);

            if ($successful !== null) {
                $met[] = $successful['label'];
            } else {
                $missing[] = 'One of '.($group ?: 'the listed options').': '.$outcomes->pluck('label')->implode(' or ');
            }
        }

        foreach ($optionRequirements as $group => $groupRequirements) {
            $requiredOutcomes = $groupRequirements
                ->where('requirement_type', 'option_required')
                ->map($evaluateRequirement);
            $anyOfOutcomes = $groupRequirements
                ->where('requirement_type', 'option_any_of')
                ->map($evaluateRequirement);
            $requiredMet = $requiredOutcomes->every(fn ($outcome) => $outcome['met']);
            $anyOfMet = $anyOfOutcomes->isEmpty() || $anyOfOutcomes->contains(fn ($outcome) => $outcome['met']);

            if ($requiredMet && $anyOfMet) {
                $met[] = 'Option met: '.$group;
                continue;
            }

            $optionMissing = $requiredOutcomes
                ->filter(fn ($outcome) => ! $outcome['met'])
                ->pluck('missing')
                ->filter()
                ->values();

            if ($anyOfOutcomes->isNotEmpty() && ! $anyOfMet) {
                $optionMissing->push('one of '.$anyOfOutcomes->pluck('label')->implode(' or '));
            }

            if ($optionMissing->isNotEmpty()) {
                $missing[] = trim($group.': '.$optionMissing->implode('; '));
            }
        }

        foreach ($requirements as $requirement) {
            if (in_array($requirement->requirement_type, ['any_of', 'option_required', 'option_any_of'], true)) {
                continue;
            }

            $outcome = $evaluateRequirement($requirement);

            if ($outcome['met']) {
                $met[] = $outcome['label'];
            } elseif ($requirement->requirement_type !== 'optional' && $outcome['missing'] !== null) {
                $missing[] = $outcome['missing'];
            }
        }

        if ($optionRequirements->isNotEmpty()) {
            $passedOption = collect($met)->contains(fn ($line) => str_starts_with($line, 'Option met: '));

            if ($passedOption) {
                $missing = collect($missing)
                    ->reject(function ($line) use ($optionRequirements) {
                        return $optionRequirements->keys()->contains(fn ($group) => str_starts_with($line, $group.':'));
                    })
                    ->values()
                    ->all();
            } elseif ($optionRequirements->count() > 1) {
                $missing = ['Meet one listed programme option: '.collect($missing)->implode(' | ')];
            }
        }

        $met = array_values(array_unique($met));
        $missing = array_values(array_unique($missing));

        return [
            'status' => $missing === [] ? 'You meet listed requirements' : 'Still needed',
            'tone' => $missing === [] ? 'emerald' : 'amber',
            'met' => $met,
            'missing' => $missing,
            'requirements_count' => $requirements->count(),
        ];
    };

    $bursaries = DB::table('bursaries')
        ->leftJoin('companies', 'companies.id', '=', 'bursaries.company_id')
        ->select(
            'bursaries.*',
            'companies.name as company_name',
            'companies.logo as company_logo',
        )
        ->where('bursaries.is_active', true)
        ->when($companyId !== null, fn ($query) => $query->where('bursaries.company_id', $companyId))
        ->when($category !== '', fn ($query) => $query->where('bursaries.category', $category))
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('bursaries.title', 'like', '%'.$search.'%')
                    ->orWhere('bursaries.category', 'like', '%'.$search.'%')
                    ->orWhere('bursaries.fields_covered', 'like', '%'.$search.'%')
                    ->orWhere('bursaries.summary', 'like', '%'.$search.'%')
                    ->orWhere('companies.name', 'like', '%'.$search.'%');
            });
        })
        ->orderByRaw(
            'case when bursaries.closing_date >= ? then 0 when bursaries.closing_date is null then 1 else 2 end',
            [$today],
        )
        ->orderByDesc('bursaries.closing_date')
        ->orderBy('bursaries.title')
        ->paginate(12)
        ->withQueryString()
        ->through(function ($bursary) use ($matchBursary) {
            $bursary->match = $matchBursary((int) $bursary->id);
            $bursary->eligibility_requirements = json_decode($bursary->eligibility_requirements ?? '[]', true) ?: [];

            return $bursary;
        });

    return view('bursaries.index', [
        'bursaries' => $bursaries,
        'companies' => $companies,
        'categories' => $categories,
        'search' => $search,
        'filters' => [
            'category' => $category,
            'company_id' => $companyId,
        ],
        'hasMarks' => $latestResults->isNotEmpty(),
    ]);
})->name('bursaries.index');

Route::get('/bursaries/{bursary}', function (Request $request, int $bursary) {
    $bursary = DB::table('bursaries')
        ->leftJoin('companies', 'companies.id', '=', 'bursaries.company_id')
        ->where('bursaries.id', $bursary)
        ->select(
            'bursaries.*',
            'companies.name as company_name',
            'companies.website as company_website',
            'companies.logo as company_logo',
            'companies.description as company_description',
        )
        ->first();

    abort_if($bursary === null, 404);

    $bursary->eligibility_requirements = json_decode($bursary->eligibility_requirements ?? '[]', true) ?: [];
    $bursary->supporting_documents = json_decode($bursary->supporting_documents ?? '[]', true) ?: [];
    $bursaryModel = (new Bursary())->setRawAttributes((array) $bursary, true);
    $providerEmail = $bursaryModel->applicationProviderEmail();
    $providerPostalAddress = $bursaryModel->applicationProviderPostalAddress();
    $isEmailSubmission = $bursaryModel->isEmailSubmission();
    $isPostalSubmission = $bursaryModel->isPostalSubmission();
    $hasValidProviderEmail = filter_var($providerEmail, FILTER_VALIDATE_EMAIL) !== false;
    $usesPostalChamuFlow = $isPostalSubmission && ! ($isEmailSubmission && $hasValidProviderEmail);
    $applicationTablesReady = Schema::hasTable('bursary_applications')
        && Schema::hasTable('bursary_application_documents');

    $requirements = DB::table('bursary_subject_requirements')
        ->where('bursary_id', $bursary->id)
        ->orderBy('id')
        ->get();

    $documentRequirements = Schema::hasTable('bursary_document_requirements')
        ? DB::table('bursary_document_requirements')
            ->where('bursary_id', $bursary->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
        : collect();

    if ($documentRequirements->isEmpty() && ($isEmailSubmission || $isPostalSubmission)) {
        $documentRequirements = BursaryDocumentRequirement::defaultEmailSubmissionRequirements();
    }

    $latestApplication = null;

    if ($request->user() !== null && Schema::hasTable('bursary_applications')) {
        $latestApplication = DB::table('bursary_applications')
            ->where('bursary_id', $bursary->id)
            ->where('user_id', $request->user()->id)
            ->latest('created_at')
            ->first();
    }

    return view('bursaries.show', [
        'bursary' => $bursary,
        'requirements' => $requirements,
        'documentRequirements' => $documentRequirements,
        'latestApplication' => $latestApplication,
        'isChamuHandled' => $applicationTablesReady && (
            ($isEmailSubmission && $hasValidProviderEmail)
            || $isPostalSubmission
        ),
        'isPostalSubmission' => $usesPostalChamuFlow,
        'applicationTablesReady' => $applicationTablesReady,
        'providerEmail' => $providerEmail,
        'providerPostalAddress' => $providerPostalAddress,
    ]);
})->name('bursaries.show');

Route::post('/bursaries/{bursary}/apply', [BursaryApplicationController::class, 'store'])
    ->middleware('auth')
    ->name('bursaries.apply');

Route::middleware('auth')->group(function () {
    Route::get('/tools', function (Request $request) {
        $calculator = [
            'first_number' => $request->query('first_number'),
            'second_number' => $request->query('second_number'),
            'operation' => $request->query('operation', 'add'),
            'result' => null,
            'error' => null,
        ];

        if ($request->hasAny(['first_number', 'second_number'])) {
            $data = $request->validate([
                'first_number' => ['required', 'numeric'],
                'second_number' => ['required', 'numeric'],
                'operation' => ['required', 'in:add,subtract,multiply,divide'],
            ]);

            $firstNumber = (float) $data['first_number'];
            $secondNumber = (float) $data['second_number'];
            $operation = $data['operation'];

            $calculator['first_number'] = $data['first_number'];
            $calculator['second_number'] = $data['second_number'];
            $calculator['operation'] = $operation;

            if ($operation === 'divide' && $secondNumber == 0.0) {
                $calculator['error'] = 'Cannot divide by zero.';
            } else {
                $calculator['result'] = match ($operation) {
                    'add' => $firstNumber + $secondNumber,
                    'subtract' => $firstNumber - $secondNumber,
                    'multiply' => $firstNumber * $secondNumber,
                    'divide' => $firstNumber / $secondNumber,
                };
            }
        }

        return view('tools.index', [
            'calculator' => $calculator,
        ]);
    })->name('tools.index');

    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();

        $selectedSubjects = collect();
        $latestTerm = null;
        $results = collect();
        $pendingQuizzes = collect();
        $recentAttempts = collect();
        $recentBursaryApplications = collect();
        $applicationSummary = (object) [
            'total' => 0,
            'submitted' => 0,
            'postal_ready' => 0,
            'failed' => 0,
        ];

        if ($user->grade_id !== null) {
            $selectedSubjects = DB::table('user_subject_preferences')
                ->join('subjects', 'subjects.id', '=', 'user_subject_preferences.subject_id')
                ->where('user_subject_preferences.user_id', $user->id)
                ->where('user_subject_preferences.grade_id', $user->grade_id)
                ->select('subjects.id', 'subjects.name', 'subjects.code', 'subjects.abbreviation')
                ->orderBy('subjects.name')
                ->get();

            $latestTermId = DB::table('user_subject_results')
                ->where('user_id', $user->id)
                ->where('grade_id', $user->grade_id)
                ->whereNotNull('mark')
                ->orderByDesc('term_id')
                ->value('term_id');

            if ($latestTermId !== null) {
                $latestTerm = DB::table('terms')->where('id', $latestTermId)->first(['id', 'name']);
                $results = DB::table('user_subject_results')
                    ->join('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
                    ->where('user_subject_results.user_id', $user->id)
                    ->where('user_subject_results.grade_id', $user->grade_id)
                    ->where('user_subject_results.term_id', $latestTermId)
                    ->whereNotNull('user_subject_results.mark')
                    ->select('subjects.name', 'subjects.code', 'subjects.abbreviation', 'user_subject_results.mark', 'user_subject_results.aps_score')
                    ->orderBy('subjects.name')
                    ->get();
            }
        }

        $isLifeOrientation = function (object $result): bool {
            $code = strtoupper($result->code ?? $result->abbreviation ?? '');

            return $code === 'LO' || strcasecmp($result->name, 'Life Orientation') === 0;
        };

        $apsTotal = $results->reject($isLifeOrientation)->sum(fn ($result) => (int) $result->aps_score);
        $averageMark = $results->reject($isLifeOrientation)->avg('mark');

        $apsProgress = DB::table('user_subject_results')
            ->join('grades', 'grades.id', '=', 'user_subject_results.grade_id')
            ->join('terms', 'terms.id', '=', 'user_subject_results.term_id')
            ->join('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
            ->where('user_subject_results.user_id', $user->id)
            ->whereNotNull('user_subject_results.mark')
            ->whereIn('terms.name', ['Term 1', 'Term 2', 'Term 3', 'Term 4'])
            ->where(function ($query) {
                $query
                    ->whereNull('subjects.code')
                    ->orWhereRaw('upper(subjects.code) <> ?', ['LO']);
            })
            ->whereRaw('lower(subjects.name) <> ?', ['life orientation'])
            ->select(
                'grades.id as grade_id',
                'grades.name as grade_name',
                'grades.sort_order as grade_sort_order',
                'terms.id as term_id',
                'terms.name as term_name',
                'terms.from_date',
                DB::raw('sum(coalesce(user_subject_results.aps_score, 0)) as aps_total'),
                DB::raw('count(user_subject_results.id) as reported_subjects'),
            )
            ->groupBy(
                'grades.id',
                'grades.name',
                'grades.sort_order',
                'terms.id',
                'terms.name',
                'terms.from_date',
            )
            ->orderBy('grades.sort_order')
            ->orderBy('terms.from_date')
            ->orderBy('terms.name')
            ->get()
            ->map(function ($progress) {
                $termNumber = (int) filter_var($progress->term_name, FILTER_SANITIZE_NUMBER_INT);

                return (object) [
                    'grade_name' => $progress->grade_name,
                    'term_name' => $progress->term_name,
                    'label' => $progress->grade_name.' T'.$termNumber,
                    'aps_total' => (int) $progress->aps_total,
                    'reported_subjects' => (int) $progress->reported_subjects,
                ];
            });

        $pendingQuizzes = DB::table('exam_sessions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
            ->where('exam_sessions.user_id', $user->id)
            ->whereNull('exam_sessions.completed_at')
            ->select('exam_sessions.id', 'exam_sessions.title', 'exam_sessions.quiz_type', 'exam_sessions.source', 'subjects.name as subject_name', 'exam_sessions.updated_at')
            ->latest('exam_sessions.updated_at')
            ->limit(4)
            ->get();

        $recentAttempts = DB::table('exam_sessions')
            ->leftJoin('subjects', 'subjects.id', '=', 'exam_sessions.subject_id')
            ->where('exam_sessions.user_id', $user->id)
            ->whereNotNull('exam_sessions.completed_at')
            ->select('exam_sessions.id', 'exam_sessions.title', 'exam_sessions.percentage', 'subjects.name as subject_name', 'exam_sessions.completed_at')
            ->latest('exam_sessions.completed_at')
            ->limit(4)
            ->get();

        if (Schema::hasTable('bursary_applications')) {
            $summary = DB::table('bursary_applications')
                ->where('user_id', $user->id)
                ->selectRaw('count(*) as total')
                ->selectRaw("sum(case when status = 'submitted' then 1 else 0 end) as submitted")
                ->selectRaw("sum(case when status = 'postal_ready' then 1 else 0 end) as postal_ready")
                ->selectRaw("sum(case when status = 'failed' then 1 else 0 end) as failed")
                ->first();

            $applicationSummary = (object) [
                'total' => (int) ($summary->total ?? 0),
                'submitted' => (int) ($summary->submitted ?? 0),
                'postal_ready' => (int) ($summary->postal_ready ?? 0),
                'failed' => (int) ($summary->failed ?? 0),
            ];

            $recentBursaryApplications = DB::table('bursary_applications')
                ->leftJoin('bursaries', 'bursaries.id', '=', 'bursary_applications.bursary_id')
                ->leftJoin('companies', 'companies.id', '=', 'bursaries.company_id')
                ->where('bursary_applications.user_id', $user->id)
                ->select(
                    'bursary_applications.id',
                    'bursary_applications.status',
                    'bursary_applications.delivery_type',
                    'bursary_applications.submitted_at',
                    'bursary_applications.receipt_sent_at',
                    'bursary_applications.created_at',
                    'bursaries.id as bursary_id',
                    'bursaries.title as bursary_title',
                    'companies.name as company_name',
                )
                ->selectSub(function ($query) {
                    $query
                        ->from('bursary_application_documents')
                        ->selectRaw('count(*)')
                        ->whereColumn('bursary_application_documents.bursary_application_id', 'bursary_applications.id');
                }, 'documents_count')
                ->latest('bursary_applications.created_at')
                ->limit(5)
                ->get();
        }

        return view('dashboard.index', [
            'user' => $user,
            'selectedSubjects' => $selectedSubjects,
            'latestTerm' => $latestTerm,
            'results' => $results,
            'apsTotal' => $apsTotal,
            'averageMark' => $averageMark,
            'apsProgress' => $apsProgress,
            'pendingQuizzes' => $pendingQuizzes,
            'recentAttempts' => $recentAttempts,
            'applicationSummary' => $applicationSummary,
            'recentBursaryApplications' => $recentBursaryApplications,
        ]);
    })->name('dashboard.index');

    Route::get('/applications', function (Request $request) {
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
    })->name('applications.index');

    Route::get('/profile', function (Request $request) {
        $user = $request->user();

        $userTypes = DB::table('user_types')
            ->select('id', 'name')
            ->whereIn('name', ['pupil', 'student', 'teacher', 'parent'])
            ->orderByRaw("case name when 'pupil' then 1 when 'student' then 2 when 'teacher' then 3 when 'parent' then 4 else 5 end")
            ->get();

        $curriculums = DB::table('curriculums')
            ->select('id', 'name', 'abbreviation')
            ->when(Schema::hasColumn('curriculums', 'is_live'), fn ($query) => $query->where('is_live', true))
            ->orderBy('abbreviation')
            ->get();

        $grades = DB::table('grades')
            ->select('id', 'curriculum_id', 'name', 'sort_order')
            ->orderBy('sort_order')
            ->get();

        $provinces = DB::table('provinces')
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return view('profile.edit', [
            'user' => $user,
            'userTypes' => $userTypes,
            'curriculums' => $curriculums,
            'grades' => $grades,
            'provinces' => $provinces,
        ]);
    })->name('profile.edit');

    Route::put('/profile', function (Request $request) {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'user_type_id' => ['required', 'exists:user_types,id'],
            'curriculum_id' => ['nullable', 'exists:curriculums,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
        ]);

        $userType = DB::table('user_types')
            ->where('id', $data['user_type_id'])
            ->whereIn('name', ['pupil', 'student', 'teacher', 'parent'])
            ->first(['id', 'name']);

        if ($userType === null) {
            return back()
                ->withErrors(['user_type_id' => 'Choose a valid user type.'])
                ->withInput();
        }

        if ($userType->name === 'pupil' && empty($data['curriculum_id'])) {
            return back()
                ->withErrors(['curriculum_id' => 'Choose your curriculum for a high school pupil account.'])
                ->withInput();
        }

        $user->forceFill([
            'user_type_id' => $data['user_type_id'],
            'curriculum_id' => $userType->name === 'pupil' ? $data['curriculum_id'] : null,
            'grade_id' => $userType->name === 'pupil' ? ($data['grade_id'] ?? null) : null,
            'province_id' => $data['province_id'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'username' => $data['username'],
            'email' => $data['email'],
        ])->save();

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profile updated.');
    })->name('profile.update');

    Route::get('/subjects', function (Request $request) {
        $user = $request->user();

        if ($user->grade_id === null) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Choose your grade before selecting subjects.');
        }

        $subjects = DB::table('subjects')
            ->select('id', 'name', 'code', 'abbreviation', 'sort_order')
            ->where('curriculum_id', $user->curriculum_id)
            ->when($user->grade_id !== null, fn ($query) => $query->where('grade_id', $user->grade_id))
            ->when(Schema::hasColumn('subjects', 'is_live'), fn ($query) => $query->where('is_live', true))
            ->orderBy('name')
            ->get();

        $selectedSubjectIds = DB::table('user_subject_preferences')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('subjects.index', [
            'user' => $user,
            'subjects' => $subjects,
            'selectedSubjectIds' => $selectedSubjectIds,
        ]);
    })->name('subjects.index');

    Route::put('/subjects', function (Request $request) {
        $user = $request->user();

        if ($user->grade_id === null) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Choose your grade before selecting subjects.');
        }

        $data = $request->validate([
            'subjects' => ['required', 'array', 'min:7'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
        ], [
            'subjects.required' => 'Select at least 7 subjects.',
            'subjects.min' => 'Select at least 7 subjects.',
        ]);

        $subjectIds = collect($data['subjects'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allowedSubjectIds = DB::table('subjects')
            ->select('id')
            ->where('curriculum_id', $user->curriculum_id)
            ->when($user->grade_id !== null, fn ($query) => $query->where('grade_id', $user->grade_id))
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($allowedSubjectIds->count() < 7) {
            return back()
                ->withInput()
                ->withErrors(['subjects' => 'Select at least 7 subjects from your grade and curriculum.']);
        }

        DB::table('user_subject_preferences')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->delete();

        foreach ($allowedSubjectIds as $index => $subjectId) {
            DB::table('user_subject_preferences')->insert([
                'user_id' => $user->id,
                'curriculum_id' => $user->curriculum_id,
                'grade_id' => $user->grade_id,
                'subject_id' => $subjectId,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('subjects.index')
            ->with('status', 'Subjects updated.');
    })->name('subjects.update');

    Route::get('/course-match', function (Request $request) {
        $user = $request->user();

        if ($user->grade_id === null) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Choose your grade before matching courses.');
        }

        $selectedSubjects = DB::table('user_subject_preferences')
            ->join('subjects', 'subjects.id', '=', 'user_subject_preferences.subject_id')
            ->where('user_subject_preferences.user_id', $user->id)
            ->where('user_subject_preferences.grade_id', $user->grade_id)
            ->select('subjects.id', 'subjects.name', 'subjects.code', 'subjects.abbreviation')
            ->orderBy('subjects.name')
            ->get();

        if ($selectedSubjects->isEmpty()) {
            return redirect()
                ->route('subjects.index')
                ->with('status', 'Select your subjects before matching courses.');
        }

        $terms = DB::table('terms')
            ->where('curriculum_id', $user->curriculum_id)
            ->where('grade_id', $user->grade_id)
            ->orderBy('from_date')
            ->orderBy('name')
            ->get(['id', 'name']);

        $latestResultTermId = DB::table('user_subject_results')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->whereNotNull('mark')
            ->orderByDesc('term_id')
            ->value('term_id');

        $termId = $request->integer('term_id') ?: ($latestResultTermId ?: optional($terms->first())->id);

        $results = DB::table('user_subject_results')
            ->join('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
            ->where('user_subject_results.user_id', $user->id)
            ->where('user_subject_results.grade_id', $user->grade_id)
            ->where('user_subject_results.term_id', $termId)
            ->whereNotNull('user_subject_results.mark')
            ->select(
                'user_subject_results.subject_id',
                'user_subject_results.mark',
                'user_subject_results.aps_score',
                'subjects.name',
                'subjects.code',
                'subjects.abbreviation',
            )
            ->get();

        $resultBySubjectId = $results->keyBy('subject_id');
        $normalise = fn (string $value): string => strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $value));

        $matchingResult = function (object $requirement) use ($results, $resultBySubjectId, $normalise): ?object {
            if ($requirement->subject_id !== null && $resultBySubjectId->has($requirement->subject_id)) {
                return $resultBySubjectId->get($requirement->subject_id);
            }

            $requirementName = $normalise($requirement->subject_name ?? '');

            if (str_contains($requirementName, 'english')) {
                return $results->first(fn ($result) => str_contains($normalise($result->name), 'english'));
            }

            return $results->first(function ($result) use ($requirementName, $normalise) {
                $subjectName = $normalise($result->name);

                return $subjectName === $requirementName
                    || str_contains($requirementName, $subjectName)
                    || str_contains($subjectName, $requirementName);
            });
        };
        $requirementThresholdLabel = function (object $requirement): string {
            if ($requirement->aps_level_required !== null) {
                return 'level '.$requirement->aps_level_required;
            }

            if ($requirement->minimum_mark !== null) {
                return (int) $requirement->minimum_mark.'%';
            }

            return 'required';
        };
        $requirementIsMet = function (?object $result, object $requirement): bool {
            if ($result === null) {
                return false;
            }

            if ($requirement->aps_level_required !== null) {
                return (int) $result->aps_score >= (int) $requirement->aps_level_required;
            }

            if ($requirement->minimum_mark !== null) {
                return (float) $result->mark >= (float) $requirement->minimum_mark;
            }

            return true;
        };

        $isLifeOrientation = function (object $result): bool {
            $code = strtoupper($result->code ?? $result->abbreviation ?? '');

            return $code === 'LO' || strcasecmp($result->name, 'Life Orientation') === 0;
        };

        $apsTotal = $results
            ->reject($isLifeOrientation)
            ->sum(fn ($result) => (int) $result->aps_score);

        $averageMark = $results
            ->reject($isLifeOrientation)
            ->avg('mark');

        $normaliseSubjectName = fn (string $value): string => trim(strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $value)));
        $resultsForAdmissionRule = function (object $rule) use ($results, $isLifeOrientation) {
            return ((bool) $rule->include_life_orientation)
                ? $results
                : $results->reject($isLifeOrientation);
        };
        $selectScoreSubjects = function ($ruleResults, object $rule) {
            $subjectCount = $rule->subject_count === null ? null : (int) $rule->subject_count;

            if ($subjectCount === null) {
                return $ruleResults;
            }

            if (($rule->subject_selection_strategy ?? null) === 'best_subjects') {
                return $ruleResults->sortByDesc('mark')->take($subjectCount);
            }

            return $ruleResults->take($subjectCount);
        };
        $findResultBySubjectName = function (string $subjectName) use ($results, $normaliseSubjectName) {
            $normalisedSubjectName = $normaliseSubjectName($subjectName);

            return $results->first(function ($result) use ($normalisedSubjectName, $normaliseSubjectName) {
                $resultName = $normaliseSubjectName($result->name);

                return $resultName === $normalisedSubjectName
                    || str_contains($normalisedSubjectName, $resultName)
                    || str_contains($resultName, $normalisedSubjectName);
            });
        };
        $pointsForMark = function (float $mark, array $bands): float {
            foreach ($bands as $band) {
                if ($mark >= (float) $band['minimum_mark'] && $mark <= (float) $band['maximum_mark']) {
                    return (float) $band['points'];
                }
            }

            return 0;
        };
        $isLanguageResult = fn (object $result): bool => str_contains($normaliseSubjectName($result->name), 'language')
            || str_contains($normaliseSubjectName($result->name), 'english')
            || str_contains($normaliseSubjectName($result->name), 'afrikaans')
            || str_contains($normaliseSubjectName($result->name), 'isizulu')
            || str_contains($normaliseSubjectName($result->name), 'isixhosa')
            || str_contains($normaliseSubjectName($result->name), 'sesotho')
            || str_contains($normaliseSubjectName($result->name), 'setswana')
            || str_contains($normaliseSubjectName($result->name), 'sepedi')
            || str_contains($normaliseSubjectName($result->name), 'xitsonga')
            || str_contains($normaliseSubjectName($result->name), 'tshivenda');
        $isHomeLanguageResult = fn (object $result): bool => str_contains($normaliseSubjectName($result->name), 'home language');
        $isMathematicsFamilyResult = fn (object $result): bool => in_array($normaliseSubjectName($result->name), [
            'mathematics',
            'mathematical literacy',
            'technical mathematics',
        ], true);
        $nscPassType = function ($ruleResults) use ($isLifeOrientation, $isHomeLanguageResult) {
            $subjects = $ruleResults->reject($isLifeOrientation);
            $homeLanguagePassed = $subjects->contains(fn ($result) => $isHomeLanguageResult($result) && (float) $result->mark >= 40);
            $subjectsAt50 = $subjects->filter(fn ($result) => (float) $result->mark >= 50)->count();
            $subjectsAt40 = $subjects->filter(fn ($result) => (float) $result->mark >= 40)->count();
            $subjectsAt30 = $subjects->filter(fn ($result) => (float) $result->mark >= 30)->count();

            return match (true) {
                $homeLanguagePassed && $subjectsAt50 >= 4 && $subjectsAt30 >= 6 => 'bachelor',
                $homeLanguagePassed && $subjectsAt40 >= 4 && $subjectsAt30 >= 6 => 'diploma',
                $homeLanguagePassed && $subjectsAt40 >= 3 && $subjectsAt30 >= 6 => 'higher_certificate',
                $homeLanguagePassed && $subjectsAt40 >= 3 && $subjectsAt30 >= 6 => 'nsc',
                default => 'none',
            };
        };
        $seniorCertificatePassed = function ($ruleResults) use ($isHomeLanguageResult, $isLanguageResult) {
            return $ruleResults->filter(fn ($result) => (float) $result->mark >= 40)->count() >= 3
                && $ruleResults->contains(fn ($result) => $isHomeLanguageResult($result) && (float) $result->mark >= 40)
                && $ruleResults->filter(fn ($result) => (float) $result->mark >= 30)->count() >= 5
                && $ruleResults->contains(fn ($result) => $isLanguageResult($result) && (float) $result->mark >= 30)
                && $ruleResults->filter(fn ($result) => (float) $result->mark >= 20)->count() >= 6;
        };
        $nmuApplicantScore = function ($ruleResults, array $config) use ($isHomeLanguageResult, $isLanguageResult, $isMathematicsFamilyResult, $isLifeOrientation): float {
            $eligibleSubjects = $ruleResults->reject($isLifeOrientation)->values();
            $selected = collect();
            $selectedSubjectIds = [];

            $addResult = function (?object $result) use (&$selected, &$selectedSubjectIds): void {
                if ($result === null || in_array((int) $result->subject_id, $selectedSubjectIds, true)) {
                    return;
                }

                $selected->push($result);
                $selectedSubjectIds[] = (int) $result->subject_id;
            };

            $addResult($eligibleSubjects
                ->filter($isHomeLanguageResult)
                ->sortByDesc('mark')
                ->first());

            $addResult($eligibleSubjects
                ->reject($isHomeLanguageResult)
                ->filter($isLanguageResult)
                ->sortByDesc('mark')
                ->first());

            $addResult($eligibleSubjects
                ->filter($isMathematicsFamilyResult)
                ->sortByDesc('mark')
                ->first());

            $eligibleSubjects
                ->reject(fn ($result) => in_array((int) $result->subject_id, $selectedSubjectIds, true))
                ->sortByDesc('mark')
                ->take(max(6 - $selected->count(), 0))
                ->each($addResult);

            $score = (float) $selected->take(6)->sum(fn ($result) => (float) $result->mark);

            if (($config['life_orientation_bonus']['apply_without_quintile_data'] ?? false) === true) {
                $lifeOrientation = $ruleResults->first($isLifeOrientation);
                $minimumMark = (float) ($config['life_orientation_bonus']['minimum_mark'] ?? 50);

                if ($lifeOrientation !== null && (float) $lifeOrientation->mark >= $minimumMark) {
                    $score += (float) ($config['life_orientation_bonus']['points'] ?? 7);
                }
            }

            return $score;
        };
        $scoreForAdmissionRule = function (?object $rule) use ($resultsForAdmissionRule, $selectScoreSubjects, $findResultBySubjectName, $normaliseSubjectName, $pointsForMark, $nscPassType, $seniorCertificatePassed, $nmuApplicantScore) {
            if ($rule === null) {
                return ['actual' => null, 'missing_components' => []];
            }

            $ruleResults = $resultsForAdmissionRule($rule);
            $scoreSubjects = $selectScoreSubjects($ruleResults, $rule);
            $config = is_array($rule->config ?? null)
                ? $rule->config
                : (json_decode($rule->config ?? '[]', true) ?: []);
            $achievedNscPassType = $rule->calculation_method === 'nsc_pass_type'
                ? $nscPassType($ruleResults)
                : null;
            $achievedSeniorCertificatePassType = $rule->calculation_method === 'senior_certificate_pass' && $seniorCertificatePassed($ruleResults)
                ? 'senior_certificate'
                : 'none';

            return match ($rule->calculation_method) {
                'aps_level_sum' => [
                    'actual' => (float) $scoreSubjects->sum(fn ($result) => (int) $result->aps_score),
                    'missing_components' => [],
                ],
                'average_mark' => [
                    'actual' => $scoreSubjects->isEmpty() ? null : (float) $scoreSubjects->avg('mark'),
                    'missing_components' => [],
                ],
                'raw_mark_sum' => [
                    'actual' => (float) $scoreSubjects->sum(fn ($result) => (float) $result->mark)
                        / max((float) ($config['score_divisor'] ?? 1), 1),
                    'missing_components' => [],
                ],
                'weighted_mark_sum' => [
                    'actual' => (float) $scoreSubjects->sum(fn ($result) => (float) $result->mark)
                        + collect($config['additional_subject_weights'] ?? [])->sum(function ($weight) use ($findResultBySubjectName) {
                            $result = $findResultBySubjectName($weight['subject'] ?? '');

                            return $result === null ? 0 : ((float) $result->mark * (float) ($weight['additional_weight'] ?? 0));
                    }),
                    'missing_components' => [],
                ],
                'nmu_applicant_score' => [
                    'actual' => $nmuApplicantScore($ruleResults, $config),
                    'missing_components' => [],
                ],
                'subject_point_sum' => [
                    'actual' => (float) $scoreSubjects->sum(function ($result) use ($config, $normaliseSubjectName, $pointsForMark) {
                        $subjectName = $normaliseSubjectName($result->name);
                        $scale = collect($config['subject_point_scales'] ?? [])
                            ->first(function ($scale) use ($subjectName, $normaliseSubjectName) {
                                return collect($scale['subjects'] ?? [])
                                    ->contains(fn ($subject) => $normaliseSubjectName($subject) === $subjectName);
                            });
                        $scale ??= $config['default_point_scale'] ?? [];

                        return $pointsForMark((float) $result->mark, $scale['bands'] ?? []);
                    }),
                    'missing_components' => [],
                ],
                'composite_sum' => [
                    'actual' => (float) $scoreSubjects->sum(fn ($result) => (float) $result->mark),
                    'missing_components' => collect($config['components'] ?? [])
                        ->filter(fn ($component) => ($component['method'] ?? null) === 'external_sum' && ($component['required'] ?? false))
                        ->pluck('label')
                        ->values()
                        ->all(),
                ],
                'nsc_pass_type' => [
                    'actual' => (float) ($config['ranking'][$achievedNscPassType] ?? 0),
                    'pass_type' => $achievedNscPassType,
                    'missing_components' => [],
                ],
                'senior_certificate_pass' => [
                    'actual' => (float) ($config['ranking'][$achievedSeniorCertificatePassType] ?? 0),
                    'pass_type' => $achievedSeniorCertificatePassType,
                    'missing_components' => [],
                ],
                default => [
                    'actual' => null,
                    'missing_components' => [],
                ],
            };
        };

        $filterUniversityId = $request->integer('university_id') ?: null;
        $filterFacultyId = $request->integer('faculty_id') ?: null;
        $filterQualificationTypeId = $request->integer('qualification_type_id') ?: null;

        $hasStatusFilters = $request->hasAny([
            'hide_not_qualified',
            'show_almost_there',
            'show_not_qualified_yet',
        ]);
        $hideNotQualified = $hasStatusFilters ? $request->boolean('hide_not_qualified') : true;
        $showAlmostThere = $hasStatusFilters ? $request->boolean('show_almost_there') : true;
        $showNotQualifiedYet = $hasStatusFilters ? $request->boolean('show_not_qualified_yet') : true;
        $allStatusFiltersSelected = $hideNotQualified && $showAlmostThere && $showNotQualifiedYet;
        $search = trim((string) $request->query('search', ''));
        $perPageOptions = [10, 25, 50, 100];
        $perPage = $request->integer('per_page', 25);
        $perPage = in_array($perPage, $perPageOptions, true) ? $perPage : 25;

        $universities = DB::table('universities')
            ->select('id', 'name', 'abbreviation')
            ->orderBy('name')
            ->get();

        $faculties = DB::table('faculties')
            ->join('universities', 'universities.id', '=', 'faculties.university_id')
            ->select('faculties.id', 'faculties.name', 'universities.abbreviation as university_abbreviation')
            ->orderBy('universities.name')
            ->orderBy('faculties.name')
            ->get();

        $qualificationTypes = DB::table('qualification_types')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $qualifications = DB::table('qualifications')
            ->join('universities', 'universities.id', '=', 'qualifications.university_id')
            ->join('faculties', 'faculties.id', '=', 'qualifications.faculty_id')
            ->join('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
            ->select(
                'qualifications.*',
                'universities.name as university_name',
                'universities.abbreviation as university_abbreviation',
                'universities.default_closing_month',
                'universities.default_closing_day',
                'faculties.name as faculty_name',
                'faculties.closing_month as faculty_closing_month',
                'faculties.closing_day as faculty_closing_day',
                'qualification_types.name as qualification_type_name',
            )
            ->orderBy('universities.name')
            ->orderBy('faculties.name')
            ->orderBy('qualifications.name')
            ->get();

        $requirementsByQualification = DB::table('qualification_subject_requirements')
            ->whereIn('qualification_id', $qualifications->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('qualification_id');

        $admissionScoreVariantsByQualification = DB::table('qualification_admission_score_variants')
            ->whereIn('qualification_id', $qualifications->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('qualification_id');

        $admissionRuleAssignments = DB::table('university_admission_rules')
            ->join('admission_rules', 'admission_rules.id', '=', 'university_admission_rules.admission_rule_id')
            ->whereIn('university_admission_rules.university_id', $qualifications->pluck('university_id')->unique())
            ->where('admission_rules.is_active', true)
            ->select(
                'university_admission_rules.*',
                'admission_rules.code',
                'admission_rules.name',
                'admission_rules.score_type',
                'admission_rules.calculation_method',
                'admission_rules.score_label',
                'admission_rules.score_suffix',
                'admission_rules.max_score',
                'admission_rules.include_life_orientation',
                'admission_rules.subject_count',
                'admission_rules.subject_selection_strategy',
                'admission_rules.minimum_pass_type as rule_minimum_pass_type',
                'admission_rules.config',
            )
            ->get()
            ->map(function ($assignment) {
                $assignment->config = json_decode($assignment->config ?? '[]', true) ?: [];
                $assignment->overrides = json_decode($assignment->overrides ?? '[]', true) ?: [];
                $assignment->config = array_replace_recursive($assignment->config, $assignment->overrides);

                return $assignment;
            });

        $admissionRuleForQualification = function (object $qualification) use ($admissionRuleAssignments): ?object {
            return $admissionRuleAssignments
                ->filter(function ($assignment) use ($qualification) {
                    if ((int) $assignment->university_id !== (int) $qualification->university_id) {
                        return false;
                    }

                    if ($assignment->qualification_id !== null && (int) $assignment->qualification_id !== (int) $qualification->id) {
                        return false;
                    }

                    if ($assignment->faculty_id !== null && (int) $assignment->faculty_id !== (int) $qualification->faculty_id) {
                        return false;
                    }

                    return true;
                })
                ->sortBy([
                    fn ($assignment) => (int) $assignment->priority,
                    fn ($assignment) => $assignment->qualification_id !== null ? -3 : ($assignment->faculty_id !== null ? -2 : -1),
                ])
                ->first();
        };

        $applicationYear = now()->year + 1;
        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $matches = $qualifications->map(function ($qualification) use (
            $requirementsByQualification,
            $admissionScoreVariantsByQualification,
            $matchingResult,
            $admissionRuleForQualification,
            $scoreForAdmissionRule,
            $requirementThresholdLabel,
            $requirementIsMet,
            $applicationYear,
            $monthNames
        ) {
            $requirements = $requirementsByQualification->get($qualification->id, collect());
            $admissionScoreVariants = $admissionScoreVariantsByQualification->get($qualification->id, collect());
            $missing = [];
            $met = [];

            $groupedRequirements = $requirements->groupBy(
                fn ($requirement) => $requirement->requirement_group ?: 'requirement_'.$requirement->id
            );

            foreach ($groupedRequirements as $requirementGroup) {
                $passedRequirement = null;
                $groupMessages = [];
                $firstRequirement = $requirementGroup->first();

                if (($firstRequirement->requirement_type ?? null) === 'subject_group_count_choice') {
                    $choiceGroups = $requirementGroup->groupBy(function ($requirement) {
                        $config = json_decode($requirement->notes ?? '[]', true) ?: [];

                        return $config['choice_key'] ?? 'choice';
                    });
                    $passedChoice = null;
                    $choiceLabels = [];

                    foreach ($choiceGroups as $choiceGroup) {
                        $choiceConfig = json_decode($choiceGroup->first()->notes ?? '[]', true) ?: [];
                        $requiredCount = (int) ($choiceConfig['required_count'] ?? 1);
                        $thresholdLabel = $requirementThresholdLabel($choiceGroup->first());
                        $choiceLabels[] = trim($choiceConfig['label'] ?? $choiceGroup->pluck('subject_name')->filter()->implode(', '));
                        $passedRequirements = $choiceGroup->filter(function ($requirement) use ($matchingResult, $requirementIsMet) {
                            $result = $matchingResult($requirement);

                            return $requirementIsMet($result, $requirement);
                        });

                        if ($passedRequirements->count() >= $requiredCount) {
                            $passedChoice = [
                                'count' => $requiredCount,
                                'label' => trim($choiceConfig['label'] ?? 'listed subjects'),
                                'threshold' => $thresholdLabel,
                            ];
                            break;
                        }
                    }

                    if ($passedChoice !== null) {
                        $met[] = $passedChoice['count'].' from '.$passedChoice['label'].' '.$passedChoice['threshold'];
                    } else {
                        $missing[] = 'Required subject combination: '.implode(' OR ', $choiceLabels);
                    }

                    continue;
                }

                if (($firstRequirement->requirement_type ?? null) === 'subject_group_count') {
                    $groupConfig = json_decode($firstRequirement->notes ?? '[]', true) ?: [];
                    $requiredCount = (int) ($groupConfig['required_count'] ?? 1);
                    $thresholdLabel = $requirementThresholdLabel($firstRequirement);
                    $passedRequirements = $requirementGroup->filter(function ($requirement) use ($matchingResult, $requirementIsMet) {
                        $result = $matchingResult($requirement);

                        return $requirementIsMet($result, $requirement);
                    });

                    if ($passedRequirements->count() >= $requiredCount) {
                        $met[] = $requiredCount.' of '.trim($groupConfig['label'] ?? 'listed subjects').' '.$thresholdLabel;
                    } else {
                        $remainingCount = max($requiredCount - $passedRequirements->count(), 0);
                        $missing[] = $remainingCount.' more of: '.$requirementGroup
                            ->pluck('subject_name')
                            ->filter()
                            ->implode(', ')
                            .' '.$thresholdLabel;
                    }

                    continue;
                }

                foreach ($requirementGroup as $requirement) {
                    $result = $matchingResult($requirement);
                    $message = trim(($requirement->subject_name ?? 'Subject').' '.$requirementThresholdLabel($requirement));

                    if ($requirementIsMet($result, $requirement)) {
                        $passedRequirement = $requirement;
                        break;
                    }

                    $groupMessages[] = $message;
                }

                if ($passedRequirement !== null) {
                    $met[] = trim(($passedRequirement->subject_name ?? 'Subject').' '.$requirementThresholdLabel($passedRequirement));
                } else {
                    $missing[] = implode(' or ', $groupMessages);
                }
            }

            $admissionRule = $admissionRuleForQualification($qualification);
            $ruleScore = $scoreForAdmissionRule($admissionRule);
            $usesAggregateAverage = ($admissionRule->score_type ?? null) === 'aggregate_average';
            $usesPassType = ($admissionRule->score_type ?? null) === 'pass_type';
            $admissionScoreType = $admissionRule->score_type
                ?? ($qualification->aggregate_average_required !== null ? 'aggregate_average' : 'aps');
            $admissionScoreLabel = $admissionRule->score_label
                ?? ($usesAggregateAverage ? 'Aggregated average' : 'APS');
            $admissionScoreSuffix = $admissionRule->score_suffix ?? ($usesAggregateAverage ? '%' : '');
            $ruleConfig = $admissionRule->config ?? [];
            $passTypeRanking = $ruleConfig['ranking'] ?? [
                'none' => 0,
                'senior_certificate' => 1,
                'nsc' => 1,
                'higher_certificate' => 2,
                'diploma' => 3,
                'bachelor' => 4,
            ];
            $passTypeLabels = [
                'none' => 'No pass yet',
                'senior_certificate' => 'Senior Certificate pass',
                'nsc' => 'NSC pass',
                'higher_certificate' => 'Higher Certificate pass',
                'diploma' => 'Diploma pass',
                'bachelor' => 'Bachelor pass',
            ];
            $requiredPassType = $qualification->minimum_pass_type ?? $admissionRule->rule_minimum_pass_type ?? null;
            $admissionScoreVariant = $admissionScoreVariants
                ->filter(function ($variant) use ($matchingResult, $requirementIsMet) {
                    return $requirementIsMet($matchingResult($variant), $variant);
                })
                ->sortBy('admission_score_required')
                ->first();
            $fallbackAdmissionScoreVariant = $admissionScoreVariants
                ->sortBy('admission_score_required')
                ->first();
            if ($usesPassType) {
                $admissionScoreRequired = $requiredPassType === null ? null : (float) ($passTypeRanking[$requiredPassType] ?? 0);
            } elseif ($admissionScoreVariant !== null) {
                $admissionScoreRequired = (float) $admissionScoreVariant->admission_score_required;
            } elseif ($qualification->admission_score_required !== null) {
                $admissionScoreRequired = (float) $qualification->admission_score_required;
            } elseif ($fallbackAdmissionScoreVariant !== null) {
                $admissionScoreRequired = (float) $fallbackAdmissionScoreVariant->admission_score_required;
            } elseif ($usesAggregateAverage) {
                $admissionScoreRequired = $qualification->aggregate_average_required === null ? null : (float) $qualification->aggregate_average_required;
            } else {
                $admissionScoreRequired = $qualification->aps_required === null ? null : (float) $qualification->aps_required;
            }
            $admissionScoreActual = $ruleScore['actual'];
            $admissionScoreGap = $admissionScoreRequired === null
                ? 0
                : max($admissionScoreRequired - ($admissionScoreActual ?? 0), 0);
            $hasScoreRequirement = $admissionScoreRequired !== null;
            $hasSubjectRequirements = $requirements->isNotEmpty();
            $hasMachineCheckableRequirements = $hasScoreRequirement || $hasSubjectRequirements;
            $formatAdmissionScore = fn (float $value): string => $admissionScoreSuffix === '%'
                ? rtrim(rtrim(number_format($value, 1), '0'), '.').$admissionScoreSuffix
                : number_format($value, 0);
            $requiredPassTypeDisplay = $requiredPassType === null ? 'N/A' : ($passTypeLabels[$requiredPassType] ?? $requiredPassType);
            $actualPassTypeDisplay = $passTypeLabels[$ruleScore['pass_type'] ?? 'none'] ?? ($ruleScore['pass_type'] ?? 'No pass yet');
            $closingMonth = $qualification->closing_month
                ?? $qualification->faculty_closing_month
                ?? $qualification->default_closing_month;
            $closingDay = $qualification->closing_day
                ?? $qualification->faculty_closing_day
                ?? $qualification->default_closing_day;

            $qualification->requirements = $requirements;
            $qualification->met_requirements = $met;
            $qualification->missing_requirements = $missing;
            $qualification->admission_score_type = $admissionScoreType;
            $qualification->admission_score_label = $admissionScoreLabel;
            $qualification->admission_score_suffix = $admissionScoreSuffix;
            $qualification->admission_rule_code = $admissionRule->code ?? null;
            $qualification->admission_rule_name = $admissionRule->name ?? null;
            $qualification->admission_score_variant_label = $admissionScoreVariant->label ?? null;
            $qualification->missing_score_components = $ruleScore['missing_components'];
            $qualification->admission_score_required = $admissionScoreRequired;
            $qualification->admission_score_actual = $admissionScoreActual;
            $qualification->admission_score_gap = $admissionScoreGap;
            $qualification->admission_score_required_display = $admissionScoreRequired === null
                ? ($hasMachineCheckableRequirements ? 'N/A' : 'See notes')
                : ($usesPassType ? $requiredPassTypeDisplay : $formatAdmissionScore($admissionScoreRequired));
            $qualification->admission_score_actual_display = $usesPassType
                ? $actualPassTypeDisplay
                : ($admissionScoreActual === null ? 'N/A' : $formatAdmissionScore($admissionScoreActual));
            $qualification->admission_score_gap_display = $usesPassType
                ? ($admissionScoreGap === 0 ? 'Met' : 'Not met')
                : ($hasMachineCheckableRequirements ? $formatAdmissionScore($admissionScoreGap) : 'Review');
            $qualification->aps_gap = $admissionScoreGap;
            $qualification->aps_met = $admissionScoreGap === 0;
            $qualification->subject_requirements_met = count($missing) === 0;
            $qualification->requires_manual_review = ! $hasMachineCheckableRequirements;
            $qualification->is_match = $hasMachineCheckableRequirements && $admissionScoreGap === 0 && count($missing) === 0;
            $qualification->is_almost_there = ! $qualification->is_match
                && ! $qualification->requires_manual_review
                && ($qualification->aps_met || $qualification->subject_requirements_met);
            $qualification->closing_label = ($closingMonth && $closingDay)
                ? $closingDay.' '.($monthNames[(int) $closingMonth] ?? '').' '.$applicationYear
                : 'Not listed';

            return $qualification;
        });

        $totalMatchesBeforeFilters = $matches->count();
        $qualifiedCountBeforeFilters = $matches->where('is_match', true)->count();

        $matches = $matches
            ->filter(function ($qualification) use (
                $filterUniversityId,
                $filterFacultyId,
                $filterQualificationTypeId,
                $hideNotQualified,
                $showAlmostThere,
                $showNotQualifiedYet,
                $allStatusFiltersSelected
            ) {
                if ($filterUniversityId !== null && (int) $qualification->university_id !== $filterUniversityId) {
                    return false;
                }

                if ($filterFacultyId !== null && (int) $qualification->faculty_id !== $filterFacultyId) {
                    return false;
                }

                if ($filterQualificationTypeId !== null && (int) $qualification->qualification_type_id !== $filterQualificationTypeId) {
                    return false;
                }

                if (! $allStatusFiltersSelected && ($hideNotQualified || $showAlmostThere || $showNotQualifiedYet)) {
                    return ($hideNotQualified && $qualification->is_match)
                        || ($showAlmostThere && $qualification->is_almost_there)
                        || ($showNotQualifiedYet && ! $qualification->is_match && ! $qualification->is_almost_there);
                }

                return true;
            })
            ->sortBy([
            ['is_match', 'desc'],
            ['aps_met', 'desc'],
            ['aps_gap', 'asc'],
            ['university_name', 'asc'],
            ['name', 'asc'],
        ])->values();

        if ($search !== '') {
            $searchNeedle = $normalise($search);

            $matches = $matches
                ->filter(function ($qualification) use ($searchNeedle, $normalise) {
                    $haystack = $normalise(implode(' ', array_filter([
                        $qualification->name ?? '',
                        $qualification->university_name ?? '',
                        $qualification->university_abbreviation ?? '',
                        $qualification->faculty_name ?? '',
                        $qualification->qualification_type_name ?? '',
                        $qualification->notes ?? '',
                    ])));

                    return str_contains($haystack, $searchNeedle);
                })
                ->values();
        }

        $visibleMatchesCount = $matches->count();
        $page = max(1, $request->integer('page', 1));
        $lastPage = max(1, (int) ceil($visibleMatchesCount / $perPage));
        $page = min($page, $lastPage);
        $paginatedMatches = new LengthAwarePaginator(
            $matches->forPage($page, $perPage)->values(),
            $visibleMatchesCount,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return view('course-match.index', [
            'user' => $user,
            'terms' => $terms,
            'termId' => $termId,
            'results' => $results,
            'apsTotal' => $apsTotal,
            'averageMark' => $averageMark,
            'universities' => $universities,
            'faculties' => $faculties,
            'qualificationTypes' => $qualificationTypes,
            'matches' => $paginatedMatches,
            'visibleMatchesCount' => $visibleMatchesCount,
            'matchedCount' => $matches->where('is_match', true)->count(),
            'totalMatchesBeforeFilters' => $totalMatchesBeforeFilters,
            'qualifiedCountBeforeFilters' => $qualifiedCountBeforeFilters,
            'applicationYear' => $applicationYear,
            'perPageOptions' => $perPageOptions,
            'perPage' => $perPage,
            'search' => $search,
            'filters' => [
                'university_id' => $filterUniversityId,
                'faculty_id' => $filterFacultyId,
                'qualification_type_id' => $filterQualificationTypeId,
                'hide_not_qualified' => $hideNotQualified,
                'show_almost_there' => $showAlmostThere,
                'show_not_qualified_yet' => $showNotQualifiedYet,
            ],
        ]);
    })->name('course-match.index');

    Route::get('/universities/{university}/programmes', function (Request $request, int $university) {
        $university = DB::table('universities')
            ->where('id', $university)
            ->first();

        abort_if($university === null, 404);

        $search = trim((string) $request->query('search', ''));
        $facultyId = $request->integer('faculty_id') ?: null;
        $qualificationTypeId = $request->integer('qualification_type_id') ?: null;
        $perPageOptions = [12, 24, 48, 96];
        $perPage = $request->integer('per_page', 24);
        $perPage = in_array($perPage, $perPageOptions, true) ? $perPage : 24;

        $faculties = DB::table('faculties')
            ->where('university_id', $university->id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $qualificationTypes = DB::table('qualification_types')
            ->join('qualifications', 'qualifications.qualification_type_id', '=', 'qualification_types.id')
            ->where('qualifications.university_id', $university->id)
            ->select('qualification_types.id', 'qualification_types.name')
            ->distinct()
            ->orderBy('qualification_types.name')
            ->get();

        $baseQualifications = DB::table('qualifications')
            ->where('university_id', $university->id);

        $stats = [
            'programmes' => (clone $baseQualifications)->count(),
            'faculties' => $faculties->count(),
            'qualification_types' => $qualificationTypes->count(),
            'selection_programmes' => (clone $baseQualifications)->where('is_selection_programme', true)->count(),
        ];

        $scoreStats = (clone $baseQualifications)
            ->where(function ($query) {
                $query->whereNotNull('admission_score_required')
                    ->orWhereNotNull('aggregate_average_required')
                    ->orWhereNotNull('aps_required')
                    ->orWhereNotNull('minimum_pass_type');
            })
            ->selectRaw('count(*) as listed_count')
            ->first();

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        $applicationYear = now()->year + 1;

        $admissionRuleAssignments = DB::table('university_admission_rules')
            ->join('admission_rules', 'admission_rules.id', '=', 'university_admission_rules.admission_rule_id')
            ->where('university_admission_rules.university_id', $university->id)
            ->where('admission_rules.is_active', true)
            ->select(
                'university_admission_rules.*',
                'admission_rules.score_type',
                'admission_rules.score_label',
                'admission_rules.score_suffix',
                'admission_rules.minimum_pass_type as rule_minimum_pass_type',
            )
            ->get();

        $admissionRuleForQualification = function (object $qualification) use ($admissionRuleAssignments): ?object {
            return $admissionRuleAssignments
                ->filter(function ($assignment) use ($qualification) {
                    if ($assignment->qualification_id !== null && (int) $assignment->qualification_id !== (int) $qualification->id) {
                        return false;
                    }

                    if ($assignment->faculty_id !== null && (int) $assignment->faculty_id !== (int) $qualification->faculty_id) {
                        return false;
                    }

                    return true;
                })
                ->sortBy([
                    fn ($assignment) => (int) $assignment->priority,
                    fn ($assignment) => $assignment->qualification_id !== null ? -3 : ($assignment->faculty_id !== null ? -2 : -1),
                ])
                ->first();
        };

        $qualificationsQuery = DB::table('qualifications')
            ->join('faculties', 'faculties.id', '=', 'qualifications.faculty_id')
            ->join('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
            ->where('qualifications.university_id', $university->id)
            ->when($facultyId !== null, fn ($query) => $query->where('qualifications.faculty_id', $facultyId))
            ->when($qualificationTypeId !== null, fn ($query) => $query->where('qualifications.qualification_type_id', $qualificationTypeId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('qualifications.name', 'like', '%'.$search.'%')
                        ->orWhere('qualifications.abbreviation', 'like', '%'.$search.'%')
                        ->orWhere('qualifications.notes', 'like', '%'.$search.'%')
                        ->orWhere('faculties.name', 'like', '%'.$search.'%')
                        ->orWhere('qualification_types.name', 'like', '%'.$search.'%');
                });
            })
            ->select(
                'qualifications.*',
                'faculties.name as faculty_name',
                'faculties.closing_month as faculty_closing_month',
                'faculties.closing_day as faculty_closing_day',
                'qualification_types.name as qualification_type_name',
                DB::raw('(select count(*) from qualification_subject_requirements where qualification_subject_requirements.qualification_id = qualifications.id) as subject_requirement_count'),
            )
            ->orderBy('faculties.name')
            ->orderBy('qualifications.name');

        $qualifications = $qualificationsQuery
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($qualification) use (
                $admissionRuleForQualification,
                $applicationYear,
                $monthNames,
                $university
            ) {
                $admissionRule = $admissionRuleForQualification($qualification);
                $usesAggregateAverage = ($admissionRule->score_type ?? null) === 'aggregate_average';
                $usesPassType = ($admissionRule->score_type ?? null) === 'pass_type';
                $requiredPassType = $qualification->minimum_pass_type ?? $admissionRule->rule_minimum_pass_type ?? null;
                $passTypeLabels = [
                    'senior_certificate' => 'Senior Certificate pass',
                    'nsc' => 'NSC pass',
                    'higher_certificate' => 'Higher Certificate pass',
                    'diploma' => 'Diploma pass',
                    'bachelor' => 'Bachelor pass',
                ];
                $admissionScoreRequired = $usesPassType
                    ? null
                    : ($qualification->admission_score_required !== null
                        ? (float) $qualification->admission_score_required
                        : ($usesAggregateAverage
                            ? ($qualification->aggregate_average_required === null ? null : (float) $qualification->aggregate_average_required)
                            : ($qualification->aps_required === null ? null : (float) $qualification->aps_required)));
                $admissionScoreSuffix = $admissionRule->score_suffix ?? ($usesAggregateAverage ? '%' : '');
                $qualification->admission_score_label = $admissionRule->score_label ?? ($usesAggregateAverage ? 'Aggregate average' : 'APS');
                $qualification->admission_score_display = $usesPassType
                    ? ($passTypeLabels[$requiredPassType] ?? 'Pass required')
                    : ($admissionScoreRequired === null
                        ? 'Not listed'
                        : ($admissionScoreSuffix === '%'
                            ? rtrim(rtrim(number_format($admissionScoreRequired, 1), '0'), '.').$admissionScoreSuffix
                            : number_format($admissionScoreRequired, 0)));

                $closingMonth = $qualification->closing_month
                    ?? $qualification->faculty_closing_month
                    ?? $university->default_closing_month;
                $closingDay = $qualification->closing_day
                    ?? $qualification->faculty_closing_day
                    ?? $university->default_closing_day;

                $qualification->closing_label = ($closingMonth && $closingDay)
                    ? $closingDay.' '.($monthNames[(int) $closingMonth] ?? '').' '.$applicationYear
                    : 'Not listed';

                return $qualification;
            });

        return view('universities.programmes', [
            'university' => $university,
            'faculties' => $faculties,
            'qualificationTypes' => $qualificationTypes,
            'qualifications' => $qualifications,
            'stats' => $stats,
            'listedScoreCount' => (int) ($scoreStats->listed_count ?? 0),
            'search' => $search,
            'filters' => [
                'faculty_id' => $facultyId,
                'qualification_type_id' => $qualificationTypeId,
            ],
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
        ]);
    })->name('universities.programmes');

    Route::get('/courses/{qualification}', function (Request $request, int $qualification) {
        $course = DB::table('qualifications')
            ->join('universities', 'universities.id', '=', 'qualifications.university_id')
            ->join('faculties', 'faculties.id', '=', 'qualifications.faculty_id')
            ->join('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
            ->where('qualifications.id', $qualification)
            ->select(
                'qualifications.*',
                'universities.name as university_name',
                'universities.abbreviation as university_abbreviation',
                'universities.logo as university_logo',
                'universities.website as university_website',
                'universities.default_closing_month',
                'universities.default_closing_day',
                'faculties.name as faculty_name',
                'faculties.closing_month as faculty_closing_month',
                'faculties.closing_day as faculty_closing_day',
                'qualification_types.name as qualification_type_name',
            )
            ->first();

        abort_if($course === null, 404);

        $requirements = DB::table('qualification_subject_requirements')
            ->where('qualification_id', $course->id)
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($requirement) => $requirement->requirement_group ?: 'requirement_'.$requirement->id);

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        $closingMonth = $course->closing_month ?? $course->faculty_closing_month ?? $course->default_closing_month;
        $closingDay = $course->closing_day ?? $course->faculty_closing_day ?? $course->default_closing_day;
        $applicationYear = now()->year + 1;
        $admissionRule = DB::table('university_admission_rules')
            ->join('admission_rules', 'admission_rules.id', '=', 'university_admission_rules.admission_rule_id')
            ->where('university_admission_rules.university_id', $course->university_id)
            ->where('admission_rules.is_active', true)
            ->where(function ($query) use ($course) {
                $query
                    ->where('university_admission_rules.qualification_id', $course->id)
                    ->orWhere(function ($query) use ($course) {
                        $query
                            ->whereNull('university_admission_rules.qualification_id')
                            ->where('university_admission_rules.faculty_id', $course->faculty_id);
                    })
                    ->orWhere(function ($query) {
                        $query
                            ->whereNull('university_admission_rules.qualification_id')
                            ->whereNull('university_admission_rules.faculty_id');
                    });
            })
            ->select(
                'university_admission_rules.priority',
                'university_admission_rules.faculty_id',
                'university_admission_rules.qualification_id',
                'admission_rules.score_type',
                'admission_rules.score_label',
                'admission_rules.score_suffix',
                'admission_rules.minimum_pass_type as rule_minimum_pass_type',
            )
            ->get()
            ->sortBy([
                fn ($rule) => (int) $rule->priority,
                fn ($rule) => $rule->qualification_id !== null ? -3 : ($rule->faculty_id !== null ? -2 : -1),
            ])
            ->first();
        $usesAggregateAverage = ($admissionRule->score_type ?? null) === 'aggregate_average';
        $usesPassType = ($admissionRule->score_type ?? null) === 'pass_type';
        $requiredPassType = $course->minimum_pass_type ?? $admissionRule->rule_minimum_pass_type ?? null;
        $passTypeLabels = [
            'senior_certificate' => 'Senior Certificate pass',
            'nsc' => 'NSC pass',
            'higher_certificate' => 'Higher Certificate pass',
            'diploma' => 'Diploma pass',
            'bachelor' => 'Bachelor pass',
        ];
        $admissionScoreRequired = $usesPassType
            ? null
            : ($course->admission_score_required !== null
                ? (float) $course->admission_score_required
                : ($usesAggregateAverage
                    ? ($course->aggregate_average_required === null ? null : (float) $course->aggregate_average_required)
                    : ($course->aps_required === null ? null : (float) $course->aps_required)));
        $admissionScoreSuffix = $admissionRule->score_suffix ?? ($usesAggregateAverage ? '%' : '');
        $admissionScoreDisplay = $usesPassType
            ? ($passTypeLabels[$requiredPassType] ?? 'Pass required')
            : ($admissionScoreRequired === null
                ? 'N/A'
                : ($admissionScoreSuffix === '%'
                ? rtrim(rtrim(number_format($admissionScoreRequired, 1), '0'), '.').$admissionScoreSuffix
                : number_format($admissionScoreRequired, 0)));

        return view('courses.show', [
            'course' => $course,
            'requirements' => $requirements,
            'admissionScoreLabel' => $admissionRule->score_label ?? ($usesAggregateAverage ? 'Aggregate average' : 'APS'),
            'admissionScoreDisplay' => $admissionScoreDisplay,
            'closingLabel' => ($closingMonth && $closingDay)
                ? $closingDay.' '.($monthNames[(int) $closingMonth] ?? '').' '.$applicationYear
                : 'Not listed',
        ]);
    })->name('courses.show');

    Route::get('/marks', function (Request $request) {
        $user = $request->user();

        if ($user->grade_id === null) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Choose your grade before adding marks.');
        }

        $selectedSubjects = DB::table('user_subject_preferences')
            ->join('subjects', 'subjects.id', '=', 'user_subject_preferences.subject_id')
            ->where('user_subject_preferences.user_id', $user->id)
            ->where('user_subject_preferences.grade_id', $user->grade_id)
            ->select('subjects.id', 'subjects.name', 'subjects.code', 'subjects.abbreviation', 'user_subject_preferences.sort_order')
            ->orderBy('user_subject_preferences.sort_order')
            ->get();

        if ($selectedSubjects->isEmpty()) {
            return redirect()
                ->route('subjects.index')
                ->with('status', 'Select your subjects before adding marks.');
        }

        $terms = DB::table('terms')
            ->where('curriculum_id', $user->curriculum_id)
            ->where('grade_id', $user->grade_id)
            ->orderBy('from_date')
            ->orderBy('name')
            ->get(['id', 'name']);

        $termId = $request->integer('term_id') ?: optional($terms->first())->id;

        $results = DB::table('user_subject_results')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->where('term_id', $termId)
            ->get()
            ->keyBy('subject_id');

        return view('marks.index', [
            'user' => $user,
            'subjects' => $selectedSubjects,
            'terms' => $terms,
            'termId' => $termId,
            'results' => $results,
        ]);
    })->name('marks.index');

    Route::put('/marks', function (Request $request) {
        $user = $request->user();

        if ($user->grade_id === null) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Choose your grade before adding marks.');
        }

        $data = $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'marks' => ['nullable', 'array'],
            'marks.*' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $termBelongsToGrade = DB::table('terms')
            ->where('id', $data['term_id'])
            ->where('curriculum_id', $user->curriculum_id)
            ->where('grade_id', $user->grade_id)
            ->exists();

        if (! $termBelongsToGrade) {
            return back()->withErrors(['term_id' => 'Choose a valid term.'])->withInput();
        }

        $selectedSubjectIds = DB::table('user_subject_preferences')
            ->where('user_id', $user->id)
            ->where('grade_id', $user->grade_id)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $aps = function (int $mark): int {
            return match (true) {
                $mark >= 80 => 7,
                $mark >= 70 => 6,
                $mark >= 60 => 5,
                $mark >= 50 => 4,
                $mark >= 40 => 3,
                $mark >= 30 => 2,
                default => 1,
            };
        };

        $changedMarks = [];
        $removedMarks = [];
        $submittedSubjectCount = 0;

        foreach (($data['marks'] ?? []) as $subjectId => $mark) {
            $subjectId = (int) $subjectId;

            if (! in_array($subjectId, $selectedSubjectIds, true)) {
                continue;
            }

            $submittedSubjectCount++;

            if ($mark === null || $mark === '') {
                $existingResult = UserSubjectResult::query()
                    ->where('user_id', $user->id)
                    ->where('grade_id', $user->grade_id)
                    ->where('term_id', $data['term_id'])
                    ->where('subject_id', $subjectId)
                    ->first();

                if ($existingResult !== null) {
                    $removedMarks[] = [
                        'subject_id' => $subjectId,
                        'previous_mark' => $existingResult->mark,
                        'previous_aps_score' => $existingResult->aps_score,
                    ];

                    $existingResult->delete();
                }

                continue;
            }

            $mark = (int) $mark;
            $apsScore = $aps($mark);

            $result = UserSubjectResult::firstOrNew([
                'user_id' => $user->id,
                'grade_id' => $user->grade_id,
                'term_id' => $data['term_id'],
                'subject_id' => $subjectId,
            ]);
            $previousMark = $result->exists ? $result->mark : null;
            $previousApsScore = $result->exists ? $result->aps_score : null;

            $result->fill([
                'mark' => $mark,
                'aps_score' => $apsScore,
            ]);
            $result->save();

            $changedMarks[] = [
                'subject_id' => $subjectId,
                'result_id' => $result->id,
                'previous_mark' => $previousMark,
                'new_mark' => $mark,
                'previous_aps_score' => $previousApsScore,
                'new_aps_score' => $apsScore,
            ];
        }

        AuditLog::create([
            'name' => 'Marks updated',
            'description' => $user->name.' saved term marks.',
            'user_id' => $user->id,
            'event' => 'marks.updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'url' => $request->fullUrl(),
            'metadata' => [
                'grade_id' => $user->grade_id,
                'term_id' => (int) $data['term_id'],
                'selected_subject_count' => count($selectedSubjectIds),
                'submitted_subject_count' => $submittedSubjectCount,
                'changed_marks' => $changedMarks,
                'removed_marks' => $removedMarks,
            ],
        ]);

        return redirect()
            ->route('marks.index', ['term_id' => $data['term_id']])
            ->with('status', 'Marks updated.');
    })->name('marks.update');
});

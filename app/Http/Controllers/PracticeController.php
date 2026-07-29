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

class PracticeController extends Controller
{
    public function setup(Request $request)
    {
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
            
    }

    public function store(Request $request)
    {
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
            
    }

    public function show(Request $request, int $session)
    {
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
            
    }

    public function begin(Request $request, int $session)
    {
        DB::table('exam_sessions')
            ->where('id', $session)
            ->where('user_id', $request->user()->id)
            ->whereNull('completed_at')
            ->update([
                'started_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()->route('practice.take', $session);
            
    }

    public function take(Request $request, int $session)
    {
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
            
    }

    public function update(Request $request, int $session)
    {
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
            
    }

    public function results(Request $request, int $session)
    {
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
            
    }
}

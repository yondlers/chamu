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

class ApsCalculatorController extends Controller
{
    public function index(Request $request)
    {
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
    }
}

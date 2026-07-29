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

class BursaryController extends Controller
{
    public function index(Request $request)
    {
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
    }

    public function show(Request $request, int $bursary)
    {
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
        $bursaryModel = (new Bursary)->setRawAttributes((array) $bursary, true);
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
        $applicationProfile = null;
        $savedApplicationDocuments = collect();

        if ($request->user() !== null && Schema::hasTable('bursary_applications')) {
            $latestApplication = DB::table('bursary_applications')
                ->where('bursary_id', $bursary->id)
                ->where('user_id', $request->user()->id)
                ->latest('created_at')
                ->first();
        }

        if ($request->user() !== null && Schema::hasTable('user_application_profiles') && Schema::hasTable('user_application_documents')) {
            $applicationProfile = DB::table('user_application_profiles')
                ->where('user_id', $request->user()->id)
                ->first();

            $savedApplicationDocuments = UserApplicationDocument::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get()
                ->filter(fn (UserApplicationDocument $document): bool => $document->existsOnDisk())
                ->groupBy('document_key');
        }

        return view('bursaries.show', [
            'bursary' => $bursary,
            'requirements' => $requirements,
            'documentRequirements' => $documentRequirements,
            'latestApplication' => $latestApplication,
            'applicationProfile' => $applicationProfile,
            'savedApplicationDocuments' => $savedApplicationDocuments,
            'isChamuHandled' => $applicationTablesReady && (
                ($isEmailSubmission && $hasValidProviderEmail)
                || $isPostalSubmission
            ),
            'isPostalSubmission' => $usesPostalChamuFlow,
            'applicationTablesReady' => $applicationTablesReady,
            'providerEmail' => $providerEmail,
            'providerPostalAddress' => $providerPostalAddress,
        ]);
    }
}

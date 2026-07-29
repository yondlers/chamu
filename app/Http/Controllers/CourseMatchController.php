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

class CourseMatchController extends Controller
{
    public function index(Request $request)
    {
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
                'universities.slug as university_slug',
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
            
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Bursary;
use App\Models\Qualification;
use App\Models\University;
use App\Models\UniversityAdmissionRule;
use App\Services\Algorithm\MostAppliedQualificationAlgorithm;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ApsController extends Controller
{
    public function index(Request $request, MostAppliedQualificationAlgorithm $mostAppliedQualificationAlgorithm)
    {
        if ($request->user() !== null) {
            return redirect()->route('course-match.index', $request->query());
        }

        $search = trim((string) $request->query('search', ''));
        $requestedUniversityIds = $request->query('university_ids', []);

        if (! is_array($requestedUniversityIds)) {
            $requestedUniversityIds = [$requestedUniversityIds];
        }

        $legacyUniversityId = $request->integer('university_id') ?: null;

        if ($legacyUniversityId !== null) {
            $requestedUniversityIds[] = $legacyUniversityId;
        }

        $universities = University::query()
            ->select('id', 'name', 'slug', 'abbreviation', 'logo')
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
        $qualificationCount = Qualification::query()->count();
        $bursaryCount = Schema::hasTable('bursaries') ? Bursary::query()->count() : 0;
        $isInitialApsLoad = count($request->query()) === 0;

        $qualificationQuery = function () use ($selectedUniversityIds, $search) {
            return Qualification::query()
                ->with([
                    'faculty:id,name',
                    'qualificationType:id,name,abbreviation',
                    'university:id,name,slug,abbreviation,logo',
                ])
                ->when($selectedUniversityIds->isNotEmpty(), fn ($query) => $query->whereIn('university_id', $selectedUniversityIds->all()))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhereHas('faculty', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                            ->orWhereHas('qualificationType', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                            ->orWhereHas('university', function ($query) use ($search) {
                                $query
                                    ->where('name', 'like', '%'.$search.'%')
                                    ->orWhere('abbreviation', 'like', '%'.$search.'%');
                            });
                    });
                });
        };

        $allCourses = $qualificationQuery()->get();
        $courses = $isInitialApsLoad
            ? $this->paginateCourseCollection(
                $mostAppliedQualificationAlgorithm->rankForFirstScreen($allCourses),
                $request,
                total: $allCourses->count()
            )
            : $this->paginateCourseCollection($this->sortCoursesForStandardListing($allCourses), $request);

        $courseItems = $courses->getCollection();
        $admissionRuleAssignments = UniversityAdmissionRule::query()
            ->with('admissionRule')
            ->whereIn('university_id', $courseItems->pluck('university_id')->unique()->values()->all())
            ->whereHas('admissionRule', fn ($query) => $query->where('is_active', true))
            ->get();

        $admissionRuleForCourse = function (Qualification $course) use ($admissionRuleAssignments): ?UniversityAdmissionRule {
            return $admissionRuleAssignments
                ->filter(function (UniversityAdmissionRule $assignment) use ($course) {
                    if ((int) $assignment->university_id !== (int) $course->university_id) {
                        return false;
                    }

                    if ($assignment->qualification_id !== null && (int) $assignment->qualification_id !== (int) $course->id) {
                        return false;
                    }

                    if ($assignment->faculty_id !== null && (int) $assignment->faculty_id !== (int) $course->faculty_id) {
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

        $passTypeLabels = [
            'senior_certificate' => 'Senior Certificate pass',
            'nsc' => 'NSC pass',
            'higher_certificate' => 'Higher Certificate pass',
            'diploma' => 'Diploma pass',
            'bachelor' => 'Bachelor pass',
        ];
        $formatScore = fn (float $score, ?string $suffix): string => $suffix === '%'
            ? rtrim(rtrim(number_format($score, 1), '0'), '.').$suffix
            : rtrim(rtrim(number_format($score, 1), '0'), '.');

        $courses->through(function (Qualification $course) use ($admissionRuleForCourse, $formatScore, $passTypeLabels) {
            $admissionRule = $admissionRuleForCourse($course);
            $rule = $admissionRule?->admissionRule;
            $scoreType = $rule?->score_type
                ?? ($course->aggregate_average_required !== null ? 'aggregate_average' : 'aps');
            $usesAggregateAverage = $scoreType === 'aggregate_average';
            $usesPassType = $scoreType === 'pass_type';
            $scoreSuffix = $rule?->score_suffix ?? ($usesAggregateAverage ? '%' : null);
            $requiredPassType = $course->minimum_pass_type ?? $rule?->minimum_pass_type ?? null;
            $requiredScore = match (true) {
                $usesPassType => null,
                $course->admission_score_required !== null => (float) $course->admission_score_required,
                $course->aggregate_average_required !== null => (float) $course->aggregate_average_required,
                $course->aps_required !== null => (float) $course->aps_required,
                default => null,
            };

            $course->setAttribute('qualification_slug', $course->slug);
            $course->setAttribute('university_name', $course->university?->name);
            $course->setAttribute('university_slug', $course->university?->slug);
            $course->setAttribute('university_abbreviation', $course->university?->abbreviation);
            $course->setAttribute('university_logo', $course->university?->logo);
            $course->setAttribute('faculty_name', $course->faculty?->name);
            $course->setAttribute('qualification_type_name', $course->qualificationType?->name);
            $course->admission_score_label = $rule?->score_label
                ?? match (true) {
                    $usesAggregateAverage => 'Aggregated average',
                    $course->admission_score_required !== null => 'Score',
                    default => 'APS',
                };
            $course->admission_score_display = match (true) {
                $usesPassType && $requiredPassType !== null => $passTypeLabels[$requiredPassType] ?? $requiredPassType,
                $usesPassType => 'N/A',
                $requiredScore !== null => $formatScore($requiredScore, $scoreSuffix),
                default => 'N/A',
            };
            $course->admission_score_badge = $course->admission_score_display === 'N/A'
                ? 'Score N/A'
                : $course->admission_score_label.' '.$course->admission_score_display;

            return $course;
        });

        return view('aps.index', [
            'search' => $search,
            'universities' => $universities,
            'qualificationCount' => $qualificationCount,
            'bursaryCount' => $bursaryCount,
            'courses' => $courses,
            'filters' => [
                'university_id' => $selectedUniversityIds->first(),
                'university_ids' => $selectedUniversityIds->all(),
            ],
        ]);
    }

    /**
     * @param  Collection<int, Qualification>  $courses
     * @return Collection<int, Qualification>
     */
    private function sortCoursesForStandardListing(Collection $courses): Collection
    {
        return $courses
            ->sort(function (Qualification $first, Qualification $second) {
                return $this->standardCourseSortValue($first) <=> $this->standardCourseSortValue($second);
            })
            ->values();
    }

    /**
     * @return array{0: int, 1: float, 2: string, 3: string}
     */
    private function standardCourseSortValue(Qualification $course): array
    {
        $score = $this->courseAdmissionScore($course);

        return [
            $score === null ? 1 : 0,
            $score ?? PHP_INT_MAX,
            strtolower((string) $course->university?->name),
            strtolower((string) $course->name),
        ];
    }

    private function courseAdmissionScore(Qualification $course): ?float
    {
        return match (true) {
            $course->admission_score_required !== null => (float) $course->admission_score_required,
            $course->aggregate_average_required !== null => (float) $course->aggregate_average_required,
            $course->aps_required !== null => (float) $course->aps_required,
            default => null,
        };
    }

    /**
     * @param  Collection<int, Qualification>  $courses
     */
    private function paginateCourseCollection(Collection $courses, Request $request, int $perPage = 25, ?int $total = null): LengthAwarePaginator
    {
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $items = $courses
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return new LengthAwarePaginator($items, $total ?? $courses->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->except(['aps_score', 'page']),
        ]);
    }
}

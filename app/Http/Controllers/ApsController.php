<?php

namespace App\Http\Controllers;

use App\Models\Bursary;
use App\Models\Faculty;
use App\Models\Qualification;
use App\Models\QualificationType;
use App\Models\University;
use App\Models\UniversityAdmissionRule;
use App\Services\Algorithm\MostAppliedQualificationAlgorithm;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApsController extends Controller
{
    public function index(Request $request, MostAppliedQualificationAlgorithm $mostAppliedQualificationAlgorithm)
    {
        if ($request->user() !== null) {
            return redirect()->route('course-match.index', $request->query());
        }

        $filterTypeUniversity = 0;
        $filterTypeFaculty = 1;
        $filterTypeQualification = 2;
        $search = trim((string) $request->query('search', ''));
        $sort = strtolower(trim((string) $request->query('sort', 'default')));
        if (! in_array($sort, ['default', 'closing', 'score', 'level', 'duration'], true)) {
            $sort = 'default';
        }

        $rawFilters = $request->query('filter', []);
        if (! is_array($rawFilters)) {
            $rawFilters = filled($rawFilters) ? [$rawFilters] : [];
        }

        $legacyUniversityIds = $request->query('university_ids', []);
        if (! is_array($legacyUniversityIds)) {
            $legacyUniversityIds = [$legacyUniversityIds];
        }
        $legacyUniversityId = $request->integer('university_id') ?: null;
        if ($legacyUniversityId !== null) {
            $legacyUniversityIds[] = $legacyUniversityId;
        }
        foreach ($legacyUniversityIds as $legacyUniversity) {
            if (is_numeric($legacyUniversity) && (int) $legacyUniversity > 0) {
                $rawFilters[] = $filterTypeUniversity.':'.(int) $legacyUniversity;
            }
        }

        $selectedUniversityIds = [];
        $selectedFacultyIds = [];
        $selectedQualificationTypeIds = [];

        foreach ($rawFilters as $rawFilter) {
            if (! is_string($rawFilter) || ! str_contains($rawFilter, ':')) {
                continue;
            }

            [$typeIndex, $value] = explode(':', $rawFilter, 2);
            $typeIndex = (int) $typeIndex;
            $value = trim($value);

            if ($value === '' || ! ctype_digit($value)) {
                continue;
            }

            $id = (int) $value;

            if ($typeIndex === $filterTypeUniversity) {
                $selectedUniversityIds[] = $id;
                continue;
            }

            if ($typeIndex === $filterTypeFaculty) {
                $selectedFacultyIds[] = $id;
                continue;
            }

            if ($typeIndex === $filterTypeQualification) {
                $selectedQualificationTypeIds[] = $id;
            }
        }

        $universities = University::query()
            ->select('id', 'name', 'slug', 'abbreviation', 'logo')
            ->orderBy('name')
            ->get();
        $validUniversityIds = $universities
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $faculties = Faculty::query()
            ->with('university:id,name,abbreviation')
            ->orderBy('name')
            ->get(['id', 'name', 'university_id']);

        $qualificationTypes = QualificationType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $qualificationTypeFacultyIds = DB::table('qualifications')
            ->whereNotNull('qualification_type_id')
            ->whereNotNull('faculty_id')
            ->select('qualification_type_id', 'faculty_id')
            ->distinct()
            ->get()
            ->groupBy('qualification_type_id')
            ->map(fn (Collection $rows) => $rows
                ->pluck('faculty_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all());

        $selectedUniversityIds = collect($selectedUniversityIds)
            ->unique()
            ->filter(fn (int $id) => in_array($id, $validUniversityIds, true))
            ->values();

        $facultiesById = $faculties->keyBy('id');
        $selectedFacultyIds = collect($selectedFacultyIds)
            ->unique()
            ->filter(function (int $id) use ($facultiesById, $selectedUniversityIds) {
                $faculty = $facultiesById->get($id);

                if ($faculty === null || $selectedUniversityIds->isEmpty()) {
                    return false;
                }

                return $selectedUniversityIds->contains((int) $faculty->university_id);
            })
            ->values();

        $selectedQualificationTypeIds = collect($selectedQualificationTypeIds)
            ->unique()
            ->filter(function (int $id) use ($qualificationTypeFacultyIds, $selectedFacultyIds, $qualificationTypes) {
                if ($selectedFacultyIds->isEmpty() || ! $qualificationTypes->contains('id', $id)) {
                    return false;
                }

                $facultyIds = collect($qualificationTypeFacultyIds->get($id, []));

                return $facultyIds->intersect($selectedFacultyIds)->isNotEmpty();
            })
            ->values();

        $qualificationCount = Qualification::query()->count();
        $bursaryCount = Schema::hasTable('bursaries') ? Bursary::query()->count() : 0;
        $isInitialApsLoad = count($request->query()) === 0;

        $qualificationQuery = function () use ($selectedUniversityIds, $selectedFacultyIds, $selectedQualificationTypeIds, $search) {
            return Qualification::query()
                ->with([
                    'faculty:id,name',
                    'qualificationType:id,name,abbreviation',
                    'university:id,name,slug,abbreviation,logo',
                    'nqfLevel:id,level,name,sort_order',
                ])
                ->when($selectedUniversityIds->isNotEmpty(), fn ($query) => $query->whereIn('university_id', $selectedUniversityIds->all()))
                ->when($selectedFacultyIds->isNotEmpty(), fn ($query) => $query->whereIn('faculty_id', $selectedFacultyIds->all()))
                ->when($selectedQualificationTypeIds->isNotEmpty(), fn ($query) => $query->whereIn('qualification_type_id', $selectedQualificationTypeIds->all()))
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
        $sortedCourses = match (true) {
            $isInitialApsLoad => $mostAppliedQualificationAlgorithm->rankForFirstScreen($allCourses),
            $sort === 'closing' => $this->sortCoursesByClosingDate($allCourses),
            $sort === 'score' => $this->sortCoursesByRequiredScore($allCourses),
            $sort === 'level' => $this->sortCoursesByQualificationLevel($allCourses),
            $sort === 'duration' => $this->sortCoursesByDuration($allCourses),
            default => $this->sortCoursesForStandardListing($allCourses),
        };
        $courses = $this->paginateCourseCollection(
            $sortedCourses,
            $request,
            total: $isInitialApsLoad ? $allCourses->count() : null,
        );
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

        $universitiesById = $universities->keyBy('id');
        $qualificationTypesById = $qualificationTypes->keyBy('id');

        $selectedFilters = $selectedUniversityIds
            ->map(function (int $universityId) use ($universitiesById, $filterTypeUniversity) {
                $university = $universitiesById->get($universityId);

                if ($university === null) {
                    return null;
                }

                $label = $university->abbreviation && $university->abbreviation !== $university->name
                    ? $university->abbreviation
                    : $university->name;

                return [
                    'index' => $filterTypeUniversity,
                    'type' => 'university',
                    'value' => (string) $universityId,
                    'label' => $label,
                    'token' => $filterTypeUniversity.':'.$universityId,
                    'university_id' => $universityId,
                ];
            })
            ->filter()
            ->concat(
                $selectedFacultyIds->map(function (int $facultyId) use ($facultiesById, $filterTypeFaculty) {
                    $faculty = $facultiesById->get($facultyId);

                    if ($faculty === null) {
                        return null;
                    }

                    $universityAbbreviation = $faculty->university?->abbreviation;
                    $label = $universityAbbreviation
                        ? $universityAbbreviation.' · '.$faculty->name
                        : $faculty->name;

                    return [
                        'index' => $filterTypeFaculty,
                        'type' => 'faculty',
                        'value' => (string) $facultyId,
                        'label' => $label,
                        'token' => $filterTypeFaculty.':'.$facultyId,
                        'university_id' => (int) $faculty->university_id,
                    ];
                })->filter()->values()
            )
            ->concat(
                $selectedQualificationTypeIds->map(function (int $typeId) use ($qualificationTypesById, $filterTypeQualification, $qualificationTypeFacultyIds) {
                    $type = $qualificationTypesById->get($typeId);

                    if ($type === null) {
                        return null;
                    }

                    return [
                        'index' => $filterTypeQualification,
                        'type' => 'qualification',
                        'value' => (string) $typeId,
                        'label' => $type->name,
                        'token' => $filterTypeQualification.':'.$typeId,
                        'faculty_ids' => $qualificationTypeFacultyIds->get($typeId, []),
                    ];
                })->filter()->values()
            )
            ->values();

        return view('aps.index', [
            'search' => $search,
            'sort' => $sort,
            'universities' => $universities,
            'faculties' => $faculties,
            'qualificationTypes' => $qualificationTypes,
            'qualificationTypeFacultyIds' => $qualificationTypeFacultyIds,
            'qualificationCount' => $qualificationCount,
            'bursaryCount' => $bursaryCount,
            'courses' => $courses,
            'selectedFilters' => $selectedFilters,
            'selectedUniversityIds' => $selectedUniversityIds->all(),
            'selectedFacultyIds' => $selectedFacultyIds->all(),
            'selectedQualificationTypeIds' => $selectedQualificationTypeIds->all(),
            'filterTypeUniversity' => $filterTypeUniversity,
            'filterTypeFaculty' => $filterTypeFaculty,
            'filterTypeQualification' => $filterTypeQualification,
            'filters' => [
                'university_id' => $selectedUniversityIds->first(),
                'university_ids' => $selectedUniversityIds->all(),
                'faculty_ids' => $selectedFacultyIds->all(),
                'qualification_type_ids' => $selectedQualificationTypeIds->all(),
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
     * @param  Collection<int, Qualification>  $courses
     * @return Collection<int, Qualification>
     */
    private function sortCoursesByClosingDate(Collection $courses): Collection
    {
        return $courses
            ->sort(function (Qualification $first, Qualification $second) {
                return $this->courseClosingSortValue($first) <=> $this->courseClosingSortValue($second);
            })
            ->values();
    }

    /**
     * @param  Collection<int, Qualification>  $courses
     * @return Collection<int, Qualification>
     */
    private function sortCoursesByRequiredScore(Collection $courses): Collection
    {
        return $courses
            ->sort(function (Qualification $first, Qualification $second) {
                $firstScore = $this->courseAdmissionScore($first);
                $secondScore = $this->courseAdmissionScore($second);

                return [
                    $firstScore === null ? 1 : 0,
                    $firstScore ?? PHP_INT_MAX,
                    strtolower((string) $first->name),
                ] <=> [
                    $secondScore === null ? 1 : 0,
                    $secondScore ?? PHP_INT_MAX,
                    strtolower((string) $second->name),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, Qualification>  $courses
     * @return Collection<int, Qualification>
     */
    private function sortCoursesByQualificationLevel(Collection $courses): Collection
    {
        return $courses
            ->sort(function (Qualification $first, Qualification $second) {
                $firstLevel = $first->nqfLevel?->level;
                $secondLevel = $second->nqfLevel?->level;

                return [
                    $firstLevel === null ? 1 : 0,
                    $firstLevel ?? PHP_INT_MAX,
                    $first->nqfLevel?->sort_order ?? PHP_INT_MAX,
                    strtolower((string) $first->name),
                ] <=> [
                    $secondLevel === null ? 1 : 0,
                    $secondLevel ?? PHP_INT_MAX,
                    $second->nqfLevel?->sort_order ?? PHP_INT_MAX,
                    strtolower((string) $second->name),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, Qualification>  $courses
     * @return Collection<int, Qualification>
     */
    private function sortCoursesByDuration(Collection $courses): Collection
    {
        return $courses
            ->sort(function (Qualification $first, Qualification $second) {
                $firstDuration = $first->duration_years !== null ? (float) $first->duration_years : null;
                $secondDuration = $second->duration_years !== null ? (float) $second->duration_years : null;

                return [
                    $firstDuration === null ? 1 : 0,
                    $firstDuration ?? PHP_INT_MAX,
                    strtolower((string) $first->name),
                ] <=> [
                    $secondDuration === null ? 1 : 0,
                    $secondDuration ?? PHP_INT_MAX,
                    strtolower((string) $second->name),
                ];
            })
            ->values();
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: string}
     */
    private function courseClosingSortValue(Qualification $course): array
    {
        $month = $course->closing_month !== null ? (int) $course->closing_month : null;
        $day = $course->closing_day !== null ? (int) $course->closing_day : null;

        return [
            ($month === null || $day === null) ? 1 : 0,
            $month ?? PHP_INT_MAX,
            $day ?? PHP_INT_MAX,
            strtolower((string) $course->name),
        ];
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

<?php

namespace App\Services\Matching;

use App\Models\Qualification;
use App\Models\UniversityAdmissionRule;
use App\Models\User;
use App\Services\Admissions\PublicAdmissionInfoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseMatcher
{
    public function __construct(
        private readonly PublicAdmissionInfoService $admissionInfo,
    ) {}

    /**
     * Match the user's most academically recent marks against all qualifications.
     *
     * @return array{
     *     has_marks: bool,
     *     term: object|null,
     *     term_options: Collection<int, object>,
     *     results: Collection<int, object>,
     *     progress: Collection<int, object>,
     *     aps_total: int,
     *     average_mark: float|null,
     *     qualified_count: int,
     *     matches: Collection<int, array<string, mixed>>,
     *     preview: Collection<int, array<string, mixed>>
     * }
     */
    public function forUser(User $user, ?int $termId = null, ?int $previewLimit = null): array
    {
        $termOptions = $this->admissionInfo->userMarkTermOptions($user);
        $selectedTerm = $termId === null
            ? $termOptions->first()
            : ($termOptions->firstWhere('id', $termId) ?? $termOptions->first());

        $results = $selectedTerm === null
            ? collect()
            : $this->admissionInfo->userResultsForTerm($user, (int) $selectedTerm->id);

        $markSummary = $this->markSummary($results);
        $progress = $this->progressForUser($user);
        $matches = collect();

        if ($results->isNotEmpty() && $selectedTerm !== null) {
            $qualifications = Qualification::query()
                ->with([
                    'faculty',
                    'qualificationType',
                    'university',
                    'qualificationSubjectRequirements' => fn ($query) => $query->orderBy('id'),
                    'admissionScoreVariants' => fn ($query) => $query->orderBy('admission_score_required')->orderBy('id'),
                ])
                ->orderBy('name')
                ->get();
            $admissionRules = UniversityAdmissionRule::query()
                ->with('admissionRule')
                ->whereIn('university_id', $qualifications->pluck('university_id')->unique()->values()->all())
                ->whereHas('admissionRule', fn ($query) => $query->where('is_active', true))
                ->get();
            $rulesForQualification = function (Qualification $qualification) use ($admissionRules): Collection {
                return $admissionRules
                    ->filter(function (UniversityAdmissionRule $rule) use ($qualification): bool {
                        if ((int) $rule->university_id !== (int) $qualification->university_id) {
                            return false;
                        }

                        if ($rule->qualification_id !== null && (int) $rule->qualification_id !== (int) $qualification->id) {
                            return false;
                        }

                        if ($rule->faculty_id !== null && (int) $rule->faculty_id !== (int) $qualification->faculty_id) {
                            return false;
                        }

                        return true;
                    })
                    ->sortBy([
                        fn (UniversityAdmissionRule $rule) => (int) $rule->priority,
                        fn (UniversityAdmissionRule $rule) => $rule->qualification_id !== null ? -3 : ($rule->faculty_id !== null ? -2 : -1),
                    ])
                    ->values();
            };

            $matches = $qualifications
                ->map(function (Qualification $qualification) use ($results, $rulesForQualification, $selectedTerm): ?array {
                    $summary = $this->admissionInfo->qualificationMatchSummaryForResults($qualification, $results, (int) $selectedTerm->id, $rulesForQualification($qualification));

                    if ($summary === null || ! ($summary['is_match'] ?? false)) {
                        return null;
                    }

                    return $this->matchPayload($qualification, $summary);
                })
                ->filter()
                ->sortBy([
                    fn (array $match) => strtolower((string) $match['university_name']),
                    fn (array $match) => strtolower((string) $match['qualification_name']),
                ])
                ->values();
        }

        return [
            'has_marks' => $results->isNotEmpty(),
            'term' => $selectedTerm,
            'term_options' => $termOptions,
            'results' => $results,
            'progress' => $progress,
            'aps_total' => $markSummary['aps_total'],
            'average_mark' => $markSummary['average_mark'],
            'qualified_count' => $matches->count(),
            'matches' => $matches,
            'preview' => $previewLimit === null ? $matches->take(2)->values() : $matches->take($previewLimit)->values(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function progressForUser(User $user): Collection
    {
        return DB::table('user_subject_results')
            ->join('grades', 'grades.id', '=', 'user_subject_results.grade_id')
            ->join('terms', 'terms.id', '=', 'user_subject_results.term_id')
            ->join('subjects', 'subjects.id', '=', 'user_subject_results.subject_id')
            ->where('user_subject_results.user_id', $user->id)
            ->whereNotNull('user_subject_results.mark')
            ->whereIn('terms.name', ['Term 1', 'Term 2', 'Term 3', 'Term 4', 'NSC'])
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
                DB::raw('sum(coalesce(user_subject_results.aps_score, 0)) as aps_total'),
                DB::raw('avg(user_subject_results.mark) as average_mark'),
                DB::raw('count(user_subject_results.id) as reported_subjects'),
            )
            ->groupBy(
                'grades.id',
                'grades.name',
                'grades.sort_order',
                'terms.id',
                'terms.name',
            )
            ->orderBy('grades.sort_order')
            ->orderByRaw($this->termOrderSql())
            ->get()
            ->map(function (object $progress): object {
                $termNumber = (int) filter_var($progress->term_name, FILTER_SANITIZE_NUMBER_INT);
                $termLabel = $progress->term_name === 'NSC' ? 'NSC' : 'T'.$termNumber;
                $progress->label = $progress->grade_name.' '.$termLabel;
                $progress->aps_total = (int) $progress->aps_total;
                $progress->average_mark = $progress->average_mark === null ? null : (float) $progress->average_mark;
                $progress->reported_subjects = (int) $progress->reported_subjects;

                return $progress;
            });
    }

    /**
     * @param  Collection<int, object>  $results
     * @return array{aps_total: int, average_mark: float|null}
     */
    private function markSummary(Collection $results): array
    {
        $counted = $results->reject(function (object $result): bool {
            $code = strtoupper((string) ($result->code ?? $result->abbreviation ?? ''));

            return $code === 'LO' || strcasecmp((string) $result->name, 'Life Orientation') === 0;
        });

        return [
            'aps_total' => (int) $counted->sum(fn (object $result): int => (int) ($result->aps_score ?? 0)),
            'average_mark' => $counted->avg('mark'),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function matchPayload(Qualification $qualification, array $summary): array
    {
        $university = $qualification->university;
        $requirements = collect($summary['met_requirements'] ?? [])
            ->filter()
            ->take(5)
            ->values();

        if ($requirements->isEmpty()) {
            $requirements = collect(['Listed score and subject checks met']);
        }

        return [
            'university_name' => $university?->name ?? 'University',
            'university_abbreviation' => $university?->abbreviation,
            'university_logo_path' => $this->localPublicAssetPath($university?->logo),
            'university_initials' => $this->initials($university?->abbreviation ?: $university?->name),
            'qualification_name' => $qualification->name,
            'qualification_id' => $qualification->id,
            'faculty_name' => $qualification->faculty?->name,
            'qualification_type_name' => $qualification->qualificationType?->name,
            'requirements' => $requirements->all(),
            'score_label' => $summary['admission_score_label'],
            'required_score' => $summary['admission_score_required_display'],
            'actual_score' => $summary['admission_score_actual_display'],
            'score_gap' => $summary['admission_score_gap_display'],
            'term_label' => $summary['term_label'],
            'url' => $university && $qualification->slug
                ? route('public.qualifications.show', ['university' => $university->slug, 'qualification' => $qualification->slug])
                : route('aps.index'),
        ];
    }

    private function localPublicAssetPath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || Str::startsWith($path, ['http://', 'https://'])) {
            return null;
        }

        $candidate = public_path(ltrim($path, '/'));

        if (is_file($candidate)) {
            return $candidate;
        }

        $candidate = public_path('images/universities/'.ltrim($path, '/'));

        return is_file($candidate) ? $candidate : null;
    }

    private function initials(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name)) ?: [];
        $letters = collect($words)
            ->filter()
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->take(3)
            ->implode('');

        return $letters !== '' ? $letters : 'U';
    }

    private function termOrderSql(): string
    {
        return "case terms.name when 'Term 1' then 1 when 'Term 2' then 2 when 'Term 3' then 3 when 'Term 4' then 4 when 'NSC' then 4 else 0 end";
    }
}

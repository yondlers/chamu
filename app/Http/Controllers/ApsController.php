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

class ApsController extends Controller
{
    public function index(Request $request)
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

        $qualificationQuery = function () use ($selectedUniversityIds, $search) {
            return DB::table('qualifications')
                ->join('universities', 'universities.id', '=', 'qualifications.university_id')
                ->join('faculties', 'faculties.id', '=', 'qualifications.faculty_id')
                ->join('qualification_types', 'qualification_types.id', '=', 'qualifications.qualification_type_id')
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
                    'qualifications.slug as qualification_slug',
                    'qualifications.aps_required',
                    'qualifications.aggregate_average_required',
                    'qualifications.admission_score_required',
                    'qualifications.minimum_pass_type',
                    'qualifications.duration_years',
                    'qualifications.is_selection_programme',
                    'universities.id as university_id',
                    'universities.name as university_name',
                    'universities.slug as university_slug',
                    'universities.abbreviation as university_abbreviation',
                    'universities.logo as university_logo',
                    'faculties.id as faculty_id',
                    'faculties.name as faculty_name',
                    'qualification_types.name as qualification_type_name',
                );
        };

        $courses = $qualificationQuery()
            ->orderByRaw('(qualifications.admission_score_required IS NULL AND qualifications.aggregate_average_required IS NULL AND qualifications.aps_required IS NULL)')
            ->orderByRaw('COALESCE(qualifications.admission_score_required, qualifications.aggregate_average_required, qualifications.aps_required)')
            ->orderBy('universities.name')
            ->orderBy('qualifications.name')
            ->paginate(25)
            ->appends($request->except(['aps_score', 'page']));

        $courseItems = $courses->getCollection();
        $admissionRuleAssignments = DB::table('university_admission_rules')
            ->join('admission_rules', 'admission_rules.id', '=', 'university_admission_rules.admission_rule_id')
            ->whereIn('university_admission_rules.university_id', $courseItems->pluck('university_id')->unique())
            ->where('admission_rules.is_active', true)
            ->select(
                'university_admission_rules.*',
                'admission_rules.score_type',
                'admission_rules.score_label',
                'admission_rules.score_suffix',
                'admission_rules.minimum_pass_type as rule_minimum_pass_type',
            )
            ->get();

        $admissionRuleForCourse = function (object $course) use ($admissionRuleAssignments): ?object {
            return $admissionRuleAssignments
                ->filter(function ($assignment) use ($course) {
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

        $courses->through(function ($course) use ($admissionRuleForCourse, $formatScore, $passTypeLabels) {
            $admissionRule = $admissionRuleForCourse($course);
            $scoreType = $admissionRule->score_type
                ?? ($course->aggregate_average_required !== null ? 'aggregate_average' : 'aps');
            $usesAggregateAverage = $scoreType === 'aggregate_average';
            $usesPassType = $scoreType === 'pass_type';
            $scoreSuffix = $admissionRule->score_suffix ?? ($usesAggregateAverage ? '%' : null);
            $requiredPassType = $course->minimum_pass_type ?? $admissionRule->rule_minimum_pass_type ?? null;
            $requiredScore = match (true) {
                $usesPassType => null,
                $course->admission_score_required !== null => (float) $course->admission_score_required,
                $course->aggregate_average_required !== null => (float) $course->aggregate_average_required,
                $course->aps_required !== null => (float) $course->aps_required,
                default => null,
            };

            $course->admission_score_label = $admissionRule->score_label
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
}

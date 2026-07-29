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

class UniversityProgrammeController extends Controller
{
    public function index(Request $request, int $university)
    {
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
            
    }
}

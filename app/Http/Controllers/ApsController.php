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

        $courses = $qualificationQuery()
            ->orderByRaw('qualifications.aps_required IS NULL')
            ->orderBy('qualifications.aps_required')
            ->orderBy('universities.name')
            ->orderBy('qualifications.name')
            ->paginate(25)
            ->appends($request->except(['aps_score', 'page']));

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

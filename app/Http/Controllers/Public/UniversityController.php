<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Qualification;
use App\Models\QualificationType;
use App\Models\University;
use App\Services\Admissions\PublicAdmissionInfoService;
use App\Support\SourceMeta;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UniversityController extends Controller
{
    public function show(Request $request, University $university, PublicAdmissionInfoService $admissionInfo): View
    {
        $university->load([
            'country',
            'faculties' => fn ($query) => $query
                ->withCount('qualifications')
                ->orderBy('name'),
        ]);

        $search = trim((string) $request->query('search', ''));
        $facultyId = $request->integer('faculty_id') ?: null;
        $qualificationTypeId = $request->integer('qualification_type_id') ?: null;
        $perPageOptions = [12, 24, 48, 96];
        $perPage = $request->integer('per_page', 24);
        $perPage = in_array($perPage, $perPageOptions, true) ? $perPage : 24;

        $qualificationCount = $university->qualifications()->count();

        $qualificationTypes = QualificationType::query()
            ->whereIn('id', Qualification::query()
                ->where('university_id', $university->id)
                ->select('qualification_type_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $faculties = Faculty::query()
            ->where('university_id', $university->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $qualifications = Qualification::query()
            ->with(['faculty', 'qualificationType', 'nqfLevel'])
            ->withCount('qualificationSubjectRequirements')
            ->where('university_id', $university->id)
            ->when($facultyId !== null, fn ($query) => $query->where('faculty_id', $facultyId))
            ->when($qualificationTypeId !== null, fn ($query) => $query->where('qualification_type_id', $qualificationTypeId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('abbreviation', 'like', '%'.$search.'%')
                        ->orWhere('notes', 'like', '%'.$search.'%')
                        ->orWhereHas('faculty', fn ($facultyQuery) => $facultyQuery->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('qualificationType', fn ($typeQuery) => $typeQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy(
                Faculty::query()
                    ->select('name')
                    ->whereColumn('faculties.id', 'qualifications.faculty_id')
                    ->limit(1)
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Qualification $qualification) use ($admissionInfo) {
                $rules = $admissionInfo->relevantAdmissionRules($qualification);
                $qualification->public_admission_score = $admissionInfo->admissionScoreSummary($qualification, $rules);

                return $qualification;
            });

        $confirmedSourceUrl = Qualification::query()
            ->where('university_id', $university->id)
            ->whereNotNull('source_url')
            ->where('source_url', '!=', '')
            ->select('source_url')
            ->groupBy('source_url')
            ->orderByRaw('COUNT(*) DESC')
            ->value('source_url');

        $canonical = route('public.universities.show', ['university' => $university->slug]);
        $title = $university->name.' Courses and Requirements | Chamu';
        $description = 'Explore qualifications, faculties and admission information for '.$university->name.'. Check which programmes may match your APS on Chamu.';

        return view('public.universities.show', [
            'university' => $university,
            'qualificationCount' => $qualificationCount,
            'qualifications' => $qualifications,
            'faculties' => $faculties,
            'qualificationTypes' => $qualificationTypes,
            'search' => $search,
            'filters' => [
                'faculty_id' => $facultyId,
                'qualification_type_id' => $qualificationTypeId,
            ],
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'closingLabel' => $admissionInfo->closingLabel($university->default_closing_month, $university->default_closing_day),
            'sourceInfo' => SourceMeta::make(
                $confirmedSourceUrl ?? $university->website,
                $university->updated_at,
                $university->website ?? $confirmedSourceUrl,
            ),
            'seo' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'jsonLd' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            [
                                '@type' => 'ListItem',
                                'position' => 1,
                                'name' => 'Chamu',
                                'item' => url('/'),
                            ],
                            [
                                '@type' => 'ListItem',
                                'position' => 2,
                                'name' => $university->name,
                                'item' => $canonical,
                            ],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebPage',
                        'name' => $title,
                        'description' => $description,
                        'url' => $canonical,
                    ],
                ],
            ],
        ]);
    }
}

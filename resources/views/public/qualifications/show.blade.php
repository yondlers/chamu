@extends('layouts.app')

@section('title', $seo['title'])

@push('head')
    <x-seo-meta
        :title="$seo['title']"
        :description="$seo['description']"
        :canonical="$seo['canonical']"
        :json-ld="$seo['jsonLd']"
    />
@endpush

@section('content')
    @php
        $sourceToneClasses = [
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            'sky' => 'border-sky-200 bg-sky-50 text-sky-900',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-950',
        ][$sourceInfo['tone'] ?? 'sky'] ?? 'border-neutral-200 bg-neutral-50 text-neutral-800';
        $applicationPlanningNoteLabels = [
            'Eligibility explanation',
            'Academic requirement',
            'Document checklist',
            'Application method',
            'Closing-date context',
            'Application safety',
        ];
        $programmeNoteSections = \App\Support\ProgrammeNotes::sections($qualification->notes);
        $programmeNotes = \App\Support\ProgrammeNotes::lines($qualification->notes, $applicationPlanningNoteLabels);
        $providerShortName = $university->abbreviation ?: $university->name;
        $usesAggregateAverageAdmission = ($rules->first()?->admissionRule?->score_type ?? null) === 'aggregate_average';
        $formatAdmissionValue = fn ($value, ?string $suffix = null) => $value === null
            ? null
            : rtrim(rtrim(number_format((float) $value, 1), '0'), '.').($suffix ?? '');
        $admissionCards = collect();

        if ($usesAggregateAverageAdmission) {
            if ($qualification->aggregate_average_required !== null) {
                $admissionCards->push([
                    'label' => 'Aggregated average',
                    'value' => $formatAdmissionValue($qualification->aggregate_average_required, '%'),
                    'hint' => 'NSC aggregate average excluding Life Orientation',
                ]);
            }
        } else {
            if ($qualification->aps_required !== null) {
                $admissionCards->push([
                    'label' => 'Published APS',
                    'value' => (string) (int) $qualification->aps_required,
                    'hint' => null,
                ]);
            }

            if (
                $qualification->admission_score_required !== null
                && (
                    $qualification->aps_required === null
                    || (float) $qualification->admission_score_required !== (float) $qualification->aps_required
                )
            ) {
                $admissionCards->push([
                    'label' => 'Admission score',
                    'value' => $formatAdmissionValue($qualification->admission_score_required),
                    'hint' => null,
                ]);
            }

            if ($qualification->aggregate_average_required !== null) {
                $admissionCards->push([
                    'label' => 'Aggregate average',
                    'value' => $formatAdmissionValue($qualification->aggregate_average_required, '%'),
                    'hint' => 'Excluding Life Orientation where the source states this',
                ]);
            }
        }

        if ($admissionCards->isEmpty()) {
            $admissionCards->push([
                'label' => 'Admission basis',
                'value' => 'Check source',
                'hint' => 'Use the source page for the current published requirement.',
            ]);
        }

        $entryGradeDisplay = $isTvetCollegeQualification
            ? ($qualification->requiredGrade?->name ?? 'Confirm in source')
            : ($usesAggregateAverageAdmission ? 'Grade 12 NSC' : 'Grade 11/12 NSC');
        $entryGradeContext = $isTvetCollegeQualification || $usesAggregateAverageAdmission
            ? null
            : 'Provisional acceptance can use Grade 11 final marks or Grade 12 mid-year marks; final acceptance depends on final Grade 12 NSC results.';
        $qualificationDetailCards = collect([
            [
                'label' => 'Type',
                'value' => $qualification->qualificationType?->name ?? 'Not listed',
                'hint' => null,
            ],
            [
                'label' => 'NQF',
                'value' => $qualification->nqfLevel?->level ? 'Level '.$qualification->nqfLevel->level : 'Confirm in source',
                'hint' => null,
            ],
            [
                'label' => 'Entry grade',
                'value' => $entryGradeDisplay,
                'hint' => $entryGradeContext,
            ],
            [
                'label' => 'Duration',
                'value' => $durationLabel ?? 'Confirm in source',
                'hint' => null,
            ],
        ]);

        if ($closingLabel) {
            $qualificationDetailCards->push([
                'label' => 'Application closing date',
                'value' => $closingLabel,
                'hint' => null,
            ]);
        }

        $collegeAdmissionCards = collect($collegeAdmissionSummary['cards'] ?? [])
            ->reject(fn (array $card) => in_array($card['label'] ?? '', ['Entry grade / NQF route', 'Programme type', 'NQF'], true))
            ->values();

        $eligibilityText = $isTvetCollegeQualification
            ? 'Use the entry details above as a first screen, then confirm campus space and selection steps.'
            : 'Use the requirements above as a first screen; provisional review may use Grade 11 or Grade 12 mid-year results.';
        $documentText = 'Prepare your ID or passport, latest results, payment proof if required, and programme-specific documents.';
        $applicationRouteText = 'Apply through '.$providerShortName.' official channels before the listed closing date; programmes can close when full.';
        $safetyText = 'Confirm offers, fees and funding on official channels only. Avoid guaranteed-admission promises.';
        $showGenericPlanningCards = $isTvetCollegeQualification
            || $usesPassTypeAdmission
            || $qualification->qualificationSubjectRequirements->isNotEmpty();
        $planningCards = $showGenericPlanningCards ? collect([
            [
                'title' => 'Eligibility and selection',
                'icon' => 'clipboard-check',
                'body' => $eligibilityText,
            ],
            [
                'title' => 'Document checklist',
                'icon' => 'files',
                'body' => $documentText,
            ],
            [
                'title' => 'Application route and dates',
                'icon' => 'send',
                'body' => $applicationRouteText,
            ],
            [
                'title' => 'Application safety',
                'icon' => 'shield-check',
                'body' => $safetyText,
            ],
        ])->filter(fn (array $card) => filled($card['body']))->values() : collect();
        $qualificationNotes = collect($isTvetCollegeQualification && $collegeAdmissionSummary ? ($collegeAdmissionSummary['notes'] ?? []) : [])
            ->merge($programmeNotes)
            ->map(fn ($note) => trim((string) $note))
            ->filter()
            ->unique()
            ->values();
        $universityLogoSrc = null;
        $universityLogo = trim((string) $university->logo);

        if ($universityLogo !== '') {
            $logoIsAbsolute = str_starts_with($universityLogo, 'http://')
                || str_starts_with($universityLogo, 'https://')
                || str_starts_with($universityLogo, '/');

            $universityLogoSrc = $logoIsAbsolute ? $universityLogo : asset($universityLogo);
        }

        $universityInitials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) ($university->abbreviation ?: $university->name)) ?: 'UNI', 0, 3));
        $possibleCareers = $qualification->careers
            ->filter(fn ($career) => $career->is_active)
            ->sortBy(fn ($career) => sprintf('%010d|%s', (int) ($career->pivot?->sort_order ?? 0), $career->name))
            ->values();
    @endphp

    <main class="bg-[#f8fafc] pb-16">
        <section class="border-b border-neutral-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
                <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-500">
                    @if ($originBreadcrumb)
                        <a href="{{ $originBreadcrumb['url'] }}" class="hover:text-neutral-950">{{ $originBreadcrumb['label'] }}</a>
                    @else
                        <a href="{{ url('/') }}" class="hover:text-neutral-950">Chamu</a>
                    @endif
                    <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                    <a href="{{ route('public.universities.show', ['university' => $university->slug]) }}" class="hover:text-neutral-950">{{ $university->name }}</a>
                    <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                    <span aria-current="page" class="text-neutral-950">{{ $qualification->name }}</span>
                </nav>

                <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full bg-[#01225E]/10 px-3 py-1 text-xs font-bold uppercase text-[#01225E]">
                            <i data-lucide="graduation-cap" style="width:14px;height:14px;"></i>
                            {{ $isTvetCollegeQualification ? 'College programme' : 'Qualification' }}
                        </p>
                        <h1 class="mt-4 max-w-4xl text-3xl font-bold leading-tight text-neutral-950 sm:text-5xl">{{ $qualification->name }}</h1>
                        <p class="mt-4 max-w-3xl text-base leading-7 text-neutral-600">
                            @if ($isTvetCollegeQualification)
                                Public college entry information for {{ $qualification->name }} at {{ $university->name }}. TVET programmes can use school grade, equivalent NQF/NC(V)/NATED routes, subject marks, campus availability and selection notes rather than a single university-style APS.
                            @elseif ($usesPassTypeAdmission)
                                Public admission information for {{ $qualification->name }} at {{ $university->name }}. This qualification is checked against the published pass type, English mark and any listed selection or portfolio notes rather than a single APS total.
                            @elseif ($usesAggregateAverageAdmission)
                                Public admission information for {{ $qualification->name }} at {{ $university->name }}. This programme uses the published NSC aggregate average, excluding Life Orientation.
                            @else
                                Public admission information for {{ $qualification->name }} at {{ $university->name }}. APS and admission scores are useful filters, but universities may also require specific subjects, marks, selection tests, portfolios or other criteria.
                            @endif
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ $qualificationAction['url'] }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 text-sm font-bold text-white hover:bg-[#001A48]" data-analytics-event="seo_qualification_action_clicked" data-action-kind="{{ $qualificationAction['kind'] }}" data-source-page-type="qualification" data-qualification-id="{{ $qualification->id }}">
                                {{ $qualificationAction['label'] }} <i data-lucide="{{ $qualificationAction['icon'] }}" style="width:17px;height:17px;"></i>
                            </a>
                            @if ($qualification->source_url)
                                <a href="{{ $qualification->source_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-5 py-3 text-sm font-bold text-neutral-950 hover:bg-neutral-50">
                                    Source information <i data-lucide="external-link" style="width:17px;height:17px;"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <aside class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm" aria-label="University and qualification summary">
                        <div class="flex flex-col items-center text-center">
                            <div class="flex h-32 w-full items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 p-5">
                                @if ($universityLogoSrc)
                                    <img src="{{ $universityLogoSrc }}" alt="{{ $university->name }} logo" class="max-h-24 max-w-full object-contain">
                                @else
                                    <span class="text-3xl font-black text-[#01225E]">{{ $universityInitials }}</span>
                                @endif
                            </div>
                            <p class="mt-4 text-xs font-bold uppercase text-neutral-500">University</p>
                            <h2 class="mt-1 text-lg font-bold leading-snug text-neutral-950">{{ $university->name }}</h2>
                        </div>

                    </aside>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
            <div class="grid gap-6">
                @if ($qualificationMatch)
                    @php
                        $statusBadgeClasses = [
                            'success' => 'bg-emerald-50 text-emerald-700',
                            'warning' => 'bg-amber-50 text-amber-700',
                            'review' => 'bg-sky-50 text-sky-700',
                            'danger' => 'bg-rose-50 text-rose-700',
                        ][$qualificationMatch['status_tone']] ?? 'bg-white text-[#01225E]';
                    @endphp
                    <section class="rounded-2xl border border-[#01225E]/20 bg-[#01225E] p-6 text-white shadow-sm" aria-labelledby="saved-mark-heading">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $statusBadgeClasses }}">
                            {{ $qualificationMatch['status_label'] }}
                        </span>
                        <h2 id="saved-mark-heading" class="mt-3 text-2xl font-bold">Your saved-mark check</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-white/80">
                            Compared with your saved subjects and marks{{ $qualificationMatch['term_label'] ? ' from '.$qualificationMatch['term_label'] : '' }}.
                        </p>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-xl border border-white/10 bg-white p-4 text-neutral-950">
                                <p class="text-xs font-bold uppercase text-neutral-500">Required score</p>
                                <p class="mt-1 text-2xl font-bold">{{ $qualificationMatch['admission_score_required_display'] }}</p>
                                @if ($qualificationMatch['admission_score_required_display'] !== 'N/A' && $qualificationMatch['admission_score_label'] !== 'Score')
                                    <p class="mt-1 text-xs font-semibold uppercase text-neutral-500">{{ $qualificationMatch['admission_score_label'] }}</p>
                                @endif
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white p-4 text-neutral-950">
                                <p class="text-xs font-bold uppercase text-neutral-500">Your {{ $qualificationMatch['admission_score_label'] }}</p>
                                <p class="mt-1 text-2xl font-bold">{{ $qualificationMatch['admission_score_actual_display'] }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white p-4 text-neutral-950">
                                <p class="text-xs font-bold uppercase text-neutral-500">{{ $qualificationMatch['admission_score_label'] }} gap</p>
                                <p class="mt-1 text-2xl font-bold">{{ $qualificationMatch['admission_score_gap_display'] }}</p>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white p-4 text-neutral-950">
                                <p class="text-xs font-bold uppercase text-neutral-500">Closes</p>
                                <p class="mt-1 text-sm font-bold">{{ $qualificationMatch['closing_label'] }}</p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 lg:grid-cols-2">
                            <div class="rounded-xl bg-white p-4 text-neutral-950">
                                <p class="text-sm font-bold">Still needed</p>
                                @if ($qualificationMatch['requires_manual_review'])
                                    <p class="mt-2 text-sm font-semibold text-sky-700">Check the published notes. This qualification has requirements that are not fully machine-checkable yet.</p>
                                @elseif ($qualificationMatch['admission_score_gap'] === 0.0 && count($qualificationMatch['missing_requirements']) === 0 && count($qualificationMatch['missing_score_components']) === 0)
                                    <p class="mt-2 text-sm font-semibold text-emerald-700">Nothing missing based on your current marks.</p>
                                @else
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @if ($qualificationMatch['admission_score_gap'] > 0)
                                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                                                {{ $qualificationMatch['admission_score_label'] }}
                                                {{ $qualificationMatch['admission_score_type'] === 'pass_type' ? $qualificationMatch['admission_score_gap_display'] : '+'.$qualificationMatch['admission_score_gap_display'] }}
                                            </span>
                                        @endif
                                        @foreach ($qualificationMatch['missing_requirements'] as $requirement)
                                            <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">{{ $requirement }}</span>
                                        @endforeach
                                        @foreach ($qualificationMatch['missing_score_components'] as $component)
                                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700">{{ $component }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if (count($qualificationMatch['met_requirements']) > 0)
                                <div class="rounded-xl bg-white p-4 text-neutral-950">
                                    <p class="text-sm font-bold">Met requirements</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($qualificationMatch['met_requirements'] as $requirement)
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $requirement }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

                @if ($isTvetCollegeQualification && $collegeAdmissionSummary)
                    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="admission-heading">
                        <h2 id="admission-heading" class="text-2xl font-bold text-neutral-950">Admission Requirements</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-neutral-600">{{ $collegeAdmissionSummary['intro'] }}</p>

                        <div class="mt-5 grid gap-3">
                            @foreach ($qualificationDetailCards as $card)
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <p class="text-xs font-bold uppercase text-neutral-500">{{ $card['label'] }}</p>
                                    <p class="mt-2 text-xl font-bold text-neutral-950">{{ $card['value'] }}</p>
                                    @if ($card['hint'])
                                        <p class="mt-2 text-xs font-semibold leading-5 text-neutral-500">{{ $card['hint'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                            @foreach ($collegeAdmissionCards as $card)
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <p class="text-xs font-bold uppercase text-neutral-500">{{ $card['label'] }}</p>
                                    <p class="mt-2 text-xl font-bold text-neutral-950">{{ $card['value'] }}</p>
                                    <p class="mt-2 text-xs font-semibold leading-5 text-neutral-500">{{ $card['hint'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @elseif ($usesPassTypeAdmission)
                    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="admission-heading">
                        <h2 id="admission-heading" class="text-2xl font-bold text-neutral-950">Admission Requirements</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-neutral-600">
                            This qualification is checked by pass type and published subject marks rather than APS points.
                        </p>
                        <div class="mt-5 grid gap-3">
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <p class="text-xs font-bold uppercase text-neutral-500">{{ $scoreSummary['label'] }}</p>
                                <p class="mt-2 text-xl font-bold text-neutral-950">{{ $scoreSummary['value'] }}</p>
                                @if ($scoreSummary['source'])
                                    <p class="mt-2 text-xs font-semibold leading-5 text-neutral-500">{{ $scoreSummary['source'] }}</p>
                                @endif
                            </div>
                            @foreach ($qualificationDetailCards as $card)
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <p class="text-xs font-bold uppercase text-neutral-500">{{ $card['label'] }}</p>
                                    <p class="mt-2 text-xl font-bold text-neutral-950">{{ $card['value'] }}</p>
                                    @if ($card['hint'])
                                        <p class="mt-2 text-xs font-semibold leading-5 text-neutral-500">{{ $card['hint'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                    </section>
                @else
                    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="admission-heading">
                        <h2 id="admission-heading" class="text-2xl font-bold text-neutral-950">Admission Requirements</h2>
                        <div class="mt-5 grid gap-3">
                            @foreach ($admissionCards as $card)
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <p class="text-xs font-bold uppercase text-neutral-500">{{ $card['label'] }}</p>
                                    <p class="mt-2 text-2xl font-bold">{{ $card['value'] }}</p>
                                    @if ($card['hint'])
                                        <p class="mt-2 text-xs font-semibold leading-5 text-neutral-500">{{ $card['hint'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                            @foreach ($qualificationDetailCards as $card)
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <p class="text-xs font-bold uppercase text-neutral-500">{{ $card['label'] }}</p>
                                    <p class="mt-2 text-xl font-bold text-neutral-950">{{ $card['value'] }}</p>
                                    @if ($card['hint'])
                                        <p class="mt-2 text-xs font-semibold leading-5 text-neutral-500">{{ $card['hint'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($qualification->minimum_pass_type)
                            <p class="mt-4 rounded-xl bg-neutral-50 px-4 py-3 text-sm font-semibold text-neutral-700">
                                Minimum pass type: {{ $admissionInfo->passTypeLabel($qualification->minimum_pass_type) }}
                            </p>
                        @endif

                    </section>
                @endif

                <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="requirements-heading">
                    <h2 id="requirements-heading" class="text-2xl font-bold text-neutral-950">Subject Requirements</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($requirements as $group)
                            <article class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <h3 class="text-sm font-bold text-neutral-950">
                                    {{ $admissionInfo->requirementGroupHeading($group) }}
                                </h3>
                                @php
                                    $choiceGroups = $admissionInfo->requirementChoiceGroups($group);
                                @endphp
                                @if ($choiceGroups !== [])
                                    <div class="mt-3 grid gap-2">
                                        @foreach ($choiceGroups as $choiceGroup)
                                            <div class="rounded-lg bg-white px-3 py-2">
                                                <p class="text-xs font-bold text-neutral-700">{{ $choiceGroup['label'] }}</p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach ($choiceGroup['requirements'] as $requirement)
                                                        <span class="rounded-full bg-neutral-50 px-3 py-1 text-xs font-bold text-neutral-700">
                                                            {{ $requirement->subject_name ?: $requirement->subject?->name ?: 'Subject' }}
                                                            {{ $usesPassTypeAdmission && $requirement->minimum_mark !== null ? (int) $requirement->minimum_mark.'%' : $admissionInfo->requirementLabel($requirement) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($group as $requirement)
                                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-neutral-700">
                                                {{ $requirement->subject_name ?: $requirement->subject?->name ?: 'Subject' }}
                                                {{ $usesPassTypeAdmission && $requirement->minimum_mark !== null ? (int) $requirement->minimum_mark.'%' : $admissionInfo->requirementLabel($requirement) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @foreach ($admissionInfo->requirementNotes($group) as $note)
                                    <p class="mt-2 text-sm text-neutral-600">{{ $note }}</p>
                                @endforeach
                            </article>
                        @empty
                            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm font-semibold leading-6 text-sky-950">
                                @if ($isTvetCollegeQualification)
                                    No subject-mark requirements are captured for this college programme yet. Use the entry grade/NQF route and programme notes above, then confirm campus-specific subject rules with the college source.
                                @else
                                    Chamu has not captured structured subject rules for this qualification yet. Before applying, confirm required subjects, minimum marks, selection tests, portfolios, interviews, and closing dates on the source page.
                                @endif
                                @if ($qualification->source_url)
                                    <a href="{{ $qualification->source_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-2 font-black text-[#01225E] underline">
                                        Check source requirements <i data-lucide="external-link" style="width:15px;height:15px;"></i>
                                    </a>
                                @endif
                            </div>
                        @endforelse
                    </div>
                </section>

                @if ($qualificationNotes->isNotEmpty())
                    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="qualification-notes-heading">
                        <h2 id="qualification-notes-heading" class="text-2xl font-bold text-neutral-950">Qualification Notes</h2>
                        <div class="mt-4 grid gap-3 text-sm leading-6 text-neutral-600">
                            @foreach ($qualificationNotes as $note)
                                <p>{{ $note }}</p>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($planningCards->isNotEmpty())
                    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="application-planning-heading">
                        <h2 id="application-planning-heading" class="text-2xl font-bold text-neutral-950">Application planning</h2>
                        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($planningCards as $card)
                                <article class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-[#01225E]">
                                            <i data-lucide="{{ $card['icon'] }}" style="width:18px;height:18px;"></i>
                                        </span>
                                        <div>
                                            <h3 class="text-sm font-bold text-neutral-950">{{ $card['title'] }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-neutral-600">{{ $card['body'] }}</p>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($qualification->admissionScoreVariants->isNotEmpty())
                    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="variants-heading">
                        <h2 id="variants-heading" class="text-2xl font-bold text-neutral-950">Alternative score variants</h2>
                        <div class="mt-5 grid gap-3">
                            @foreach ($qualification->admissionScoreVariants as $variant)
                                <article class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h3 class="font-bold text-neutral-950">{{ $variant->label ?: 'Admission score variant' }}</h3>
                                            @if ($variant->subject_name)
                                                <p class="mt-1 text-sm text-neutral-600">{{ $variant->subject_name }} {{ $variant->aps_level_required !== null ? 'level '.(int) $variant->aps_level_required : ($variant->minimum_mark !== null ? (int) $variant->minimum_mark.'%' : '') }}</p>
                                            @endif
                                        </div>
                                        <p class="text-xl font-bold text-neutral-950">{{ rtrim(rtrim(number_format((float) $variant->admission_score_required, 1), '0'), '.') }}</p>
                                    </div>
                                    @if ($variant->notes)
                                        <p class="mt-2 text-sm text-neutral-600">{{ $variant->notes }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($rules->isNotEmpty())
                    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="rules-heading">
                        <h2 id="rules-heading" class="text-2xl font-bold text-neutral-950">{{ $isTvetCollegeQualification ? 'College matching rule' : 'Relevant Admission Rules' }}</h2>
                        <div class="mt-5 grid gap-3">
                            @foreach ($rules as $rule)
                            <article class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="font-bold text-neutral-950">{{ $rule->admissionRule->name }}</h3>
                                        <p class="mt-1 text-sm text-neutral-600">
                                            @if ($isTvetCollegeQualification)
                                                Entry route, subject rules and college notes
                                            @else
                                                {{ $rule->admissionRule->score_label ?: str($rule->admissionRule->score_type)->replace('_', ' ')->title() }} · {{ str($rule->admissionRule->calculation_method)->replace('_', ' ')->title() }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-neutral-700">
                                        @if ($rule->qualification_id)
                                            Qualification rule
                                        @elseif ($rule->faculty_id)
                                            Faculty rule
                                        @else
                                            University rule
                                        @endif
                                    </span>
                                </div>
                                @if ($rule->notes)
                                    <p class="mt-2 text-sm text-neutral-600">{{ $rule->notes }}</p>
                                @elseif ($rule->admissionRule->description)
                                    <p class="mt-2 text-sm text-neutral-600">{{ $rule->admissionRule->description }}</p>
                                @endif
                            </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($possibleCareers->isNotEmpty())
                    <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="possible-careers-heading">
                        <h2 id="possible-careers-heading" class="text-2xl font-bold text-neutral-950">Possible Careers</h2>
                        <div class="mt-5 overflow-x-auto">
                            <table class="min-w-full table-fixed text-left">
                                <colgroup>
                                    <col class="w-1/3">
                                    <col class="w-1/3">
                                    <col class="w-1/3">
                                </colgroup>
                                <tbody>
                                    @foreach ($possibleCareers->chunk(3) as $careerRow)
                                        <tr>
                                            @foreach ($careerRow as $career)
                                                <th scope="col" class="pb-2 pr-6 align-top text-sm font-bold text-neutral-950 {{ $loop->parent->first ? '' : 'pt-5' }}">
                                                    @if (filled($career->source_url))
                                                        <a href="{{ $career->source_url }}" target="_blank" rel="noopener noreferrer" class="text-[#01225E] underline decoration-[#01225E]/30 underline-offset-2 hover:decoration-[#01225E]">
                                                            {{ $career->name }}
                                                        </a>
                                                    @else
                                                        {{ $career->name }}
                                                    @endif
                                                </th>
                                            @endforeach
                                            @for ($i = $careerRow->count(); $i < 3; $i++)
                                                <th class="pb-2 pr-6 {{ $loop->first ? '' : 'pt-5' }}" aria-hidden="true"></th>
                                            @endfor
                                        </tr>
                                        <tr>
                                            @foreach ($careerRow as $career)
                                                <td class="pr-6 align-top text-sm font-semibold leading-6 text-neutral-600">
                                                    {{ $career->salary_expectation ?: 'Salary expectation to be confirmed' }}
                                                </td>
                                            @endforeach
                                            @for ($i = $careerRow->count(); $i < 3; $i++)
                                                <td class="pr-6" aria-hidden="true"></td>
                                            @endfor
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>

        </div>

        @unless ($qualificationMatch)
            <section class="mx-auto max-w-7xl px-4 pb-6 sm:px-5 lg:px-8" aria-labelledby="review-requirements-heading">
                <div class="rounded-2xl border border-[#01225E]/20 bg-[#01225E] p-6 text-white shadow-sm">
                    <h2 id="review-requirements-heading" class="text-2xl font-bold">{{ $isTvetCollegeQualification ? 'Review college requirements first' : 'Review requirements first' }}</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-white/80">
                        Browse the published score, subjects, notes and related qualifications here. Add marks later when you want Chamu to turn this into a personal comparison.
                    </p>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <a href="{{ $qualificationAction['url'] }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#01225E] hover:bg-neutral-100" data-analytics-event="seo_match_action_clicked" data-action-kind="{{ $qualificationAction['kind'] }}" data-qualification-id="{{ $qualification->id }}">
                            {{ $qualificationAction['label'] }} <i data-lucide="{{ $qualificationAction['icon'] }}" style="width:16px;height:16px;"></i>
                        </a>
                        @auth
                            @if ($qualificationAction['kind'] === 'browse_qualifications')
                                <a href="{{ route('subjects.index', ['manage' => 1]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 px-4 py-3 text-sm font-bold text-white hover:bg-white/10">
                                    Add marks when ready <i data-lucide="line-chart" style="width:16px;height:16px;"></i>
                                </a>
                            @endif
                        @endauth
                        @guest
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 px-4 py-3 text-sm font-bold text-white hover:bg-white/10">
                                Create a Free Account <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
                            </a>
                        @endguest
                    </div>
                </div>
            </section>
        @endunless

        <section class="mx-auto max-w-7xl px-4 pb-6 sm:px-5 lg:px-8" aria-labelledby="related-heading">
            <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 id="related-heading" class="text-2xl font-bold text-neutral-950">Related qualifications</h2>
                <div class="mt-5 grid gap-3">
                    @forelse ($relatedQualifications as $related)
                        @php
                            $relatedUniversity = $related->university ?? $university;
                            $relatedUniversityName = $relatedUniversity->abbreviation ?: $relatedUniversity->name;
                            $relatedUniversitySlug = $relatedUniversity->slug ?: $university->slug;
                        @endphp
                        <a href="{{ route('public.qualifications.show', ['university' => $relatedUniversitySlug, 'qualification' => $related->slug]) }}" class="block rounded-xl border border-neutral-200 bg-neutral-50 p-4 hover:bg-white">
                            <span class="block font-bold text-neutral-950">{{ $related->name }}</span>
                            <span class="mt-1 block text-sm font-semibold text-neutral-500">{{ $relatedUniversityName }} / {{ $related->qualificationType?->name ?? 'Qualification' }}</span>
                            @if ($related->faculty)
                                <span class="mt-2 block text-xs font-bold uppercase text-[#01225E]">{{ $related->faculty->name }}</span>
                            @endif
                        </a>
                    @empty
                        <p class="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-4 text-sm text-neutral-600">No related qualifications are listed yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        @include('partials.university-contact', [
            'university' => $university,
            'qualification' => $qualification,
            'sectionClass' => 'mx-auto max-w-7xl px-4 pb-6 sm:px-5 lg:px-8',
        ])

        <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-5 lg:px-8" aria-labelledby="source-heading">
            <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 id="source-heading" class="text-2xl font-bold text-neutral-950">Source and review</h2>
                <div class="mt-4 rounded-xl border p-4 text-sm font-semibold leading-6 {{ $sourceToneClasses }}">
                    <p class="font-bold">{{ $sourceInfo['label'] }}</p>
                    <p class="mt-2">{{ $sourceInfo['summary'] }}</p>
                </div>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="font-semibold text-neutral-500">Last reviewed</dt>
                        <dd class="text-right font-bold text-neutral-950">
                            @if ($sourceInfo['last_reviewed_machine'])
                                <time datetime="{{ $sourceInfo['last_reviewed_machine'] }}">{{ $sourceInfo['last_reviewed'] }}</time>
                            @else
                                {{ $sourceInfo['last_reviewed'] }}
                            @endif
                        </dd>
                    </div>
                    @if ($sourceInfo['source_host'])
                        <div class="flex items-start justify-between gap-4">
                            <dt class="font-semibold text-neutral-500">Source host</dt>
                            <dd class="break-all text-right font-bold text-neutral-950">{{ $sourceInfo['source_host'] }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($sourceInfo['source_url'])
                    <a href="{{ $sourceInfo['source_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 px-4 py-3 text-sm font-bold hover:bg-neutral-50">
                        Open source <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                    </a>
                @endif
            </div>
        </section>
    </main>
@endsection

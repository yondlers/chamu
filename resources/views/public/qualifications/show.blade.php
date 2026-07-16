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
    <main class="bg-[#f8fafc] pb-16">
        <section class="border-b border-neutral-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8">
                <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-500">
                    <a href="{{ url('/') }}" class="hover:text-neutral-950">Chamu</a>
                    <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                    <a href="{{ route('public.universities.show', ['university' => $university->slug]) }}" class="hover:text-neutral-950">{{ $university->name }}</a>
                    <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                    <span aria-current="page" class="text-neutral-950">{{ $qualification->name }}</span>
                </nav>

                <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full bg-[#01225E]/10 px-3 py-1 text-xs font-bold uppercase text-[#01225E]">
                            <i data-lucide="graduation-cap" style="width:14px;height:14px;"></i>
                            Qualification
                        </p>
                        <h1 class="mt-4 max-w-4xl text-3xl font-bold leading-tight text-neutral-950 sm:text-5xl">{{ $qualification->name }}</h1>
                        <p class="mt-4 max-w-3xl text-base leading-7 text-neutral-600">
                            Public admission information for {{ $qualification->name }} at {{ $university->name }}. APS and admission scores are useful filters, but universities may also require specific subjects, marks, selection tests, portfolios or other criteria.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('course-match.index', ['university_id' => $university->id, 'faculty_id' => $qualification->faculty_id, 'search' => $qualification->name]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 text-sm font-bold text-white hover:bg-[#001A48]" data-analytics-event="seo_full_match_started" data-source-page-type="qualification" data-qualification-id="{{ $qualification->id }}">
                                Check My Full Eligibility <i data-lucide="target" style="width:17px;height:17px;"></i>
                            </a>
                            @if ($qualification->source_url)
                                <a href="{{ $qualification->source_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-5 py-3 text-sm font-bold text-neutral-950 hover:bg-neutral-50">
                                    Source information <i data-lucide="external-link" style="width:17px;height:17px;"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <aside class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm" aria-label="Qualification summary">
                        <dl class="grid gap-3">
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <dt class="text-xs font-bold uppercase text-neutral-500">{{ $scoreSummary['label'] }}</dt>
                                <dd class="mt-2 text-3xl font-bold text-neutral-950">{{ $scoreSummary['value'] }}</dd>
                                @if ($scoreSummary['source'])
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $scoreSummary['source'] }}</p>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <dt class="text-xs font-bold uppercase text-neutral-500">Type</dt>
                                    <dd class="mt-2 text-sm font-bold text-neutral-950">{{ $qualification->qualificationType?->name ?? 'Not listed' }}</dd>
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <dt class="text-xs font-bold uppercase text-neutral-500">NQF</dt>
                                    <dd class="mt-2 text-sm font-bold text-neutral-950">{{ $qualification->nqfLevel?->level ? 'Level '.$qualification->nqfLevel->level : 'Not listed' }}</dd>
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <dt class="text-xs font-bold uppercase text-neutral-500">Grade</dt>
                                    <dd class="mt-2 text-sm font-bold text-neutral-950">{{ $qualification->requiredGrade?->name ?? 'Not listed' }}</dd>
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <dt class="text-xs font-bold uppercase text-neutral-500">Duration</dt>
                                    <dd class="mt-2 text-sm font-bold text-neutral-950">{{ $qualification->duration_years ? $qualification->duration_years.' years' : 'Not listed' }}</dd>
                                </div>
                            </div>
                            @if ($closingLabel)
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                    <dt class="text-xs font-bold uppercase text-neutral-500">Application closing date</dt>
                                    <dd class="mt-2 text-sm font-bold text-neutral-950">{{ $closingLabel }}</dd>
                                </div>
                            @endif
                        </dl>
                    </aside>
                </div>
            </div>
        </section>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-5 lg:grid-cols-[minmax(0,1fr)_340px] lg:px-8">
            <div class="grid gap-6">
                <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="admission-heading">
                    <h2 id="admission-heading" class="text-2xl font-bold text-neutral-950">Admission information</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <p class="text-xs font-bold uppercase text-neutral-500">Published APS</p>
                            <p class="mt-2 text-2xl font-bold">{{ $qualification->aps_required !== null ? (int) $qualification->aps_required : 'Not listed' }}</p>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <p class="text-xs font-bold uppercase text-neutral-500">Admission score</p>
                            <p class="mt-2 text-2xl font-bold">{{ $qualification->admission_score_required !== null ? rtrim(rtrim(number_format((float) $qualification->admission_score_required, 1), '0'), '.') : 'Not listed' }}</p>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <p class="text-xs font-bold uppercase text-neutral-500">Aggregate average</p>
                            <p class="mt-2 text-2xl font-bold">{{ $qualification->aggregate_average_required !== null ? rtrim(rtrim(number_format((float) $qualification->aggregate_average_required, 1), '0'), '.').'%' : 'Not listed' }}</p>
                        </div>
                    </div>

                    @if ($qualification->minimum_pass_type)
                        <p class="mt-4 rounded-xl bg-neutral-50 px-4 py-3 text-sm font-semibold text-neutral-700">
                            Minimum pass type: {{ $admissionInfo->passTypeLabel($qualification->minimum_pass_type) }}
                        </p>
                    @endif

                    @if ($qualification->notes)
                        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $qualification->notes }}</p>
                    @endif
                </section>

                <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="variants-heading">
                    <h2 id="variants-heading" class="text-2xl font-bold text-neutral-950">Alternative score variants</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($qualification->admissionScoreVariants as $variant)
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
                        @empty
                            <p class="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-4 text-sm text-neutral-600">No alternative score variants are listed for this qualification.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="requirements-heading">
                    <h2 id="requirements-heading" class="text-2xl font-bold text-neutral-950">Subject requirements</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($requirements as $group)
                            <article class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <h3 class="text-sm font-bold text-neutral-950">
                                    {{ $admissionInfo->requirementGroupHeading($group) }}
                                </h3>
                                @php($choiceGroups = $admissionInfo->requirementChoiceGroups($group))
                                @if ($choiceGroups !== [])
                                    <div class="mt-3 grid gap-2">
                                        @foreach ($choiceGroups as $choiceGroup)
                                            <div class="rounded-lg bg-white px-3 py-2">
                                                <p class="text-xs font-bold text-neutral-700">{{ $choiceGroup['label'] }}</p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach ($choiceGroup['requirements'] as $requirement)
                                                        <span class="rounded-full bg-neutral-50 px-3 py-1 text-xs font-bold text-neutral-700">
                                                            {{ $requirement->subject_name ?: $requirement->subject?->name ?: 'Subject' }}
                                                            {{ $admissionInfo->requirementLabel($requirement) }}
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
                                                {{ $admissionInfo->requirementLabel($requirement) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @foreach ($admissionInfo->requirementNotes($group) as $note)
                                    <p class="mt-2 text-sm text-neutral-600">{{ $note }}</p>
                                @endforeach
                            </article>
                        @empty
                            <p class="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-4 text-sm text-neutral-600">No subject requirements are listed for this qualification yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm" aria-labelledby="rules-heading">
                    <h2 id="rules-heading" class="text-2xl font-bold text-neutral-950">Relevant admission rules</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($rules as $rule)
                            <article class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="font-bold text-neutral-950">{{ $rule->admissionRule->name }}</h3>
                                        <p class="mt-1 text-sm text-neutral-600">{{ $rule->admissionRule->score_label ?: str($rule->admissionRule->score_type)->replace('_', ' ')->title() }} · {{ str($rule->admissionRule->calculation_method)->replace('_', ' ')->title() }}</p>
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
                        @empty
                            <p class="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-4 text-sm text-neutral-600">No separate admission rule record is listed for this qualification.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="grid content-start gap-6">
                <section class="rounded-2xl border border-[#01225E]/20 bg-[#01225E] p-5 text-white shadow-sm" aria-labelledby="full-match-heading">
                    <h2 id="full-match-heading" class="text-xl font-bold">Check your full subject-level eligibility</h2>
                    <p class="mt-3 text-sm leading-6 text-white/80">
                        APS is only part of the admission decision. Create a free Chamu account to enter your subjects and marks, compare them with this qualification's requirements, and save your result.
                    </p>
                    <div class="mt-5 grid gap-2">
                        <a href="{{ route('course-match.index', ['university_id' => $university->id, 'faculty_id' => $qualification->faculty_id, 'search' => $qualification->name]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#01225E] hover:bg-neutral-100" data-analytics-event="seo_match_signup_clicked" data-qualification-id="{{ $qualification->id }}">
                            Check My Full Eligibility <i data-lucide="target" style="width:16px;height:16px;"></i>
                        </a>
                        @guest
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 px-4 py-3 text-sm font-bold text-white hover:bg-white/10">
                                Create a Free Account <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
                            </a>
                        @endguest
                    </div>
                </section>

                <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm" aria-labelledby="related-heading">
                    <h2 id="related-heading" class="text-xl font-bold text-neutral-950">Related qualifications</h2>
                    <div class="mt-4 grid gap-3">
                        @forelse ($relatedQualifications as $related)
                            <a href="{{ route('public.qualifications.show', ['university' => $university->slug, 'qualification' => $related->slug]) }}" class="block rounded-xl border border-neutral-200 bg-neutral-50 p-4 hover:bg-white">
                                <span class="block font-bold text-neutral-950">{{ $related->name }}</span>
                                <span class="mt-1 block text-sm font-semibold text-neutral-500">{{ $related->qualificationType?->name ?? 'Qualification' }}</span>
                            </a>
                        @empty
                            <p class="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-4 text-sm text-neutral-600">No related qualifications are listed yet.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </main>
@endsection

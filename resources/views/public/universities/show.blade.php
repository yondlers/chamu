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
                    <span aria-current="page" class="text-neutral-950">{{ $university->name }}</span>
                </nav>

                <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full bg-[#01225E]/10 px-3 py-1 text-xs font-bold uppercase text-[#01225E]">
                            <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                            University
                        </p>
                        <h1 class="mt-4 max-w-4xl text-3xl font-bold leading-tight text-neutral-950 sm:text-5xl">{{ $university->name }}</h1>
                        <p class="mt-4 max-w-3xl text-base leading-7 text-neutral-600">
                            Explore qualifications, faculties and published admission information captured for {{ $university->abbreviation ?: $university->name }}. APS can help narrow your options, but subject requirements and selection rules still matter.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('aps.index', ['university_id' => $university->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 text-sm font-bold text-white hover:bg-[#001A48]" data-analytics-event="seo_full_match_started" data-source-page-type="university">
                                Check APS options <i data-lucide="target" style="width:17px;height:17px;"></i>
                            </a>
                            @if ($university->website)
                                <a href="{{ $university->website }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-5 py-3 text-sm font-bold text-neutral-950 hover:bg-neutral-50">
                                    University website <i data-lucide="external-link" style="width:17px;height:17px;"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <aside class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm" aria-label="University summary">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <p class="text-xs font-bold uppercase text-neutral-500">Qualifications</p>
                                <p class="mt-2 text-3xl font-bold">{{ number_format($qualificationCount) }}</p>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                                <p class="text-xs font-bold uppercase text-neutral-500">Faculties</p>
                                <p class="mt-2 text-3xl font-bold">{{ number_format($university->faculties->count()) }}</p>
                            </div>
                        </div>

                        <dl class="mt-5 grid gap-3 text-sm">
                            @if ($university->abbreviation)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="font-semibold text-neutral-500">Abbreviation</dt>
                                    <dd class="text-right font-bold text-neutral-950">{{ $university->abbreviation }}</dd>
                                </div>
                            @endif
                            @if ($university->country)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="font-semibold text-neutral-500">Country</dt>
                                    <dd class="text-right font-bold text-neutral-950">{{ $university->country->name }}</dd>
                                </div>
                            @endif
                            @if ($closingLabel)
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="font-semibold text-neutral-500">Default closing date</dt>
                                    <dd class="text-right font-bold text-neutral-950">{{ $closingLabel }}</dd>
                                </div>
                            @endif
                        </dl>
                    </aside>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-8 sm:px-5 lg:px-8" aria-labelledby="faculties-heading">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="faculties-heading" class="text-2xl font-bold text-neutral-950">Faculties</h2>
                    <p class="mt-1 text-sm text-neutral-600">Faculties currently represented in Chamu for {{ $university->abbreviation ?: $university->name }}.</p>
                </div>
            </div>

            @if ($university->faculties->isNotEmpty())
                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($university->faculties as $faculty)
                        <article class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
                            <h3 class="font-bold text-neutral-950">{{ $faculty->name }}</h3>
                            <p class="mt-2 text-sm font-semibold text-neutral-500">{{ number_format($faculty->qualifications_count) }} qualifications</p>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="mt-5 rounded-2xl border border-dashed border-neutral-300 bg-white p-5 text-sm text-neutral-600">No faculties are listed yet for this university.</p>
            @endif
        </section>

        <section id="qualification-preview" class="mx-auto max-w-7xl px-4 sm:px-5 lg:px-8" aria-labelledby="qualifications-heading">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="qualifications-heading" class="text-2xl font-bold text-neutral-950">Qualification examples</h2>
                    <p class="mt-1 text-sm text-neutral-600">A public preview of captured programmes and published admission information.</p>
                </div>
            </div>

            <div class="mt-5 grid gap-4">
                @forelse ($qualificationPreview as $qualification)
                    <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($qualification->qualificationType)
                                        <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-700">{{ $qualification->qualificationType->name }}</span>
                                    @endif
                                    @if ($qualification->is_selection_programme)
                                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Selection programme</span>
                                    @endif
                                </div>
                                <h3 class="mt-3 text-xl font-bold text-neutral-950">{{ $qualification->name }}</h3>
                                @if ($qualification->faculty)
                                    <p class="mt-1 text-sm font-semibold text-neutral-500">{{ $qualification->faculty->name }}</p>
                                @endif
                                <p class="mt-3 text-sm text-neutral-600">Subject requirements still apply even when the published APS or admission score looks compatible.</p>
                            </div>

                            <div class="grid min-w-full grid-cols-2 gap-2 sm:min-w-[360px] sm:grid-cols-3">
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                    <p class="text-xs font-bold uppercase text-neutral-500">{{ $qualification->public_admission_score['label'] }}</p>
                                    <p class="mt-1 text-2xl font-bold">{{ $qualification->public_admission_score['value'] }}</p>
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                    <p class="text-xs font-bold uppercase text-neutral-500">NQF</p>
                                    <p class="mt-1 text-2xl font-bold">{{ $qualification->nqfLevel?->level ?? 'N/A' }}</p>
                                </div>
                                <div class="col-span-2 rounded-xl border border-neutral-200 bg-neutral-50 p-3 sm:col-span-1">
                                    <p class="text-xs font-bold uppercase text-neutral-500">Subjects</p>
                                    <p class="mt-1 text-2xl font-bold">{{ number_format($qualification->qualification_subject_requirements_count) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('public.qualifications.show', ['university' => $university->slug, 'qualification' => $qualification->slug]) }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-bold hover:bg-neutral-50" data-analytics-event="seo_qualification_opened" data-qualification-id="{{ $qualification->id }}">
                                View requirements <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                            </a>
                        </div>
                    </article>
                @empty
                    <section class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center">
                        <h3 class="text-xl font-bold">No public qualifications listed yet</h3>
                        <p class="mt-2 text-neutral-600">Chamu has this university record, but no qualification records are available for public browsing yet.</p>
                    </section>
                @endforelse
            </div>
        </section>
    </main>
@endsection

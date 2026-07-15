@extends('layouts.app')

@section('title', $bursary->title . ' · Matric Hub')

@section('content')
    <main class="mx-auto max-w-6xl px-5 py-8 lg:px-8">
        <a href="{{ route('bursaries.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-900">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
            Bursaries
        </a>

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[#E8425B]">{{ $bursary->company_name ?? 'Bursary provider' }}</p>
                    <h1 class="mt-2 text-3xl font-bold">{{ $bursary->title }}</h1>
                    <p class="mt-2 text-neutral-500">{{ $bursary->category ?? 'Bursary' }}</p>
                    @if ($bursary->summary)
                        <p class="mt-4 rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-600">{{ $bursary->summary }}</p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ $bursary->apply_url ?: $bursary->source_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-[#E8425B] px-4 py-2 text-sm font-semibold text-white hover:bg-[#d73550]">
                            Apply link <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                        </a>
                        <a href="{{ $bursary->source_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                            Source <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                        </a>
                    </div>
                </div>

                <div class="grid min-w-full grid-cols-2 gap-2 sm:min-w-[420px] sm:grid-cols-3">
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                        <p class="text-xs font-bold uppercase text-neutral-500">Closes</p>
                        <p class="mt-1 text-sm font-bold">{{ $bursary->closing_date_label ?? 'Not listed' }}</p>
                    </div>
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                        <p class="text-xs font-bold uppercase text-neutral-500">Academic reqs</p>
                        <p class="mt-1 text-2xl font-bold">{{ $requirements->count() }}</p>
                    </div>
                    <div class="col-span-2 rounded-xl border border-neutral-200 bg-neutral-50 p-3 sm:col-span-1">
                        <p class="text-xs font-bold uppercase text-neutral-500">Contact</p>
                        <p class="mt-1 text-sm font-bold">{{ $bursary->contact_email ?? 'See source' }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 lg:grid-cols-2">
            <article class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-xl font-bold">Funding Details</h2>
                <div class="mt-4 grid gap-4 text-sm text-neutral-600">
                    @foreach ([
                        'Fields covered' => $bursary->fields_covered,
                        'Coverage value' => $bursary->coverage_value,
                        'Service contract' => $bursary->service_contract,
                        'Renewal' => $bursary->renewal,
                        'How to apply' => $bursary->application_method,
                    ] as $label => $value)
                        @if ($value)
                            <div>
                                <p class="text-xs font-bold uppercase text-neutral-500">{{ $label }}</p>
                                <p class="mt-1">{{ $value }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-xl font-bold">Academic Requirements</h2>
                @if ($requirements->isEmpty())
                    <p class="mt-3 text-sm text-neutral-500">No structured academic requirements have been captured for this bursary yet.</p>
                @else
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($requirements as $requirement)
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                {{ $requirement->subject_name ?? 'Subject' }}
                                @if ($requirement->requirement_type === 'minimum_average' && $requirement->minimum_mark !== null)
                                    {{ (int) $requirement->minimum_mark }}% average
                                @elseif ($requirement->requirement_type === 'minimum_aps' && $requirement->aps_level_required !== null)
                                    APS {{ (int) $requirement->aps_level_required }}
                                @elseif ($requirement->minimum_mark !== null)
                                    {{ (int) $requirement->minimum_mark }}%
                                @elseif ($requirement->aps_level_required !== null)
                                    level {{ (int) $requirement->aps_level_required }}
                                @else
                                    required
                                @endif
                            </span>
                        @endforeach
                    </div>
                    @foreach ($requirements->pluck('notes')->filter() as $note)
                        <p class="mt-3 text-sm text-neutral-500">{{ $note }}</p>
                    @endforeach
                @endif
            </article>
        </section>

        <section class="mt-6 grid gap-4 lg:grid-cols-2">
            <article class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-xl font-bold">Eligibility</h2>
                @if (count($bursary->eligibility_requirements) > 0)
                    <ul class="mt-4 grid gap-2 text-sm text-neutral-600">
                        @foreach ($bursary->eligibility_requirements as $requirement)
                            <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[#E8425B]"></span>{{ $requirement }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 text-sm text-neutral-500">Eligibility requirements are not listed yet.</p>
                @endif
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-xl font-bold">Supporting Documents</h2>
                @if (count($bursary->supporting_documents) > 0)
                    <ul class="mt-4 grid gap-2 text-sm text-neutral-600">
                        @foreach ($bursary->supporting_documents as $document)
                            <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-neutral-400"></span>{{ $document }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 text-sm text-neutral-500">Supporting documents are not listed yet.</p>
                @endif
            </article>
        </section>
    </main>
@endsection

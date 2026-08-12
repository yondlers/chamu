@extends('layouts.app')

@section('title', $seo['title'] ?? ($bursary->title . ' · Chamu'))

@push('head')
    @isset($seo)
        <x-seo-meta
            :title="$seo['title']"
            :description="$seo['description']"
            :canonical="$seo['canonical']"
            :json-ld="$seo['jsonLd']"
        />
    @endisset
@endpush

@section('content')
    @php
        $applicationDeliveryType = $bursary->application_delivery_type ?? 'external_link';
        $providerEmail = $providerEmail ?? (($bursary->application_email ?? null) ?: $bursary->contact_email);
        $providerPostalAddress = $providerPostalAddress ?? ($bursary->application_postal_address ?? null);
        $isChamuHandled = $isChamuHandled ?? false;
        $isPostalSubmission = $isPostalSubmission ?? $applicationDeliveryType === 'postal';
        $applicationTablesReady = $applicationTablesReady ?? true;
        $applicationDeliveryLabel = $isPostalSubmission ? 'postal submission' : 'email submission';
        $latestDeliveryType = $latestApplication->delivery_type ?? ($isPostalSubmission ? 'postal' : 'email');
        $applicationActionLabel = $isPostalSubmission ? 'Apply with Postal' : 'Apply with Chamu';
        $applicationActionIcon = $isPostalSubmission ? 'package-check' : 'send';
        $applicationBadgeLabel = $isPostalSubmission ? 'Postal required' : 'Chamu application';
        $applicationPanelLabel = $isPostalSubmission
            ? 'Postal submission required'
            : ($isChamuHandled ? 'Chamu-managed '.$applicationDeliveryLabel : 'Provider application');
        $applicationModalTitle = $isPostalSubmission ? 'Apply with Postal' : 'Apply with Chamu';
        $applicationModalIntro = $isPostalSubmission
            ? 'This bursary requires postal or hand-delivery submission. Chamu prepares the pack; you still need to print and submit it.'
            : 'Review your details before Chamu sends the application.';
        $academicDocumentKeys = ['academic_transcript', 'grade_12_marks', 'grade_11_marks', 'matric_certificate'];
        $applicationProfile = $applicationProfile ?? null;
        $savedApplicationDocuments = $savedApplicationDocuments ?? collect();
        $profileSpecialCircumstances = $applicationProfile->special_circumstances ?? [];
        $closingDate = $bursary->closing_date ? \Illuminate\Support\Carbon::parse($bursary->closing_date)->startOfDay() : null;
        $today = now()->startOfDay();
        $applicationsClosed = $closingDate !== null && $closingDate->lt($today);
        $closingDateDisplay = $applicationsClosed
            ? (filled($bursary->closing_date_label) && str_starts_with(strtolower((string) $bursary->closing_date_label), 'closed')
                ? $bursary->closing_date_label
                : 'Closed — '.$closingDate->format('j F Y'))
            : ($bursary->closing_date_label ?? 'Closing date not listed');
        $closingContext = match (true) {
            $closingDate === null => [
                'label' => 'Closing date to confirm',
                'body' => 'Chamu does not have a structured closing date for this bursary yet. Check the provider source before preparing documents.',
                'tone' => 'amber',
            ],
            $closingDate->isSameDay($today) => [
                'label' => 'Closes today',
                'body' => 'Submit only if the provider still accepts applications today and you can meet every document requirement.',
                'tone' => 'amber',
            ],
            $applicationsClosed => [
                'label' => 'Applications closed',
                'body' => 'Do not apply. This bursary closed on '.$closingDate->format('j F Y').'. The provider is not accepting applications for this cycle. Browse related open bursaries below, or check the source only for a future cycle.',
                'tone' => 'rose',
            ],
            default => [
                'label' => $closingDate->diffInDays($today).' days left',
                'body' => 'Use the remaining time to confirm eligibility, prepare certified copies if required, and submit before the provider deadline.',
                'tone' => 'emerald',
            ],
        };
        $canApplyWithChamu = $isChamuHandled && $documentRequirements->isNotEmpty() && ! $applicationsClosed;
        $closingContextClasses = [
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-950',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-900',
        ][$closingContext['tone']] ?? 'border-neutral-200 bg-neutral-50 text-neutral-800';

        if (is_string($profileSpecialCircumstances)) {
            $profileSpecialCircumstances = json_decode($profileSpecialCircumstances, true) ?: [];
        }

        $profileDocumentCount = $savedApplicationDocuments->flatten(1)->count();
        $requiredDocumentKeys = $documentRequirements
            ->where('is_required', true)
            ->whereNull('requirement_group')
            ->pluck('key')
            ->values()
            ->all();
        $documentRequirementKeys = $documentRequirements->pluck('key')->values()->all();
        $extraSavedApplicationDocuments = $savedApplicationDocuments
            ->reject(fn ($documents, $key) => in_array($key, $documentRequirementKeys, true))
            ->flatten(1);
        $documentLabelsByKey = $documentRequirements->pluck('label', 'key')->all();
        $submittedApplication = $latestApplication && in_array($latestApplication->status, ['submitted', 'postal_ready'], true);
        $companyName = $bursary->company_name ?? 'Bursary provider';
        $fundingRows = collect([
            'Fields covered' => $bursary->fields_covered,
            'Coverage value' => $bursary->coverage_value,
            'Service contract' => $bursary->service_contract,
            'Renewal' => $bursary->renewal,
        ])->filter();
        $sourceToneClasses = [
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            'sky' => 'border-sky-200 bg-sky-50 text-sky-900',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-950',
        ][$sourceInfo['tone'] ?? 'sky'] ?? 'border-neutral-200 bg-neutral-50 text-neutral-800';

        if (! $isChamuHandled && $bursary->application_method) {
            $fundingRows->put('How to apply', $bursary->application_method);
        }
    @endphp

    <main class="bg-[#f5f7fb] text-neutral-950">
        <section class="border-b border-neutral-200 bg-white">
            <div class="mx-auto max-w-7xl px-5 py-5 lg:px-8">
                <a href="{{ route('bursaries.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-neutral-500 hover:text-neutral-900">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                    Bursaries
                </a>
            </div>
        </section>

        @if (session('status') || $submittedApplication)
            <section class="border-b border-emerald-200 bg-emerald-50">
                <div class="mx-auto max-w-5xl px-5 py-8 text-center lg:px-8">
                    <div class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-600 text-white">
                        <i data-lucide="check" style="width:22px;height:22px;"></i>
                    </div>
                    <h1 class="mt-4 text-3xl font-black text-emerald-950">
                        {{ $latestDeliveryType === 'postal' ? 'Your postal application is ready' : 'Your application has been sent' }}
                    </h1>
                    <p class="mt-2 text-sm font-semibold text-emerald-800">
                        @if ($latestDeliveryType === 'postal')
                            Chamu prepared your postal pack and sent your receipt to {{ auth()->user()?->email ?? 'your email address' }}. You still need to print and submit it to the provider.
                        @else
                            Chamu emailed the bursary provider and sent your receipt to {{ auth()->user()?->email ?? 'your email address' }}.
                        @endif
                    </p>
                    <div class="mx-auto mt-5 max-w-3xl rounded-xl border border-emerald-200 bg-white p-4 text-left">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">Application summary</p>
                        <div class="mt-3 grid gap-3 text-sm font-semibold text-neutral-700 sm:grid-cols-3">
                            <span class="inline-flex items-center gap-2"><i data-lucide="briefcase" style="width:16px;height:16px;"></i>{{ $bursary->title }}</span>
                            <span class="inline-flex items-center gap-2"><i data-lucide="building-2" style="width:16px;height:16px;"></i>{{ $companyName }}</span>
                            <span class="inline-flex items-center gap-2"><i data-lucide="{{ $latestDeliveryType === 'postal' ? 'package-check' : 'mail-check' }}" style="width:16px;height:16px;"></i>Receipt sent</span>
                        </div>
                        @if ($latestDeliveryType === 'postal' && $latestApplication)
                            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                                <a href="{{ route('applications.postal-pack', $latestApplication->id) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-black text-white hover:bg-[#001A48]">
                                    Print postal pack <i data-lucide="printer" style="width:16px;height:16px;"></i>
                                </a>
                                @if ($bursary->source_url)
                                    <a href="{{ $bursary->source_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 px-4 py-2.5 text-sm font-black text-emerald-800 hover:bg-emerald-50">
                                        Source instructions <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @elseif ($errors->any())
            <section class="border-b border-red-200 bg-red-50">
                <div class="mx-auto max-w-7xl px-5 py-4 text-sm font-bold text-red-700 lg:px-8">
                    Please check the application form and try again.
                </div>
            </section>
        @endif

        @if ($applicationsClosed)
            <section class="border-b border-rose-200 bg-rose-50">
                <div class="mx-auto max-w-7xl px-5 py-4 lg:px-8">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm font-black text-rose-950">
                            Applications closed — this bursary closed on {{ $closingDate->format('j F Y') }}. Do not apply for this cycle.
                        </p>
                        <a href="{{ route('bursaries.index', ['category' => $bursary->category]) }}" class="inline-flex w-fit items-center gap-2 text-sm font-black text-rose-900 underline">
                            Browse open bursaries <i data-lucide="arrow-right" style="width:15px;height:15px;"></i>
                        </a>
                    </div>
                </div>
            </section>
        @endif

        <section class="mx-auto grid max-w-7xl gap-6 px-5 py-8 lg:grid-cols-[minmax(0,1fr)_360px] lg:px-8">
            <div class="min-w-0 space-y-5">
                <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-black text-[#01225E]">{{ $companyName }}</p>
                            <h1 class="mt-2 text-3xl font-black leading-tight sm:text-4xl">{{ $bursary->title }}</h1>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm font-bold text-neutral-600">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-3 py-1">
                                    <i data-lucide="tag" style="width:14px;height:14px;"></i>{{ $bursary->category ?? 'Bursary' }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 {{ $applicationsClosed ? 'bg-rose-50 text-rose-800' : 'bg-neutral-100' }}">
                                    <i data-lucide="{{ $applicationsClosed ? 'circle-x' : 'calendar-days' }}" style="width:14px;height:14px;"></i>{{ $closingDateDisplay }}
                                </span>
                                @if ($applicationsClosed)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-600 px-3 py-1 text-white">
                                        <i data-lucide="ban" style="width:14px;height:14px;"></i>Applications closed
                                    </span>
                                @elseif ($isChamuHandled)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-emerald-800">
                                        <i data-lucide="{{ $isPostalSubmission ? 'package-check' : 'shield-check' }}" style="width:14px;height:14px;"></i>{{ $applicationBadgeLabel }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($bursary->summary)
                        <p class="mt-5 max-w-3xl text-base font-medium leading-7 text-neutral-700">{{ $bursary->summary }}</p>
                    @endif

                    <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border p-4 {{ $applicationsClosed ? 'border-rose-200 bg-rose-50' : 'border-neutral-200 bg-neutral-50' }}">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] {{ $applicationsClosed ? 'text-rose-700' : 'text-neutral-500' }}">Closes</dt>
                            <dd class="mt-1 text-sm font-black {{ $applicationsClosed ? 'text-rose-950' : '' }}">{{ $closingDateDisplay }}</dd>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">Academic reqs</dt>
                            <dd class="mt-1 text-2xl font-black">{{ $requirements->count() }}</dd>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">Application</dt>
                            <dd class="mt-1 text-sm font-black">{{ $applicationsClosed ? 'Not accepting applications' : $applicationPanelLabel }}</dd>
                        </div>
                    </dl>
                </article>

                @include('partials.adsterra-feed-banner')

                <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black">Funding details</h2>
                    @if ($fundingRows->isEmpty())
                        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm font-semibold leading-6 text-sky-950">
                            Chamu has not captured the full funding package for this opportunity yet. Before applying, confirm whether the provider covers registration, tuition, books, accommodation, meals, travel, device costs, or a monthly allowance.
                            @if ($bursary->source_url)
                                <a href="{{ $bursary->source_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-2 font-black text-[#01225E] underline">
                                    Check source details <i data-lucide="external-link" style="width:15px;height:15px;"></i>
                                </a>
                            @endif
                        </div>
                    @else
                        <dl class="mt-5 divide-y divide-neutral-200">
                            @foreach ($fundingRows as $label => $value)
                                <div class="grid gap-2 py-4 first:pt-0 sm:grid-cols-[180px_1fr]">
                                    <dt class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">{{ $label }}</dt>
                                    <dd class="text-sm font-medium leading-6 text-neutral-700">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </article>

                <section class="grid gap-5 lg:grid-cols-2">
                    <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black">Application method</h2>
                        <p class="mt-3 text-sm font-semibold leading-6 text-neutral-700">
                            @if ($applicationsClosed)
                                Applications for this bursary are closed. Chamu will not prepare or send an application for this opportunity.
                            @elseif ($isChamuHandled)
                                {{ $isPostalSubmission ? 'Chamu can help prepare a printable postal pack, but you still need to post or hand-deliver it according to the provider instructions.' : 'Chamu can help prepare and send this application using the captured provider email route.' }}
                            @elseif ($bursary->application_method)
                                {{ $bursary->application_method }}
                            @elseif ($bursary->apply_url)
                                This bursary uses a provider application link. Open the official application page and follow the provider instructions there.
                            @else
                                Chamu has not captured a complete application method yet. Use the source page to confirm whether the provider accepts online, email, postal, or hand-delivery submissions.
                            @endif
                        </p>
                        @if (! $applicationsClosed && $bursary->apply_url)
                            <a href="{{ $bursary->apply_url }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-neutral-300 px-4 py-2 text-sm font-black hover:bg-neutral-50">
                                Open application route <i data-lucide="external-link" style="width:15px;height:15px;"></i>
                            </a>
                        @endif
                    </article>

                    <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black">Closing-date context</h2>
                        <div class="mt-4 rounded-xl border p-4 text-sm font-semibold leading-6 {{ $closingContextClasses }}">
                            <p class="font-black">{{ $closingContext['label'] }}</p>
                            <p class="mt-2">{{ $closingContext['body'] }}</p>
                            @if ($closingDate)
                                <p class="mt-3 text-xs font-black uppercase">Captured date: <time datetime="{{ $closingDate->toDateString() }}">{{ $closingDate->format('j F Y') }}</time></p>
                            @endif
                        </div>
                    </article>
                </section>

                <section class="grid gap-5 lg:grid-cols-2">
                    <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black">Eligibility</h2>
                        @if (count($bursary->eligibility_requirements) > 0)
                            <ul class="mt-5 grid gap-3 text-sm font-medium leading-6 text-neutral-700">
                                @foreach ($bursary->eligibility_requirements as $requirement)
                                    <li class="flex gap-3">
                                        <i data-lucide="check-circle-2" class="mt-0.5 shrink-0 text-emerald-600" style="width:17px;height:17px;"></i>
                                        <span>{{ $requirement }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-6 text-amber-950">
                                Chamu has not captured provider-specific eligibility rules for this bursary yet. Confirm citizenship or residency, study level, field of study, institution type, academic performance, financial need, and province or community restrictions before applying.
                            </div>
                        @endif
                    </article>

                    <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black">Academic requirements</h2>
                        @if ($requirements->isEmpty())
                            <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm font-semibold leading-6 text-sky-950">
                                No structured mark rules are captured yet. Treat this as a manual-review bursary: check the provider source for minimum averages, required subjects, study-year limits, and whether latest results or full transcripts are needed.
                            </div>
                        @else
                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach ($requirements as $requirement)
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-800">
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
                                <p class="mt-3 text-sm font-semibold text-neutral-500">{{ $note }}</p>
                            @endforeach
                        @endif
                    </article>
                </section>

                @include('partials.adsterra-feed-banner')

                <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-xl font-black">Supporting documents</h2>
                            @if ($isChamuHandled)
                            <p class="mt-1 text-sm font-semibold text-neutral-500">
                                {{ $isPostalSubmission ? 'These are the files Chamu will prepare into your postal pack for printing.' : 'These are the files Chamu will attach to your bursary application.' }}
                            </p>
                            @endif
                        </div>
                        @if ($isChamuHandled && $documentRequirements->where('requirement_group', 'academic_record')->isNotEmpty())
                            <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">
                                <i data-lucide="file-check-2" style="width:14px;height:14px;"></i>One academic file required
                            </span>
                        @endif
                    </div>

                    @if ($documentRequirements->isNotEmpty())
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ($documentRequirements as $document)
                                <div class="rounded-xl border border-neutral-200 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-black">{{ $document->label }}</span>
                                        @if ($document->is_required)
                                            <span class="rounded-full bg-[#01225E] px-2 py-0.5 text-[11px] font-black uppercase text-white">Required</span>
                                        @elseif (in_array($document->key, $academicDocumentKeys, true))
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-black uppercase text-amber-800">Academic</span>
                                        @endif
                                    </div>
                                    @if ($document->description)
                                        <p class="mt-2 text-xs font-semibold leading-5 text-neutral-500">{{ $document->description }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif (count($bursary->supporting_documents) > 0)
                        <ul class="mt-5 grid gap-3 text-sm font-medium text-neutral-700 sm:grid-cols-2">
                            @foreach ($bursary->supporting_documents as $document)
                                <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-neutral-400"></span>{{ $document }}</li>
                            @endforeach
                        </ul>
                    @else
                        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm font-semibold leading-6 text-sky-950">
                            A complete document checklist is not captured yet. Most bursary applications may ask for an ID copy, latest academic results, proof of registration or acceptance, proof of household income, a CV, and a motivation letter. Confirm the exact list on the provider source before submitting.
                        </div>
                    @endif
                </article>
            </div>

            <aside class="grid content-start gap-5 lg:sticky lg:top-24 lg:self-start">
                <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">You are applying for</p>
                    <h2 class="mt-2 text-xl font-black leading-tight text-[#01225E]">{{ $bursary->title }}</h2>
                    <p class="mt-1 text-sm font-bold text-neutral-600">{{ $companyName }}</p>

                    <div class="mt-5 space-y-3 border-y border-neutral-200 py-5 text-sm font-semibold text-neutral-700">
                        <p class="flex items-center gap-2"><i data-lucide="calendar-days" style="width:16px;height:16px;"></i>{{ $closingDateDisplay }}</p>
                        <p class="flex items-center gap-2"><i data-lucide="folder-check" style="width:16px;height:16px;"></i>{{ $documentRequirements->count() }} document checks</p>
                        <p class="flex items-center gap-2"><i data-lucide="{{ $applicationsClosed ? 'ban' : ($isPostalSubmission ? 'package-check' : 'mail-check') }}" style="width:16px;height:16px;"></i>{{ $applicationsClosed ? 'Not accepting applications' : $applicationPanelLabel }}</p>
                    </div>

                    @if ($applicationsClosed)
                        <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold leading-6 text-rose-950">
                            <p class="inline-flex items-center gap-2 font-black"><i data-lucide="circle-x" style="width:16px;height:16px;"></i>Applications closed</p>
                            <p class="mt-2 font-semibold">This bursary is not open. Do not prepare or submit an application for this cycle.</p>
                        </div>
                        @if ($bursary->source_url)
                            <a href="{{ $bursary->source_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-300 px-5 py-3 text-sm font-black hover:bg-neutral-50">
                                Check source for future cycles <i data-lucide="external-link" style="width:18px;height:18px;"></i>
                            </a>
                        @endif
                        <a href="{{ route('bursaries.index', ['category' => $bursary->category]) }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 text-sm font-black text-white hover:bg-[#001A48]">
                            Browse open bursaries <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                        </a>
                    @elseif ($canApplyWithChamu)
                        @auth
                            <button type="button" data-open-apply-modal class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 text-sm font-black text-white shadow-[0_14px_30px_rgba(1,34,94,0.22)] hover:bg-[#001A48]">
                                {{ $applicationActionLabel }} <i data-lucide="{{ $applicationActionIcon }}" style="width:18px;height:18px;"></i>
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 text-sm font-black text-white shadow-[0_14px_30px_rgba(1,34,94,0.22)] hover:bg-[#001A48]">
                                {{ $applicationActionLabel }} <i data-lucide="log-in" style="width:18px;height:18px;"></i>
                            </a>
                            <a href="{{ route('register') }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-300 px-5 py-3 text-sm font-black hover:bg-neutral-50">
                                Sign up <i data-lucide="user-plus" style="width:18px;height:18px;"></i>
                            </a>
                        @endauth
                    @elseif (! $applicationTablesReady && $isChamuHandled)
                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-900">
                            Bursary applications are being prepared. Run the latest migrations to enable Apply with Chamu.
                        </div>
                    @else
                        @if ($bursary->apply_url)
                            <a href="{{ $bursary->apply_url }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 text-sm font-black text-white hover:bg-[#001A48]">
                                Apply Link <i data-lucide="external-link" style="width:18px;height:18px;"></i>
                            </a>
                        @endif
                        @if ($bursary->source_url)
                            <a href="{{ $bursary->source_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-300 px-5 py-3 text-sm font-black hover:bg-neutral-50">
                                Source <i data-lucide="external-link" style="width:18px;height:18px;"></i>
                            </a>
                        @endif
                    @endif

                    @if ($latestApplication)
                        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm font-bold text-sky-900">
                            Latest application: {{ Str::of($latestApplication->status)->title() }}
                        </div>
                    @endif
                </section>

                <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm" aria-labelledby="source-heading">
                    <h2 id="source-heading" class="text-lg font-black">Source and review</h2>
                    <div class="mt-4 rounded-xl border p-4 text-sm font-semibold leading-6 {{ $sourceToneClasses }}">
                        <p class="font-black">{{ $sourceInfo['label'] }}</p>
                        <p class="mt-2">{{ $sourceInfo['summary'] }}</p>
                    </div>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="font-semibold text-neutral-500">Last reviewed</dt>
                            <dd class="text-right font-black text-neutral-950">
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
                                <dd class="break-all text-right font-black text-neutral-950">{{ $sourceInfo['source_host'] }}</dd>
                            </div>
                        @endif
                    </dl>
                    @if ($sourceInfo['source_url'])
                        <a href="{{ $sourceInfo['source_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-300 px-4 py-3 text-sm font-black hover:bg-neutral-50">
                            Open source <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                        </a>
                    @endif
                </section>

                <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm" aria-labelledby="safety-heading">
                    <h2 id="safety-heading" class="text-lg font-black">Application safety</h2>
                    <ul class="mt-4 grid gap-3 text-sm font-semibold leading-6 text-neutral-700">
                        <li class="flex gap-2"><i data-lucide="shield-check" class="mt-0.5 shrink-0 text-emerald-600" style="width:16px;height:16px;"></i>Use official provider portals or verified email addresses.</li>
                        <li class="flex gap-2"><i data-lucide="badge-alert" class="mt-0.5 shrink-0 text-amber-600" style="width:16px;height:16px;"></i>Do not pay anyone to guarantee funding or fast-track approval.</li>
                        <li class="flex gap-2"><i data-lucide="file-check-2" class="mt-0.5 shrink-0 text-sky-600" style="width:16px;height:16px;"></i>Keep proof of submission, reference numbers, and sent-email receipts.</li>
                    </ul>
                </section>

            </aside>
        </section>

        <div class="mx-auto max-w-7xl px-5 pb-6 lg:px-8">
            @include('partials.adsterra-feed-banner')
        </div>

        @if ($relatedBursaries->isNotEmpty())
            <section class="mx-auto max-w-7xl px-5 pb-10 lg:px-8" aria-labelledby="related-bursaries-heading">
                <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 id="related-bursaries-heading" class="text-2xl font-black">Related bursaries</h2>
                            <p class="mt-2 text-sm font-semibold text-neutral-500">Similar funding opportunities based on provider, field or category.</p>
                        </div>
                        <a href="{{ route('bursaries.index', ['category' => $bursary->category]) }}" class="inline-flex w-fit items-center gap-2 text-sm font-black text-[#01225E] hover:text-[#001A48]">
                            Browse more <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                        </a>
                    </div>
                    <div class="mt-5 grid gap-3">
                        @foreach ($relatedBursaries as $related)
                            <a href="{{ route('bursaries.show', ['bursary' => $related->id]) }}" class="block rounded-xl border border-neutral-200 bg-neutral-50 p-4 hover:bg-white">
                                <span class="block text-sm font-black text-neutral-950">{{ $related->title }}</span>
                                <span class="mt-1 block text-xs font-bold text-neutral-500">{{ $related->company_name ?? 'Provider' }} · {{ $related->category ?? 'Bursary' }}</span>
                                <span class="mt-2 block text-xs font-bold text-[#01225E]">{{ $related->closing_date_label ?? 'Closing date to confirm' }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @auth
            @if ($canApplyWithChamu)
                <div id="bursary-apply-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-neutral-950/60 px-4 py-6 backdrop-blur-sm">
                    <div class="mx-auto max-w-4xl rounded-xl bg-white shadow-2xl">
                        <div class="flex items-start justify-between gap-4 border-b border-neutral-200 px-5 py-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[#01225E]">{{ $companyName }}</p>
                                <h2 class="mt-1 text-2xl font-black">{{ $applicationModalTitle }}</h2>
                                <p class="mt-1 text-sm font-semibold text-neutral-500">
                                    {{ $applicationModalIntro }}
                                </p>
                            </div>
                            <button type="button" data-close-apply-modal class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-300 hover:bg-neutral-50" aria-label="Close application form">
                                <i data-lucide="x" style="width:18px;height:18px;"></i>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('bursaries.apply', $bursary->id) }}" enctype="multipart/form-data" class="p-5" data-apply-form>
                            @csrf

                            @error('application')
                                <p class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</p>
                            @enderror

                            <div class="mb-5 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-900">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p>
                                        {{ $profileDocumentCount > 0 ? $profileDocumentCount.' saved profile document'.($profileDocumentCount === 1 ? '' : 's').' ready to reuse.' : 'Save your ID, CV, and marks once to make future bursary applications faster.' }}
                                    </p>
                                    <a href="{{ route('profile.application') }}" class="inline-flex w-fit items-center gap-2 rounded-lg border border-sky-300 bg-white px-3 py-2 text-xs font-black text-[#01225E] hover:bg-sky-100">
                                        Application profile <i data-lucide="folder-check" style="width:15px;height:15px;"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="mb-5 grid gap-2 sm:grid-cols-2">
                                <div class="rounded-xl border border-[#01225E] bg-blue-50 px-4 py-3">
                                    <p class="text-xs font-black uppercase tracking-[0.14em] text-[#01225E]">Step 1</p>
                                    <p class="mt-1 text-sm font-black">Details and documents</p>
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                                    <p class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">Step 2</p>
                                    <p class="mt-1 text-sm font-black">{{ $isPostalSubmission ? 'Print and submit' : 'Confirm and send' }}</p>
                                </div>
                            </div>

                            <section data-apply-step="details">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    @if ($isPostalSubmission)
                                        <div class="sm:col-span-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-950">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="text-xs font-black uppercase tracking-[0.14em] text-amber-700">Postal submission required</p>
                                                    <p class="mt-2 leading-6">This bursary does not get emailed by Chamu. We prepare the application pack so you can print and post or hand-deliver it.</p>
                                                    @if ($providerPostalAddress)
                                                        <p class="mt-3"><span class="font-black">Provider address:</span><br>{{ $providerPostalAddress }}</p>
                                                    @endif
                                                </div>
                                                @if ($bursary->source_url)
                                                    <a href="{{ $bursary->source_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex w-fit items-center gap-2 rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs font-black text-amber-900 hover:bg-amber-100">
                                                        Source instructions <i data-lucide="external-link" style="width:15px;height:15px;"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    <div>
                                        <label for="applicant_phone" class="block text-sm font-bold mb-2">Phone</label>
                                        <input id="applicant_phone" name="applicant_phone" value="{{ old('applicant_phone', $applicationProfile->applicant_phone ?? '') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('applicant_phone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="study_level" class="block text-sm font-bold mb-2">Study level</label>
                                        @php
                                            $selectedStudyLevel = old('study_level', $applicationProfile->study_level ?? '');
                                        @endphp
                                        <select id="study_level" name="study_level" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                            <option value="">Choose level</option>
                                            <option value="Pupil (High School)" @selected($selectedStudyLevel === 'Pupil (High School)')>Pupil (High School)</option>
                                            <option value="Student (University/College)" @selected($selectedStudyLevel === 'Student (University/College)')>Student (University/College)</option>
                                            <option value="Other" @selected($selectedStudyLevel === 'Other')>Other</option>
                                        </select>
                                        @error('study_level') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="institution" class="block text-sm font-bold mb-2">Institution</label>
                                        <input id="institution" name="institution" value="{{ old('institution', $applicationProfile->institution ?? '') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('institution') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="qualification" class="block text-sm font-bold mb-2">Qualification or field</label>
                                        <input id="qualification" name="qualification" value="{{ old('qualification', $applicationProfile->qualification ?? '') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('qualification') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_year" class="block text-sm font-bold mb-2">Current year</label>
                                        <input id="current_year" name="current_year" value="{{ old('current_year', $applicationProfile->current_year ?? '') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('current_year') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    @if ($isPostalSubmission)
                                        <div class="sm:col-span-2">
                                            <label for="applicant_postal_address" class="block text-sm font-bold mb-2">Postal or return address</label>
                                            <textarea id="applicant_postal_address" name="applicant_postal_address" rows="3" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">{{ old('applicant_postal_address', $applicationProfile->applicant_postal_address ?? '') }}</textarea>
                                            <p class="mt-2 text-xs font-semibold text-neutral-500">Chamu adds this to the printable postal pack so the provider has your correct return address details.</p>
                                            @error('applicant_postal_address') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                    <div>
                                        <label for="household_income" class="block text-sm font-bold mb-2">Household income context</label>
                                        <input id="household_income" name="household_income" value="{{ old('household_income', $applicationProfile->household_income ?? '') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('household_income') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="funding_need" class="block text-sm font-bold mb-2">Funding need</label>
                                        <textarea id="funding_need" name="funding_need" rows="3" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">{{ old('funding_need', $applicationProfile->funding_need ?? '') }}</textarea>
                                        @error('funding_need') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <section class="mt-6 border-t border-neutral-200 pt-5">
                                    <h3 class="text-base font-black">Applicant circumstances</h3>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                        <label class="flex items-start gap-2 rounded-xl border border-neutral-200 p-3 text-sm font-semibold">
                                            <input type="checkbox" name="sassa_recipient" value="1" @checked(old('sassa_recipient', $applicationProfile->sassa_recipient ?? false)) class="mt-1">
                                            SASSA grant recipient
                                        </label>
                                        <label class="flex items-start gap-2 rounded-xl border border-neutral-200 p-3 text-sm font-semibold">
                                            <input type="checkbox" name="special_circumstances[]" value="disability" @checked(in_array('disability', old('special_circumstances', $profileSpecialCircumstances), true)) class="mt-1">
                                            Disability
                                        </label>
                                        <label class="flex items-start gap-2 rounded-xl border border-neutral-200 p-3 text-sm font-semibold">
                                            <input type="checkbox" name="special_circumstances[]" value="vulnerable_child" @checked(in_array('vulnerable_child', old('special_circumstances', $profileSpecialCircumstances), true)) class="mt-1">
                                            Vulnerable child
                                        </label>
                                    </div>
                                </section>

                                <section class="mt-6 border-t border-neutral-200 pt-5">
                                    <h3 class="text-base font-black">Documents</h3>
                                    <p class="mt-1 text-sm font-semibold text-neutral-500">Upload certified copies where the bursary asks for certified copies.</p>
                                    <p data-document-error class="mt-3 hidden text-sm font-bold text-red-600"></p>
                                    <p data-academic-error class="mt-3 hidden text-sm font-bold text-red-600">Upload at least one academic record, transcript, Grade 12 marks, Grade 11 marks, or matric certificate.</p>
                                    @error('documents.academic_record') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        @foreach ($documentRequirements as $document)
                                            @php
                                                $storedForKey = $savedApplicationDocuments->get($document->key, collect());
                                                $defaultSavedDocumentIds = $storedForKey->pluck('id')->map(fn ($id) => (string) $id)->all();
                                                $oldSavedDocumentIds = old("saved_documents.{$document->key}", $defaultSavedDocumentIds);
                                                $oldSavedDocumentIds = is_array($oldSavedDocumentIds) ? array_map('strval', $oldSavedDocumentIds) : [(string) $oldSavedDocumentIds];
                                            @endphp
                                            <div class="rounded-xl border border-neutral-200 p-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <label for="document_{{ $document->key }}" class="font-black">{{ $document->label }}</label>
                                                    @if ($document->is_required)
                                                        <span class="rounded-full bg-[#01225E] px-2 py-0.5 text-[11px] font-black uppercase text-white">Required</span>
                                                    @elseif (in_array($document->key, $academicDocumentKeys, true))
                                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-black uppercase text-amber-800">Academic</span>
                                                    @endif
                                                </div>
                                                @if ($document->description)
                                                    <p class="mt-1 text-xs font-semibold leading-5 text-neutral-500">{{ $document->description }}</p>
                                                @endif
                                                @if ($storedForKey->isNotEmpty())
                                                    <div class="mt-3 space-y-2">
                                                        <p class="text-xs font-black uppercase tracking-[0.12em] text-neutral-500">Saved in profile</p>
                                                        @foreach ($storedForKey as $storedDocument)
                                                            <label class="flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-950">
                                                                <input
                                                                    type="checkbox"
                                                                    name="saved_documents[{{ $document->key }}][]"
                                                                    value="{{ $storedDocument->id }}"
                                                                    data-saved-document-key="{{ $document->key }}"
                                                                    data-saved-document-label="{{ $document->label }}"
                                                                    data-saved-document-name="{{ $storedDocument->original_name }}"
                                                                    @if (in_array($document->key, $academicDocumentKeys, true)) data-academic-record="true" @endif
                                                                    @checked(in_array((string) $storedDocument->id, $oldSavedDocumentIds, true))
                                                                >
                                                                <span class="min-w-0 flex-1 truncate">{{ $storedDocument->original_name }}</span>
                                                                <span class="shrink-0 text-xs text-emerald-700">{{ number_format(($storedDocument->size ?? 0) / 1024, 1) }} KB</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                <input
                                                    id="document_{{ $document->key }}"
                                                    name="documents[{{ $document->key }}][]"
                                                    type="file"
                                                    @if ($document->accepts_multiple) multiple @endif
                                                    data-document-key="{{ $document->key }}"
                                                    @if (in_array($document->key, $academicDocumentKeys, true)) data-academic-record="true" @endif
                                                    data-document-label="{{ $document->label }}"
                                                    class="mt-3 w-full rounded-xl border border-neutral-300 px-4 py-3 text-sm font-semibold file:mr-4 file:rounded-lg file:border-0 file:bg-[#01225E] file:px-3 file:py-2 file:font-bold file:text-white"
                                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                >
                                                @error('documents.'.$document->key) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                                @error('documents.'.$document->key.'.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                        @endforeach
                                    </div>

                                    @if ($extraSavedApplicationDocuments->isNotEmpty())
                                        <div class="mt-4 rounded-xl border border-dashed border-neutral-300 p-4">
                                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <h4 class="font-black">Other saved documents</h4>
                                                    <p class="mt-1 text-xs font-semibold text-neutral-500">Include extra profile files only when this bursary asks for them.</p>
                                                </div>
                                                <span class="w-fit rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-black uppercase text-neutral-600">Optional</span>
                                            </div>
                                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                                @foreach ($extraSavedApplicationDocuments as $storedDocument)
                                                    @php
                                                        $oldExtraDocumentIds = old("saved_documents.{$storedDocument->document_key}", []);
                                                        $oldExtraDocumentIds = is_array($oldExtraDocumentIds) ? array_map('strval', $oldExtraDocumentIds) : [(string) $oldExtraDocumentIds];
                                                    @endphp
                                                    <label class="flex items-center gap-3 rounded-xl bg-neutral-50 px-3 py-2 text-sm font-semibold text-neutral-800">
                                                        <input
                                                            type="checkbox"
                                                            name="saved_documents[{{ $storedDocument->document_key }}][]"
                                                            value="{{ $storedDocument->id }}"
                                                            data-saved-document-key="{{ $storedDocument->document_key }}"
                                                            data-saved-document-label="{{ $storedDocument->label }}"
                                                            data-saved-document-name="{{ $storedDocument->original_name }}"
                                                            @checked(in_array((string) $storedDocument->id, $oldExtraDocumentIds, true))
                                                        >
                                                        <span class="min-w-0 flex-1 truncate">{{ $storedDocument->label }}: {{ $storedDocument->original_name }}</span>
                                                        <span class="shrink-0 text-xs text-neutral-500">{{ number_format(($storedDocument->size ?? 0) / 1024, 1) }} KB</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </section>

                                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                    <button type="button" data-close-apply-modal class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-3 font-bold hover:bg-neutral-50">Cancel</button>
                                    <button type="button" data-review-application class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-black text-white hover:bg-[#001A48]">
                                        Review application <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                                    </button>
                                </div>
                            </section>

                            <section data-apply-step="review" class="hidden">
                                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_260px]">
                                    <section class="rounded-xl border border-neutral-200 p-5">
                                        <h3 class="text-xl font-black text-[#01225E]">Application summary</h3>
                                        <div class="mt-4 grid gap-3 text-sm font-semibold text-neutral-700">
                                            <p class="flex items-center gap-2"><i data-lucide="user" style="width:16px;height:16px;"></i>{{ auth()->user()->name }}</p>
                                            <p class="flex items-center gap-2"><i data-lucide="mail" style="width:16px;height:16px;"></i>{{ auth()->user()->email }}</p>
                                            <p class="flex items-center gap-2"><i data-lucide="phone" style="width:16px;height:16px;"></i><span data-review-phone>Not added</span></p>
                                            <p class="flex items-center gap-2"><i data-lucide="graduation-cap" style="width:16px;height:16px;"></i><span data-review-study>Not added</span></p>
                                            @if ($isPostalSubmission)
                                                <p class="flex items-start gap-2"><i data-lucide="map-pin" class="mt-0.5" style="width:16px;height:16px;"></i><span data-review-postal-address>Not added</span></p>
                                            @endif
                                        </div>

                                        <div class="mt-5 border-t border-neutral-200 pt-4">
                                            <p class="text-sm font-black">{{ $isPostalSubmission ? 'Documents in postal pack' : 'Attached documents' }}</p>
                                            <ul data-review-files class="mt-3 grid gap-2 text-sm font-semibold text-neutral-700"></ul>
                                        </div>

                                        @if ($isPostalSubmission)
                                            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-950">
                                                <p class="font-black">Postal destination</p>
                                                <p class="mt-2 whitespace-pre-line">{{ $providerPostalAddress ?: 'Use the provider postal or hand-delivery address shown in the source instructions.' }}</p>
                                                @if ($bursary->source_url)
                                                    <a href="{{ $bursary->source_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-2 text-xs font-black text-amber-900 underline">
                                                        Review source instructions <i data-lucide="external-link" style="width:14px;height:14px;"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif

                                        <label class="mt-5 flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-sm font-semibold text-neutral-700">
                                            <input type="checkbox" name="consent" value="1" required disabled @checked(old('consent')) data-consent-input class="mt-1">
                                            {{ $isPostalSubmission ? 'I understand this bursary requires postal or hand-delivery submission, and I will print and submit the prepared pack to the provider.' : 'I confirm that Chamu may email this application and attached documents on my behalf.' }}
                                        </label>
                                        @error('consent') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </section>

                                    <aside class="rounded-xl border border-neutral-200 bg-neutral-50 p-5">
                                        <p class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">You are applying for</p>
                                        <h3 class="mt-2 text-lg font-black text-[#01225E]">{{ $bursary->title }}</h3>
                                        <p class="mt-1 text-sm font-bold text-neutral-600">{{ $companyName }}</p>
                                        <div class="mt-5 space-y-3 text-sm font-semibold text-neutral-700">
                                            <p class="flex gap-2"><i data-lucide="{{ $isPostalSubmission ? 'package-check' : 'shield-check' }}" style="width:16px;height:16px;"></i>{{ $isPostalSubmission ? 'Postal pack prepared in Chamu' : 'Chamu-managed submission' }}</p>
                                            @if ($isPostalSubmission)
                                                <p class="flex gap-2"><i data-lucide="printer" style="width:16px;height:16px;"></i>Print, sign, and submit yourself</p>
                                            @else
                                                <p class="flex gap-2"><i data-lucide="reply" style="width:16px;height:16px;"></i>Provider replies to your email</p>
                                            @endif
                                            <p class="flex gap-2"><i data-lucide="receipt" style="width:16px;height:16px;"></i>You receive a receipt</p>
                                        </div>
                                    </aside>
                                </div>

                                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                    <button type="button" data-edit-application class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-3 font-bold hover:bg-neutral-50">Edit details</button>
                                    <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-black text-white hover:bg-[#001A48]">
                                        {{ $isPostalSubmission ? 'Prepare postal pack' : 'Confirm and send' }} <i data-lucide="{{ $isPostalSubmission ? 'package-check' : 'send' }}" style="width:18px;height:18px;"></i>
                                    </button>
                                </div>
                            </section>
                        </form>
                    </div>
                </div>
            @endif
        @endauth
    </main>
@endsection

@push('scripts')
    <script>
        const applyModal = document.getElementById('bursary-apply-modal');
        const applyForm = document.querySelector('[data-apply-form]');
        const detailsStep = document.querySelector('[data-apply-step="details"]');
        const reviewStep = document.querySelector('[data-apply-step="review"]');
        const consentInput = document.querySelector('[data-consent-input]');
        const academicInputs = [...document.querySelectorAll('[data-academic-record="true"]')];
        const academicError = document.querySelector('[data-academic-error]');
        const documentError = document.querySelector('[data-document-error]');
        const documentInputs = [...document.querySelectorAll('[data-document-key]')];
        const savedDocumentInputs = [...document.querySelectorAll('[data-saved-document-key]')];
        const isPostalSubmission = @json($isPostalSubmission);
        const requiredDocumentKeys = @json($requiredDocumentKeys);
        const documentLabelsByKey = @json($documentLabelsByKey);

        const openApplyModal = () => {
            if (!applyModal) return;
            applyModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };
        const closeApplyModal = () => {
            if (!applyModal) return;
            applyModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };
        const showDetailsStep = () => {
            detailsStep?.classList.remove('hidden');
            reviewStep?.classList.add('hidden');
            if (consentInput) consentInput.disabled = true;
        };
        const showReviewStep = () => {
            detailsStep?.classList.add('hidden');
            reviewStep?.classList.remove('hidden');
            if (consentInput) consentInput.disabled = false;
            if (window.lucide) lucide.createIcons();
        };
        const fieldValue = (name) => applyForm?.querySelector(`[name="${name}"]`)?.value?.trim() || 'Not added';
        const inputHasDocumentValue = (input) => input.type === 'file'
            ? input.files.length > 0
            : input.checked;
        const hasFileForKey = (key) => documentInputs.some((input) => input.dataset.documentKey === key && input.files.length > 0);
        const hasSavedForKey = (key) => savedDocumentInputs.some((input) => input.dataset.savedDocumentKey === key && input.checked);
        const focusDocumentKey = (key) => {
            const target = savedDocumentInputs.find((input) => input.dataset.savedDocumentKey === key)
                || documentInputs.find((input) => input.dataset.documentKey === key);
            target?.focus();
        };
        const validateRequiredDocuments = () => {
            const missingKey = requiredDocumentKeys.find((key) => !hasFileForKey(key) && !hasSavedForKey(key));

            if (documentError) {
                documentError.textContent = missingKey
                    ? `${documentLabelsByKey[missingKey] || 'Required document'} is required. Upload it or keep a saved profile document selected.`
                    : '';
                documentError.classList.toggle('hidden', !missingKey);
            }

            if (missingKey) focusDocumentKey(missingKey);

            return !missingKey;
        };
        const validateAcademicFiles = () => {
            if (academicInputs.length === 0) return true;
            const hasAcademicFile = academicInputs.some(inputHasDocumentValue);
            academicError?.classList.toggle('hidden', hasAcademicFile);
            if (!hasAcademicFile) academicInputs[0]?.focus();
            return hasAcademicFile;
        };
        const populateReview = () => {
            document.querySelector('[data-review-phone]').textContent = fieldValue('applicant_phone');
            document.querySelector('[data-review-study]').textContent = fieldValue('study_level');
            if (isPostalSubmission) {
                const postalReview = document.querySelector('[data-review-postal-address]');
                if (postalReview) postalReview.textContent = fieldValue('applicant_postal_address');
            }

            const fileList = document.querySelector('[data-review-files]');
            fileList.innerHTML = '';
            savedDocumentInputs.filter((input) => input.checked).forEach((input) => {
                const item = document.createElement('li');
                const label = document.createElement('span');
                const name = document.createElement('span');
                item.className = 'flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2';
                label.textContent = input.dataset.savedDocumentLabel || 'Saved document';
                name.className = 'truncate text-emerald-700';
                name.textContent = input.dataset.savedDocumentName || 'Saved in profile';
                item.append(label, name);
                fileList.appendChild(item);
            });
            document.querySelectorAll('input[type="file"][data-document-label]').forEach((input) => {
                [...input.files].forEach((file) => {
                    const item = document.createElement('li');
                    const label = document.createElement('span');
                    const name = document.createElement('span');
                    item.className = 'flex items-center justify-between gap-3 rounded-xl border border-neutral-200 px-3 py-2';
                    label.textContent = input.dataset.documentLabel || 'Uploaded document';
                    name.className = 'truncate text-neutral-500';
                    name.textContent = file.name;
                    item.append(label, name);
                    fileList.appendChild(item);
                });
            });

            if (fileList.children.length === 0) {
                const item = document.createElement('li');
                item.className = 'rounded-xl border border-neutral-200 px-3 py-2 text-neutral-500';
                item.textContent = 'No files selected';
                fileList.appendChild(item);
            }
        };

        document.querySelectorAll('[data-open-apply-modal]').forEach((button) => button.addEventListener('click', () => {
            showDetailsStep();
            openApplyModal();
        }));
        document.querySelectorAll('[data-close-apply-modal]').forEach((button) => button.addEventListener('click', closeApplyModal));
        document.querySelector('[data-edit-application]')?.addEventListener('click', showDetailsStep);
        document.querySelector('[data-review-application]')?.addEventListener('click', () => {
            if (!applyForm.reportValidity()) return;
            if (!validateRequiredDocuments()) return;
            if (!validateAcademicFiles()) return;
            populateReview();
            showReviewStep();
        });
        [...documentInputs, ...savedDocumentInputs].forEach((input) => {
            input.addEventListener('change', () => {
                validateRequiredDocuments();
                validateAcademicFiles();
            });
        });
        applyModal?.addEventListener('click', (event) => {
            if (event.target === applyModal) closeApplyModal();
        });

        if (@json($errors->any())) {
            showDetailsStep();
            openApplyModal();
        }
    </script>
@endpush

@extends('layouts.app')

@section('title', $bursary->title . ' · Chamu')

@section('content')
    @php
        $applicationDeliveryType = $bursary->application_delivery_type ?? 'external_link';
        $providerEmail = $providerEmail ?? (($bursary->application_email ?? null) ?: $bursary->contact_email);
        $providerPostalAddress = $providerPostalAddress ?? ($bursary->application_postal_address ?? null);
        $isChamuHandled = $isChamuHandled ?? false;
        $isPostalSubmission = $isPostalSubmission ?? $applicationDeliveryType === 'postal';
        $applicationTablesReady = $applicationTablesReady ?? true;
        $canApplyWithChamu = $isChamuHandled && $documentRequirements->isNotEmpty();
        $applicationDeliveryLabel = $isPostalSubmission ? 'postal submission' : 'email submission';
        $latestDeliveryType = $latestApplication->delivery_type ?? ($isPostalSubmission ? 'postal' : 'email');
        $academicDocumentKeys = ['academic_transcript', 'grade_12_marks', 'grade_11_marks', 'matric_certificate'];
        $submittedApplication = $latestApplication && in_array($latestApplication->status, ['submitted', 'postal_ready'], true);
        $companyName = $bursary->company_name ?? 'Bursary provider';
        $fundingRows = collect([
            'Fields covered' => $bursary->fields_covered,
            'Coverage value' => $bursary->coverage_value,
            'Service contract' => $bursary->service_contract,
            'Renewal' => $bursary->renewal,
        ])->filter();

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
                            Chamu saved the postal application pack and sent your receipt to {{ auth()->user()?->email ?? 'your email address' }}.
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
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-3 py-1">
                                    <i data-lucide="calendar-days" style="width:14px;height:14px;"></i>{{ $bursary->closing_date_label ?? 'Closing date not listed' }}
                                </span>
                                @if ($isChamuHandled)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-emerald-800">
                                        <i data-lucide="shield-check" style="width:14px;height:14px;"></i>Chamu application
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($bursary->summary)
                        <p class="mt-5 max-w-3xl text-base font-medium leading-7 text-neutral-700">{{ $bursary->summary }}</p>
                    @endif

                    <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">Closes</dt>
                            <dd class="mt-1 text-sm font-black">{{ $bursary->closing_date_label ?? 'Not listed' }}</dd>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">Academic reqs</dt>
                            <dd class="mt-1 text-2xl font-black">{{ $requirements->count() }}</dd>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">Application</dt>
                            <dd class="mt-1 text-sm font-black">{{ $isChamuHandled ? 'Handled by Chamu' : 'See provider' }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black">Funding details</h2>
                    @if ($fundingRows->isEmpty())
                        <p class="mt-3 text-sm font-semibold text-neutral-500">Funding details are not listed yet.</p>
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
                            <p class="mt-3 text-sm font-semibold text-neutral-500">Eligibility requirements are not listed yet.</p>
                        @endif
                    </article>

                    <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black">Academic requirements</h2>
                        @if ($requirements->isEmpty())
                            <p class="mt-3 text-sm font-semibold leading-6 text-neutral-500">No structured academic requirements have been captured for this bursary yet.</p>
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

                <article class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-xl font-black">Supporting documents</h2>
                            @if ($isChamuHandled)
                            <p class="mt-1 text-sm font-semibold text-neutral-500">
                                {{ $isPostalSubmission ? 'These are the files Chamu will include in your postal application pack.' : 'These are the files Chamu will attach to your bursary application.' }}
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
                        <p class="mt-3 text-sm font-semibold text-neutral-500">Supporting documents are not listed yet.</p>
                    @endif
                </article>
            </div>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">You are applying for</p>
                    <h2 class="mt-2 text-xl font-black leading-tight text-[#01225E]">{{ $bursary->title }}</h2>
                    <p class="mt-1 text-sm font-bold text-neutral-600">{{ $companyName }}</p>

                    <div class="mt-5 space-y-3 border-y border-neutral-200 py-5 text-sm font-semibold text-neutral-700">
                        <p class="flex items-center gap-2"><i data-lucide="calendar-days" style="width:16px;height:16px;"></i>{{ $bursary->closing_date_label ?? 'Closing date not listed' }}</p>
                        <p class="flex items-center gap-2"><i data-lucide="folder-check" style="width:16px;height:16px;"></i>{{ $documentRequirements->count() }} document checks</p>
                        <p class="flex items-center gap-2"><i data-lucide="{{ $isPostalSubmission ? 'package-check' : 'mail-check' }}" style="width:16px;height:16px;"></i>{{ $isChamuHandled ? 'Chamu-managed '.$applicationDeliveryLabel : 'Provider application' }}</p>
                    </div>

                    @if ($canApplyWithChamu)
                        @auth
                            <button type="button" data-open-apply-modal class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 text-sm font-black text-white shadow-[0_14px_30px_rgba(1,34,94,0.22)] hover:bg-[#001A48]">
                                Apply with Chamu <i data-lucide="send" style="width:18px;height:18px;"></i>
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 text-sm font-black text-white shadow-[0_14px_30px_rgba(1,34,94,0.22)] hover:bg-[#001A48]">
                                Apply with Chamu <i data-lucide="log-in" style="width:18px;height:18px;"></i>
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
                            <a href="{{ $bursary->apply_url }}" target="_blank" rel="noreferrer" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 text-sm font-black text-white hover:bg-[#001A48]">
                                Apply link <i data-lucide="external-link" style="width:18px;height:18px;"></i>
                            </a>
                        @endif
                        @if ($bursary->source_url)
                            <a href="{{ $bursary->source_url }}" target="_blank" rel="noreferrer" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-300 px-5 py-3 text-sm font-black hover:bg-neutral-50">
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
            </aside>
        </section>

        @auth
            @if ($canApplyWithChamu)
                <div id="bursary-apply-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-neutral-950/60 px-4 py-6 backdrop-blur-sm">
                    <div class="mx-auto max-w-4xl rounded-xl bg-white shadow-2xl">
                        <div class="flex items-start justify-between gap-4 border-b border-neutral-200 px-5 py-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-[#01225E]">{{ $companyName }}</p>
                                <h2 class="mt-1 text-2xl font-black">Apply with Chamu</h2>
                                <p class="mt-1 text-sm font-semibold text-neutral-500">
                                    {{ $isPostalSubmission ? 'Review your details before Chamu prepares the postal application.' : 'Review your details before Chamu sends the application.' }}
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

                            <div class="mb-5 grid gap-2 sm:grid-cols-2">
                                <div class="rounded-xl border border-[#01225E] bg-blue-50 px-4 py-3">
                                    <p class="text-xs font-black uppercase tracking-[0.14em] text-[#01225E]">Step 1</p>
                                    <p class="mt-1 text-sm font-black">Details and documents</p>
                                </div>
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                                    <p class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">Step 2</p>
                                    <p class="mt-1 text-sm font-black">Confirm and send</p>
                                </div>
                            </div>

                            <section data-apply-step="details">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="applicant_phone" class="block text-sm font-bold mb-2">Phone</label>
                                        <input id="applicant_phone" name="applicant_phone" value="{{ old('applicant_phone') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('applicant_phone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="study_level" class="block text-sm font-bold mb-2">Study level</label>
                                        <select id="study_level" name="study_level" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                            <option value="">Choose level</option>
                                            <option value="Pupil (High School)" @selected(old('study_level') === 'Pupil (High School)')>Pupil (High School)</option>
                                            <option value="Student (University/College)" @selected(old('study_level') === 'Student (University/College)')>Student (University/College)</option>
                                            <option value="Other" @selected(old('study_level') === 'Other')>Other</option>
                                        </select>
                                        @error('study_level') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="institution" class="block text-sm font-bold mb-2">Institution</label>
                                        <input id="institution" name="institution" value="{{ old('institution') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('institution') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="qualification" class="block text-sm font-bold mb-2">Qualification or field</label>
                                        <input id="qualification" name="qualification" value="{{ old('qualification') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('qualification') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_year" class="block text-sm font-bold mb-2">Current year</label>
                                        <input id="current_year" name="current_year" value="{{ old('current_year') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('current_year') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    @if ($isPostalSubmission)
                                        <div class="sm:col-span-2">
                                            <label for="applicant_postal_address" class="block text-sm font-bold mb-2">Postal or return address</label>
                                            <textarea id="applicant_postal_address" name="applicant_postal_address" rows="3" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">{{ old('applicant_postal_address') }}</textarea>
                                            <p class="mt-2 text-xs font-semibold text-neutral-500">Chamu keeps this with your postal application pack so the submission has your correct address details.</p>
                                            @error('applicant_postal_address') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                    <div>
                                        <label for="household_income" class="block text-sm font-bold mb-2">Household income context</label>
                                        <input id="household_income" name="household_income" value="{{ old('household_income') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        @error('household_income') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="funding_need" class="block text-sm font-bold mb-2">Funding need</label>
                                        <textarea id="funding_need" name="funding_need" rows="3" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">{{ old('funding_need') }}</textarea>
                                        @error('funding_need') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <section class="mt-6 border-t border-neutral-200 pt-5">
                                    <h3 class="text-base font-black">Applicant circumstances</h3>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                        <label class="flex items-start gap-2 rounded-xl border border-neutral-200 p-3 text-sm font-semibold">
                                            <input type="checkbox" name="sassa_recipient" value="1" @checked(old('sassa_recipient')) class="mt-1">
                                            SASSA grant recipient
                                        </label>
                                        <label class="flex items-start gap-2 rounded-xl border border-neutral-200 p-3 text-sm font-semibold">
                                            <input type="checkbox" name="special_circumstances[]" value="disability" @checked(in_array('disability', old('special_circumstances', []), true)) class="mt-1">
                                            Disability
                                        </label>
                                        <label class="flex items-start gap-2 rounded-xl border border-neutral-200 p-3 text-sm font-semibold">
                                            <input type="checkbox" name="special_circumstances[]" value="vulnerable_child" @checked(in_array('vulnerable_child', old('special_circumstances', []), true)) class="mt-1">
                                            Vulnerable child
                                        </label>
                                    </div>
                                </section>

                                <section class="mt-6 border-t border-neutral-200 pt-5">
                                    <h3 class="text-base font-black">Documents</h3>
                                    <p class="mt-1 text-sm font-semibold text-neutral-500">Upload certified copies where the bursary asks for certified copies.</p>
                                    <p data-academic-error class="mt-3 hidden text-sm font-bold text-red-600">Upload at least one academic record, transcript, Grade 12 marks, Grade 11 marks, or matric certificate.</p>
                                    @error('documents.academic_record') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        @foreach ($documentRequirements as $document)
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
                                                <input
                                                    id="document_{{ $document->key }}"
                                                    name="documents[{{ $document->key }}][]"
                                                    type="file"
                                                    @if ($document->accepts_multiple) multiple @endif
                                                    @if ($document->is_required) required @endif
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
                                            <p class="text-sm font-black">Attached documents</p>
                                            <ul data-review-files class="mt-3 grid gap-2 text-sm font-semibold text-neutral-700"></ul>
                                        </div>

                                        <label class="mt-5 flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 text-sm font-semibold text-neutral-700">
                                            <input type="checkbox" name="consent" value="1" required @checked(old('consent')) class="mt-1">
                                            {{ $isPostalSubmission ? 'I confirm that Chamu may prepare this postal application and keep the uploaded documents on my behalf.' : 'I confirm that Chamu may email this application and attached documents on my behalf.' }}
                                        </label>
                                        @error('consent') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </section>

                                    <aside class="rounded-xl border border-neutral-200 bg-neutral-50 p-5">
                                        <p class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">You are applying for</p>
                                        <h3 class="mt-2 text-lg font-black text-[#01225E]">{{ $bursary->title }}</h3>
                                        <p class="mt-1 text-sm font-bold text-neutral-600">{{ $companyName }}</p>
                                        <div class="mt-5 space-y-3 text-sm font-semibold text-neutral-700">
                                            <p class="flex gap-2"><i data-lucide="shield-check" style="width:16px;height:16px;"></i>Chamu-managed submission</p>
                                            @if ($isPostalSubmission)
                                                <p class="flex gap-2"><i data-lucide="package-check" style="width:16px;height:16px;"></i>Postal pack prepared in Chamu</p>
                                            @else
                                                <p class="flex gap-2"><i data-lucide="reply" style="width:16px;height:16px;"></i>Provider replies to your email</p>
                                            @endif
                                            <p class="flex gap-2"><i data-lucide="receipt-text" style="width:16px;height:16px;"></i>You receive a receipt</p>
                                        </div>
                                    </aside>
                                </div>

                                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                    <button type="button" data-edit-application class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-3 font-bold hover:bg-neutral-50">Edit details</button>
                                    <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-black text-white hover:bg-[#001A48]">
                                        {{ $isPostalSubmission ? 'Confirm application' : 'Confirm and send' }} <i data-lucide="{{ $isPostalSubmission ? 'package-check' : 'send' }}" style="width:18px;height:18px;"></i>
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
        const academicInputs = [...document.querySelectorAll('[data-academic-record="true"]')];
        const academicError = document.querySelector('[data-academic-error]');
        const isPostalSubmission = @json($isPostalSubmission);

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
        };
        const showReviewStep = () => {
            detailsStep?.classList.add('hidden');
            reviewStep?.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        };
        const fieldValue = (name) => applyForm?.querySelector(`[name="${name}"]`)?.value?.trim() || 'Not added';
        const validateAcademicFiles = () => {
            if (academicInputs.length === 0) return true;
            const hasAcademicFile = academicInputs.some((input) => input.files.length > 0);
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
            document.querySelectorAll('input[type="file"][data-document-label]').forEach((input) => {
                [...input.files].forEach((file) => {
                    const item = document.createElement('li');
                    item.className = 'flex items-center justify-between gap-3 rounded-xl border border-neutral-200 px-3 py-2';
                    item.innerHTML = `<span>${input.dataset.documentLabel}</span><span class="truncate text-neutral-500">${file.name}</span>`;
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
            if (!validateAcademicFiles()) return;
            populateReview();
            showReviewStep();
        });
        academicInputs.forEach((input) => input.addEventListener('change', validateAcademicFiles));
        applyModal?.addEventListener('click', (event) => {
            if (event.target === applyModal) closeApplyModal();
        });

        if (@json($errors->any())) {
            showDetailsStep();
            openApplyModal();
        }
    </script>
@endpush

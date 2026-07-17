@extends('layouts.app')

@section('title', $bursary->title . ' · Chamu')

@section('content')
    @php
        $applicationDeliveryType = $bursary->application_delivery_type ?? 'external_link';
        $providerEmail = ($bursary->application_email ?? null) ?: $bursary->contact_email;
        $canApplyWithChamu = ($bursary->chamu_apply_enabled ?? false)
            && $applicationDeliveryType === 'email'
            && filled($providerEmail)
            && $documentRequirements->isNotEmpty();
        $academicDocumentKeys = ['academic_transcript', 'grade_12_marks', 'grade_11_marks', 'matric_certificate'];
    @endphp

    <main class="mx-auto max-w-6xl px-5 py-8 lg:px-8">
        <a href="{{ route('bursaries.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-neutral-500 hover:text-neutral-900">
            <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
            Bursaries
        </a>

        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                Please check the application form and try again.
            </div>
        @endif

        <section class="mt-6 rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[#01225E]">{{ $bursary->company_name ?? 'Bursary provider' }}</p>
                    <h1 class="mt-2 text-3xl font-bold">{{ $bursary->title }}</h1>
                    <p class="mt-2 text-neutral-500">{{ $bursary->category ?? 'Bursary' }}</p>
                    @if ($bursary->summary)
                        <p class="mt-4 rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-600">{{ $bursary->summary }}</p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($canApplyWithChamu)
                            @auth
                                <button type="button" data-open-apply-modal class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#001A48]">
                                    Apply with Chamu <i data-lucide="send" style="width:16px;height:16px;"></i>
                                </button>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#001A48]">
                                    Log in to apply <i data-lucide="log-in" style="width:16px;height:16px;"></i>
                                </a>
                                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl border border-[#01225E] px-4 py-2 text-sm font-semibold text-[#01225E] hover:bg-blue-50">
                                    Sign up <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
                                </a>
                            @endauth
                        @elseif ($bursary->apply_url)
                            <a href="{{ $bursary->apply_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#001A48]">
                                Apply link <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                            </a>
                        @endif
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
            @if ($latestApplication)
                <div class="mt-5 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-900">
                    Latest Chamu application: {{ Str::of($latestApplication->status)->title() }} on {{ optional($latestApplication->created_at ? \Carbon\Carbon::parse($latestApplication->created_at) : null)->format('d M Y H:i') }}.
                </div>
            @endif
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
                            <li class="flex gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-[#01225E]"></span>{{ $requirement }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 text-sm text-neutral-500">Eligibility requirements are not listed yet.</p>
                @endif
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-5">
                <h2 class="text-xl font-bold">Supporting Documents</h2>
                @if ($documentRequirements->isNotEmpty())
                    <ul class="mt-4 grid gap-3 text-sm text-neutral-600">
                        @foreach ($documentRequirements as $document)
                            <li class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-bold text-neutral-950">{{ $document->label }}</span>
                                    @if ($document->is_required)
                                        <span class="rounded-full bg-[#01225E] px-2 py-0.5 text-[11px] font-bold uppercase text-white">Required</span>
                                    @elseif (in_array($document->key, $academicDocumentKeys, true))
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold uppercase text-amber-800">One academic file</span>
                                    @endif
                                </div>
                                @if ($document->description)
                                    <p class="mt-1 text-xs font-semibold text-neutral-500">{{ $document->description }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @elseif (count($bursary->supporting_documents) > 0)
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

        @auth
            @if ($canApplyWithChamu)
                <div id="bursary-apply-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-neutral-950/55 px-4 py-6 backdrop-blur-sm">
                    <div class="mx-auto max-w-3xl rounded-2xl bg-white shadow-2xl">
                        <div class="flex items-start justify-between gap-4 border-b border-neutral-200 px-5 py-4">
                            <div>
                                <p class="text-sm font-semibold text-[#01225E]">{{ $bursary->company_name ?? 'Bursary provider' }}</p>
                                <h2 class="mt-1 text-2xl font-bold">Apply with Chamu</h2>
                                <p class="mt-1 text-sm font-semibold text-neutral-500">Email submission to {{ $providerEmail }}</p>
                            </div>
                            <button type="button" data-close-apply-modal class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-300 hover:bg-neutral-50" aria-label="Close application form">
                                <i data-lucide="x" style="width:18px;height:18px;"></i>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('bursaries.apply', $bursary->id) }}" enctype="multipart/form-data" class="px-5 py-5">
                            @csrf

                            @error('application')
                                <p class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</p>
                            @enderror

                            <section class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="applicant_phone" class="block text-sm font-semibold mb-2">Phone</label>
                                    <input id="applicant_phone" name="applicant_phone" value="{{ old('applicant_phone') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                    @error('applicant_phone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="study_level" class="block text-sm font-semibold mb-2">Study level</label>
                                    <select id="study_level" name="study_level" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                        <option value="">Choose level</option>
                                        <option value="Pupil (High School)" @selected(old('study_level') === 'Pupil (High School)')>Pupil (High School)</option>
                                        <option value="Student (University/College)" @selected(old('study_level') === 'Student (University/College)')>Student (University/College)</option>
                                        <option value="Other" @selected(old('study_level') === 'Other')>Other</option>
                                    </select>
                                    @error('study_level') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="institution" class="block text-sm font-semibold mb-2">Institution</label>
                                    <input id="institution" name="institution" value="{{ old('institution') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                    @error('institution') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="qualification" class="block text-sm font-semibold mb-2">Qualification or field</label>
                                    <input id="qualification" name="qualification" value="{{ old('qualification') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                    @error('qualification') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="current_year" class="block text-sm font-semibold mb-2">Current year</label>
                                    <input id="current_year" name="current_year" value="{{ old('current_year') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                    @error('current_year') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="household_income" class="block text-sm font-semibold mb-2">Household income context</label>
                                    <input id="household_income" name="household_income" value="{{ old('household_income') }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                                    @error('household_income') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="funding_need" class="block text-sm font-semibold mb-2">Funding need</label>
                                    <textarea id="funding_need" name="funding_need" rows="3" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">{{ old('funding_need') }}</textarea>
                                    @error('funding_need') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </section>

                            <section class="mt-6 rounded-2xl border border-neutral-200 bg-neutral-50 p-4">
                                <h3 class="text-base font-bold">Applicant circumstances</h3>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                    <label class="flex items-start gap-2 rounded-xl bg-white p-3 text-sm font-semibold">
                                        <input type="checkbox" name="sassa_recipient" value="1" @checked(old('sassa_recipient')) class="mt-1">
                                        SASSA grant recipient
                                    </label>
                                    <label class="flex items-start gap-2 rounded-xl bg-white p-3 text-sm font-semibold">
                                        <input type="checkbox" name="special_circumstances[]" value="disability" @checked(in_array('disability', old('special_circumstances', []), true)) class="mt-1">
                                        Disability
                                    </label>
                                    <label class="flex items-start gap-2 rounded-xl bg-white p-3 text-sm font-semibold">
                                        <input type="checkbox" name="special_circumstances[]" value="vulnerable_child" @checked(in_array('vulnerable_child', old('special_circumstances', []), true)) class="mt-1">
                                        Vulnerable child
                                    </label>
                                </div>
                            </section>

                            <section class="mt-6">
                                <div class="flex flex-col gap-1">
                                    <h3 class="text-base font-bold">Documents</h3>
                                    <p class="text-sm font-semibold text-neutral-500">Upload certified copies where the bursary asks for certified copies.</p>
                                </div>
                                @error('documents.academic_record') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror

                                <div class="mt-4 grid gap-4">
                                    @foreach ($documentRequirements as $document)
                                        <div class="rounded-2xl border border-neutral-200 p-4">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <label for="document_{{ $document->key }}" class="font-bold">{{ $document->label }}</label>
                                                @if ($document->is_required)
                                                    <span class="rounded-full bg-[#01225E] px-2 py-0.5 text-[11px] font-bold uppercase text-white">Required</span>
                                                @elseif (in_array($document->key, $academicDocumentKeys, true))
                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold uppercase text-amber-800">One academic file required</span>
                                                @endif
                                            </div>
                                            @if ($document->description)
                                                <p class="mt-1 text-sm font-semibold text-neutral-500">{{ $document->description }}</p>
                                            @endif
                                            <input
                                                id="document_{{ $document->key }}"
                                                name="documents[{{ $document->key }}][]"
                                                type="file"
                                                @if ($document->accepts_multiple) multiple @endif
                                                class="mt-3 w-full rounded-xl border border-neutral-300 px-4 py-3 text-sm font-semibold file:mr-4 file:rounded-lg file:border-0 file:bg-[#01225E] file:px-3 file:py-2 file:font-bold file:text-white"
                                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                            >
                                            @error('documents.'.$document->key) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                            @error('documents.'.$document->key.'.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </section>

                            <label class="mt-6 flex items-start gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 text-sm font-semibold text-neutral-700">
                                <input type="checkbox" name="consent" value="1" required @checked(old('consent')) class="mt-1">
                                I confirm that Chamu may email this application and attached documents on my behalf.
                            </label>
                            @error('consent') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" data-close-apply-modal class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">Cancel</button>
                                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white hover:bg-[#001A48]">
                                    Send application <i data-lucide="send" style="width:18px;height:18px;"></i>
                                </button>
                            </div>
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

        document.querySelectorAll('[data-open-apply-modal]').forEach((button) => button.addEventListener('click', openApplyModal));
        document.querySelectorAll('[data-close-apply-modal]').forEach((button) => button.addEventListener('click', closeApplyModal));
        applyModal?.addEventListener('click', (event) => {
            if (event.target === applyModal) closeApplyModal();
        });

        if (@json($errors->any())) {
            openApplyModal();
        }
    </script>
@endpush

@extends('layouts.app')

@section('title', 'Application Profile · Chamu')

@section('content')
    @php
        $academicKeys = $documentDefinitions->where('requirement_group', 'academic_record')->pluck('key')->all();
        $hasId = $savedDocuments->get('id_document', collect())->isNotEmpty();
        $hasCv = $savedDocuments->get('curriculum_vitae', collect())->isNotEmpty();
        $hasAcademic = collect($academicKeys)->contains(fn ($key) => $savedDocuments->get($key, collect())->isNotEmpty());
        $readyCount = collect([$hasId, $hasCv, $hasAcademic])->filter()->count();
    @endphp

    <main class="mx-auto max-w-6xl px-5 py-10 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">Funding setup</p>
                <h1 class="mt-1 text-3xl font-bold">Application profile</h1>
                <p class="mt-2 max-w-2xl text-neutral-500">Save the details and documents Chamu reuses when you apply for bursaries.</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                <i data-lucide="user-cog" style="width:16px;height:16px;"></i>
                Profile
            </a>
        </div>

        @if (session('status'))
            <p class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                Please check the highlighted application fields and documents below.
            </div>
        @endif

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-neutral-500">Required readiness</p>
                <p class="mt-2 text-3xl font-black">{{ $readyCount }}/3</p>
            </div>
            <div class="rounded-2xl border {{ $hasId ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} p-4">
                <p class="text-xs font-black uppercase tracking-[0.12em] {{ $hasId ? 'text-emerald-700' : 'text-red-700' }}">ID document</p>
                <p class="mt-2 font-black">{{ $hasId ? 'Saved' : 'Required' }}</p>
            </div>
            <div class="rounded-2xl border {{ $hasCv && $hasAcademic ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-4">
                <p class="text-xs font-black uppercase tracking-[0.12em] {{ $hasCv && $hasAcademic ? 'text-emerald-700' : 'text-amber-700' }}">CV and academics</p>
                <p class="mt-2 font-black">{{ $hasCv && $hasAcademic ? 'Ready' : 'Still needed' }}</p>
            </div>
        </section>

        <form method="POST" action="{{ route('profile.application.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
                <h2 class="text-xl font-bold">Application details</h2>
                <p class="mt-1 text-sm text-neutral-500">These values prefill Apply with Chamu so you only review before sending.</p>

                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="applicant_phone" class="mb-2 block text-sm font-semibold">Phone</label>
                        <input id="applicant_phone" name="applicant_phone" value="{{ old('applicant_phone', $applicationProfile->applicant_phone) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('applicant_phone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="study_level" class="mb-2 block text-sm font-semibold">Study level</label>
                        <select id="study_level" name="study_level" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                            <option value="">Choose level</option>
                            <option value="Pupil (High School)" @selected(old('study_level', $applicationProfile->study_level) === 'Pupil (High School)')>Pupil (High School)</option>
                            <option value="Student (University/College)" @selected(old('study_level', $applicationProfile->study_level) === 'Student (University/College)')>Student (University/College)</option>
                            <option value="Other" @selected(old('study_level', $applicationProfile->study_level) === 'Other')>Other</option>
                        </select>
                        @error('study_level') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="institution" class="mb-2 block text-sm font-semibold">Institution</label>
                        <input id="institution" name="institution" value="{{ old('institution', $applicationProfile->institution) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('institution') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="qualification" class="mb-2 block text-sm font-semibold">Qualification or field</label>
                        <input id="qualification" name="qualification" value="{{ old('qualification', $applicationProfile->qualification) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('qualification') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="current_year" class="mb-2 block text-sm font-semibold">Current year</label>
                        <input id="current_year" name="current_year" value="{{ old('current_year', $applicationProfile->current_year) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('current_year') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="household_income" class="mb-2 block text-sm font-semibold">Household income context</label>
                        <input id="household_income" name="household_income" value="{{ old('household_income', $applicationProfile->household_income) }}" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('household_income') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="applicant_postal_address" class="mb-2 block text-sm font-semibold">Postal or return address</label>
                        <textarea id="applicant_postal_address" name="applicant_postal_address" rows="3" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">{{ old('applicant_postal_address', $applicationProfile->applicant_postal_address) }}</textarea>
                        <p class="mt-2 text-xs font-semibold text-neutral-500">Used when a bursary has postal or hand-delivery submission instructions.</p>
                        @error('applicant_postal_address') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="funding_need" class="mb-2 block text-sm font-semibold">Funding need</label>
                        <textarea id="funding_need" name="funding_need" rows="3" class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">{{ old('funding_need', $applicationProfile->funding_need) }}</textarea>
                        @error('funding_need') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <label class="flex items-start gap-2 rounded-xl border border-neutral-200 p-3 text-sm font-semibold">
                        <input type="checkbox" name="sassa_recipient" value="1" @checked(old('sassa_recipient', $applicationProfile->sassa_recipient)) class="mt-1">
                        SASSA grant recipient
                    </label>
                    <label class="flex items-start gap-2 rounded-xl border border-neutral-200 p-3 text-sm font-semibold">
                        <input type="checkbox" name="special_circumstances[]" value="disability" @checked(in_array('disability', old('special_circumstances', $applicationProfile->special_circumstances ?? []), true)) class="mt-1">
                        Disability
                    </label>
                    <label class="flex items-start gap-2 rounded-xl border border-neutral-200 p-3 text-sm font-semibold">
                        <input type="checkbox" name="special_circumstances[]" value="vulnerable_child" @checked(in_array('vulnerable_child', old('special_circumstances', $applicationProfile->special_circumstances ?? []), true)) class="mt-1">
                        Vulnerable child
                    </label>
                </div>
            </section>

            <section class="rounded-2xl border border-neutral-200 bg-white p-6 soft-card">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Reusable documents</h2>
                        <p class="mt-1 text-sm text-neutral-500">ID document, CV, and one academic document are required before this profile can be saved.</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full bg-[#01225E] px-3 py-1 text-xs font-black text-white">PDF, JPG, PNG, DOC, DOCX</span>
                </div>

                @error('documents.academic_record') <p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @foreach ($documentDefinitions as $document)
                        @php
                            $storedForKey = $savedDocuments->get($document->key, collect());
                            $isAcademic = in_array($document->key, $academicKeys, true);
                        @endphp
                        <div class="rounded-2xl border border-neutral-200 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <label for="document_{{ $document->key }}" class="font-black">{{ $document->label }}</label>
                                @if ($document->is_required)
                                    <span class="rounded-full bg-[#01225E] px-2 py-0.5 text-[11px] font-black uppercase text-white">Required</span>
                                @elseif ($isAcademic)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-black uppercase text-amber-800">Academic option</span>
                                @endif
                            </div>

                            @if ($document->description)
                                <p class="mt-2 text-xs font-semibold leading-5 text-neutral-500">{{ $document->description }}</p>
                            @endif

                            @if ($storedForKey->isNotEmpty())
                                <div class="mt-3 space-y-2">
                                    @foreach ($storedForKey as $storedDocument)
                                        <div class="flex items-center justify-between gap-3 rounded-xl bg-neutral-50 px-3 py-2 text-sm font-semibold">
                                            <span class="min-w-0 truncate">{{ $storedDocument->original_name }}</span>
                                            <span class="shrink-0 text-xs text-neutral-500">{{ number_format(($storedDocument->size ?? 0) / 1024, 1) }} KB</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-3 rounded-xl bg-neutral-50 px-3 py-2 text-sm font-semibold text-neutral-500">No saved file yet.</p>
                            @endif

                            <input
                                id="document_{{ $document->key }}"
                                name="documents[{{ $document->key }}][]"
                                type="file"
                                @if ($document->accepts_multiple) multiple @endif
                                class="mt-3 w-full rounded-xl border border-neutral-300 px-4 py-3 text-sm font-semibold file:mr-4 file:rounded-lg file:border-0 file:bg-[#01225E] file:px-3 file:py-2 file:font-bold file:text-white"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            >
                            @if (! $document->accepts_multiple && $storedForKey->isNotEmpty())
                                <p class="mt-2 text-xs font-semibold text-neutral-500">Uploading a new file replaces the saved one.</p>
                            @endif
                            @error('documents.'.$document->key) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('documents.'.$document->key.'.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-xl border border-neutral-300 px-5 py-3 font-semibold hover:bg-neutral-50">Cancel</a>
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white hover:bg-[#001A48]">
                    Save application profile <i data-lucide="save" style="width:18px;height:18px;"></i>
                </button>
            </div>
        </form>
    </main>
@endsection

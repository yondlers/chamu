@extends('layouts.app')

@section('title', 'Postal pack · Chamu')

@section('content')
    @php
        $submittedAt = $application->submitted_at ?? $application->created_at;
    @endphp

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            main {
                padding: 0 !important;
            }

            .print-sheet {
                border: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>

    <main class="bg-[#f5f7fb] px-5 py-8 text-neutral-950 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <div class="no-print mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('applications.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-neutral-600 hover:text-neutral-950">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                    Back to applications
                </a>
                <div class="flex flex-col gap-2 sm:flex-row">
                    @if ($application->source_url)
                        <a href="{{ $application->source_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm font-black text-neutral-800 hover:bg-neutral-50">
                            Source instructions <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                        </a>
                    @endif
                    <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-black text-white hover:bg-[#001A48]">
                        Print postal pack <i data-lucide="printer" style="width:16px;height:16px;"></i>
                    </button>
                </div>
            </div>

            <article class="print-sheet rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-4 border-b border-neutral-200 pb-6 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-[#01225E]">Chamu postal application pack</p>
                        <h1 class="mt-2 text-3xl font-black leading-tight">{{ $application->bursary_title }}</h1>
                        <p class="mt-1 text-base font-bold text-neutral-600">{{ $application->company_name ?? 'Bursary provider' }}</p>
                    </div>
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm font-bold text-neutral-700">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-neutral-500">Prepared</p>
                        <p class="mt-1">{{ $submittedAt ? \Illuminate\Support\Carbon::parse($submittedAt)->format('d M Y') : 'Not dated' }}</p>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-6 text-amber-950">
                    <p class="font-black">This bursary requires postal or hand-delivery submission.</p>
                    <p class="mt-2">Chamu has not emailed this bursary provider. Print this pack, attach the listed documents behind it, sign any required forms, and submit everything to the address below.</p>
                </div>

                <section class="mt-6 grid gap-5 md:grid-cols-2">
                    <div class="rounded-xl border border-neutral-200 p-4">
                        <h2 class="text-sm font-black uppercase tracking-[0.14em] text-neutral-500">Provider destination</h2>
                        <p class="mt-3 whitespace-pre-line text-sm font-semibold leading-6 text-neutral-800">{{ $application->provider_postal_address ?: 'Use the provider postal or hand-delivery address shown in the source instructions.' }}</p>
                        @if ($application->source_url)
                            <p class="mt-3 break-all text-xs font-semibold text-neutral-500">Source: {{ $application->source_url }}</p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-neutral-200 p-4">
                        <h2 class="text-sm font-black uppercase tracking-[0.14em] text-neutral-500">Applicant return address</h2>
                        <p class="mt-3 whitespace-pre-line text-sm font-semibold leading-6 text-neutral-800">{{ $application->applicant_postal_address ?: 'No return address was captured.' }}</p>
                    </div>
                </section>

                <section class="mt-6 rounded-xl border border-neutral-200 p-4">
                    <h2 class="text-sm font-black uppercase tracking-[0.14em] text-neutral-500">Applicant details</h2>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-black text-neutral-500">Name</dt>
                            <dd class="mt-1 font-semibold text-neutral-900">{{ $application->applicant_name }}</dd>
                        </div>
                        <div>
                            <dt class="font-black text-neutral-500">Email</dt>
                            <dd class="mt-1 font-semibold text-neutral-900">{{ $application->applicant_email }}</dd>
                        </div>
                        <div>
                            <dt class="font-black text-neutral-500">Phone</dt>
                            <dd class="mt-1 font-semibold text-neutral-900">{{ $application->applicant_phone ?: 'Not added' }}</dd>
                        </div>
                        <div>
                            <dt class="font-black text-neutral-500">Study level</dt>
                            <dd class="mt-1 font-semibold text-neutral-900">{{ $application->study_level ?: 'Not added' }}</dd>
                        </div>
                        <div>
                            <dt class="font-black text-neutral-500">Institution</dt>
                            <dd class="mt-1 font-semibold text-neutral-900">{{ $application->institution ?: 'Not added' }}</dd>
                        </div>
                        <div>
                            <dt class="font-black text-neutral-500">Qualification</dt>
                            <dd class="mt-1 font-semibold text-neutral-900">{{ $application->qualification ?: 'Not added' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="mt-6 rounded-xl border border-neutral-200 p-4">
                    <h2 class="text-sm font-black uppercase tracking-[0.14em] text-neutral-500">Documents to attach</h2>
                    <ul class="mt-4 space-y-3 text-sm font-semibold text-neutral-800">
                        @forelse ($documents as $document)
                            <li class="flex items-start gap-3 rounded-xl border border-neutral-200 px-3 py-2">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded border border-neutral-400"></span>
                                <span>
                                    <span class="font-black">{{ $document->requirement_label ?? \Illuminate\Support\Str::of($document->document_key)->replace('_', ' ')->title() }}</span>
                                    <span class="block text-neutral-500">{{ $document->original_name }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="rounded-xl border border-neutral-200 px-3 py-2 text-neutral-500">No documents were saved with this pack.</li>
                        @endforelse
                    </ul>
                </section>

                <section class="mt-6 rounded-xl border border-neutral-200 p-4">
                    <h2 class="text-sm font-black uppercase tracking-[0.14em] text-neutral-500">Final checks before posting</h2>
                    <ul class="mt-4 space-y-3 text-sm font-semibold text-neutral-800">
                        <li class="flex gap-3"><span class="mt-0.5 h-5 w-5 shrink-0 rounded border border-neutral-400"></span>Every required document is attached behind this cover sheet.</li>
                        <li class="flex gap-3"><span class="mt-0.5 h-5 w-5 shrink-0 rounded border border-neutral-400"></span>Certified copies are included where the bursary asks for certified copies.</li>
                        <li class="flex gap-3"><span class="mt-0.5 h-5 w-5 shrink-0 rounded border border-neutral-400"></span>Any official provider form is completed and signed.</li>
                        <li class="flex gap-3"><span class="mt-0.5 h-5 w-5 shrink-0 rounded border border-neutral-400"></span>The pack is posted or hand-delivered before the closing date.</li>
                    </ul>
                </section>
            </article>
        </div>
    </main>
@endsection

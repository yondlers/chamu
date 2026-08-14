@extends('layouts.app')

@section('title', 'Reports · Chamu')

@section('content')
    <main class="mx-auto max-w-6xl px-5 py-8 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-bold text-[#01225E]">Reports</p>
                <h1 class="mt-1 text-3xl font-bold">Personal reports</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">Pull a Course Matcher PDF from your saved marks or a bursary PDF from your saved application profile.</p>
            </div>
            <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-bold hover:bg-neutral-50">
                Dashboard <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="grid gap-5 lg:grid-cols-2">
            <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#01225E] text-white">
                        <i data-lucide="target" style="width:20px;height:20px;"></i>
                    </span>
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-black',
                        'bg-emerald-50 text-emerald-700' => $courseMatch['has_marks'],
                        'bg-amber-50 text-amber-700' => ! $courseMatch['has_marks'],
                    ])>
                        {{ $courseMatch['has_marks'] ? 'Ready' : 'Marks needed' }}
                    </span>
                </div>
                <h2 class="mt-4 text-xl font-bold">Course Matcher report</h2>
                <p class="mt-2 text-sm leading-6 text-neutral-500">
                    {{ $courseMatch['has_marks'] ? 'Uses '.$courseMatch['term']->label.' and includes your grade graph, AI review, matched qualifications, university logos, and course links.' : 'Upload marks before pulling a personalised course report.' }}
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-neutral-50 p-3">
                        <p class="text-xs font-black uppercase text-neutral-500">Matches</p>
                        <p class="mt-1 text-2xl font-black">{{ number_format((int) $courseMatch['qualified_count']) }}</p>
                    </div>
                    <div class="rounded-xl bg-neutral-50 p-3">
                        <p class="text-xs font-black uppercase text-neutral-500">APS</p>
                        <p class="mt-1 text-2xl font-black">{{ number_format((int) $courseMatch['aps_total']) }}</p>
                    </div>
                    <div class="rounded-xl bg-neutral-50 p-3">
                        <p class="text-xs font-black uppercase text-neutral-500">Average</p>
                        <p class="mt-1 text-2xl font-black">{{ $courseMatch['average_mark'] === null ? 'N/A' : number_format((float) $courseMatch['average_mark'], 1).'%' }}</p>
                    </div>
                </div>

                @if ($courseReview)
                    <p class="mt-4 rounded-xl bg-blue-50 px-4 py-3 text-sm font-semibold leading-6 text-[#01225E]">{{ $courseReview }}</p>
                @endif

                <div class="mt-5 flex flex-wrap gap-2">
                    @if ($courseMatch['has_marks'])
                        <a href="{{ route('reports.course') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                            Download PDF <i data-lucide="download" style="width:16px;height:16px;"></i>
                        </a>
                    @else
                        <a href="{{ route('subjects.index', ['manage' => 1]) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                            Upload marks <i data-lucide="line-chart" style="width:16px;height:16px;"></i>
                        </a>
                    @endif
                </div>
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-600 text-white">
                        <i data-lucide="badge-dollar-sign" style="width:20px;height:20px;"></i>
                    </span>
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-black',
                        'bg-emerald-50 text-emerald-700' => $bursaryReadiness['ready'],
                        'bg-amber-50 text-amber-700' => ! $bursaryReadiness['ready'],
                    ])>
                        {{ $bursaryReadiness['ready'] ? 'Ready' : 'Profile needed' }}
                    </span>
                </div>
                <h2 class="mt-4 text-xl font-bold">Bursary report</h2>
                <p class="mt-2 text-sm leading-6 text-neutral-500">
                    {{ $bursaryReadiness['ready'] ? 'Includes '.number_format($openBursaryCount).' currently open bursaries with provider, field, coverage, closing date, and Chamu links.' : 'Complete your application profile and required documents before pulling a bursary report.' }}
                </p>

                @unless ($bursaryReadiness['ready'])
                    <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3">
                        <p class="text-sm font-black text-amber-800">Still needed</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($bursaryReadiness['missing'] as $missing)
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-amber-800">{{ $missing }}</span>
                            @endforeach
                        </div>
                    </div>
                @endunless

                <div class="mt-5 flex flex-wrap gap-2">
                    @if ($bursaryReadiness['ready'])
                        <a href="{{ route('reports.bursaries') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                            Download PDF <i data-lucide="download" style="width:16px;height:16px;"></i>
                        </a>
                    @else
                        <a href="{{ route('profile.application') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#001A48]">
                            Complete profile <i data-lucide="folder-check" style="width:16px;height:16px;"></i>
                        </a>
                    @endif
                </div>
            </article>
        </section>
    </main>
@endsection

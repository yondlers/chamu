@extends('layouts.app')

@section('title', 'About Chamu')

@push('head')
    <x-seo-meta
        title="About Chamu"
        description="Chamu helps South African learners compare APS requirements, explore bursaries, and prepare for study decisions with clearer information."
        :canonical="route('about')"
    />
@endpush

@section('content')
    <main class="bg-[#f8fafc]">
        <section class="border-b border-neutral-200 bg-white">
            <div class="mx-auto max-w-5xl px-5 py-12 lg:px-8">
                <p class="text-sm font-bold uppercase text-[#01225E]">About</p>
                <h1 class="mt-3 max-w-3xl text-4xl font-black leading-tight text-neutral-950 sm:text-5xl">Chamu makes study decisions easier to compare.</h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-neutral-600">Learners often have to move between prospectuses, bursary PDFs, marks, deadlines, and advice from different people. Chamu brings the key pieces into one public planning space so a learner can ask better questions before applying.</p>
            </div>
        </section>

        <section class="mx-auto grid max-w-5xl gap-6 px-5 py-10 lg:px-8">
            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">What Chamu Helps With</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-[#01225E]">
                            <i data-lucide="target" style="width:22px;height:22px;"></i>
                        </span>
                        <h3 class="mt-3 font-bold text-neutral-950">APS planning</h3>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">Search captured South African programmes and compare APS with subject requirements.</p>
                    </div>
                    <div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                            <i data-lucide="book-open" style="width:22px;height:22px;"></i>
                        </span>
                        <h3 class="mt-3 font-bold text-neutral-950">Learning support</h3>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">Explore practice content, subjects, papers, and study guides built for repeated use.</p>
                    </div>
                    <div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                            <i data-lucide="badge-dollar-sign" style="width:22px;height:22px;"></i>
                        </span>
                        <h3 class="mt-3 font-bold text-neutral-950">Funding discovery</h3>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">Browse bursaries, closing dates, requirements, and application links in one place.</p>
                    </div>
                </div>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">How We Think About Accuracy</h2>
                <p class="mt-3 leading-7 text-neutral-600">Chamu is a planning tool, not an official university or funding provider. We aim to make requirements easier to understand, but learners should always confirm final rules, dates, documents, and selection conditions on the official university or funder website before applying.</p>
                <p class="mt-3 leading-7 text-neutral-600">When information appears incomplete or outdated, we prefer to mark it clearly and improve it rather than pretend every answer is final.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Useful Starting Points</h2>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('aps.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 text-sm font-bold text-white">
                        Check APS <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                    <a href="{{ route('bursaries.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white px-5 py-3 text-sm font-bold text-neutral-950">
                        Browse bursaries <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                    <a href="{{ route('guides.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white px-5 py-3 text-sm font-bold text-neutral-950">
                        Read guides <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                </div>
            </article>
        </section>
    </main>
@endsection

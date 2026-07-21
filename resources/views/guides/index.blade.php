@extends('layouts.app')

@section('title', 'Study and Application Guides - Chamu')

@push('head')
    <x-seo-meta
        title="Study and Application Guides - Chamu"
        description="Read Chamu guides on APS scores, subject requirements, bursary applications, NSFAS, and university planning."
        :canonical="route('guides.index')"
    />
@endpush

@section('content')
    <main class="bg-[#f8fafc] pb-14">
        <section class="border-b border-neutral-200 bg-white">
            <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
                <p class="text-sm font-bold uppercase text-[#01225E]">Guides</p>
                <h1 class="mt-3 max-w-4xl text-4xl font-black leading-tight text-neutral-950 sm:text-5xl">Practical study, APS, and funding guides.</h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-neutral-600">Short explanations for South African learners who want to understand course requirements, marks, funding documents, and application choices before they commit.</p>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-5 py-10 lg:px-8">
            @include('partials.adsense-home-placement', ['class' => 'mb-8'])

            <div class="grid gap-5 md:grid-cols-2">
                @foreach ($guides as $guide)
                    <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-[#01225E]">
                                <i data-lucide="file-text" style="width:22px;height:22px;"></i>
                            </span>
                            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-600">{{ $guide->minutes }} min read</span>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-neutral-950">{{ $guide->title }}</h2>
                        <p class="mt-3 leading-7 text-neutral-600">{{ $guide->summary }}</p>
                        <a href="{{ route('guides.show', ['guide' => $guide->slug]) }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 text-sm font-bold text-white">
                            Read guide <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    </main>
@endsection

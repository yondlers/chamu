@extends('layouts.app')

@section('title', $guide->title.' - Chamu')

@php
    $canonical = route('guides.show', ['guide' => $guide->slug]);
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $guide->title,
        'description' => $guide->description,
        'mainEntityOfPage' => $canonical,
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Chamu',
            'url' => url('/'),
        ],
    ];
@endphp

@push('head')
    <x-seo-meta
        :title="$guide->title.' - Chamu'"
        :description="$guide->description"
        :canonical="$canonical"
        :json-ld="$jsonLd"
    />
@endpush

@section('content')
    <main class="bg-[#f8fafc] pb-14">
        <article>
            <header class="border-b border-neutral-200 bg-white">
                <div class="mx-auto max-w-4xl px-5 py-12 lg:px-8">
                    <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-500">
                        <a href="{{ route('guides.index') }}" class="hover:text-neutral-950">Guides</a>
                        <i data-lucide="chevron-right" style="width:14px;height:14px;"></i>
                        <span aria-current="page" class="text-neutral-950">{{ $guide->title }}</span>
                    </nav>
                    <p class="mt-6 text-sm font-bold uppercase text-[#01225E]">{{ $guide->minutes }} min read</p>
                    <h1 class="mt-3 text-4xl font-black leading-tight text-neutral-950 sm:text-5xl">{{ $guide->title }}</h1>
                    <p class="mt-5 text-lg leading-8 text-neutral-600">{{ $guide->summary }}</p>
                </div>
            </header>

            <div class="mx-auto max-w-4xl px-5 py-10 lg:px-8">
                @include('partials.adsense-home-placement', ['class' => 'mb-8'])

                <div class="grid gap-5">
                    @foreach ($guide->sections as $section)
                        <section class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                            <h2 class="text-2xl font-bold text-neutral-950">{{ $section['heading'] }}</h2>
                            @foreach ($section['body'] as $paragraph)
                                <p class="mt-3 leading-7 text-neutral-600">{{ $paragraph }}</p>
                            @endforeach
                        </section>
                    @endforeach

                    <section class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                        <h2 class="text-2xl font-bold text-neutral-950">Quick Checklist</h2>
                        <ul class="mt-4 grid gap-3 leading-7 text-neutral-600">
                            @foreach ($guide->checklist as $item)
                                <li class="flex gap-3">
                                    <i data-lucide="check-circle-2" class="mt-1 shrink-0 text-emerald-700" style="width:18px;height:18px;"></i>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                </div>
            </div>
        </article>

        @if ($relatedGuides->isNotEmpty())
            <section class="mx-auto max-w-4xl px-5 lg:px-8" aria-labelledby="related-guides-heading">
                <h2 id="related-guides-heading" class="text-2xl font-bold text-neutral-950">Read Next</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach ($relatedGuides as $relatedGuide)
                        <a href="{{ route('guides.show', ['guide' => $relatedGuide->slug]) }}" class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm hover:border-neutral-300">
                            <span class="text-xs font-bold uppercase text-[#01225E]">{{ $relatedGuide->minutes }} min read</span>
                            <h3 class="mt-2 font-bold text-neutral-950">{{ $relatedGuide->title }}</h3>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
@endsection

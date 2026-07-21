@extends('layouts.app')

@section('title', 'Terms - Chamu')

@push('head')
    <x-seo-meta
        title="Terms - Chamu"
        description="Read the basic terms for using Chamu's APS, bursary, learning, and application planning tools."
        :canonical="route('terms')"
    />
@endpush

@section('content')
    <main class="bg-[#f8fafc]">
        <section class="border-b border-neutral-200 bg-white">
            <div class="mx-auto max-w-4xl px-5 py-12 lg:px-8">
                <p class="text-sm font-bold uppercase text-[#01225E]">Terms</p>
                <h1 class="mt-3 text-4xl font-black leading-tight text-neutral-950 sm:text-5xl">Terms for Using Chamu</h1>
                <p class="mt-5 text-lg leading-8 text-neutral-600">These terms describe how visitors and account holders should use Chamu's public information, learner tools, and application planning features.</p>
                <p class="mt-3 text-sm font-semibold text-neutral-500">Last updated: 21 July 2026</p>
            </div>
        </section>

        <section class="mx-auto grid max-w-4xl gap-5 px-5 py-10 lg:px-8">
            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Educational Planning Only</h2>
                <p class="mt-3 leading-7 text-neutral-600">Chamu helps learners compare information, but it is not an official university, school, bursary provider, or admissions authority. Always confirm requirements, closing dates, fees, documents, and selection rules with the official institution or funder.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Account Responsibilities</h2>
                <p class="mt-3 leading-7 text-neutral-600">Users are responsible for keeping login details private, entering accurate information, reviewing generated outputs, and checking all application details before submission.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Content Accuracy</h2>
                <p class="mt-3 leading-7 text-neutral-600">We work to keep Chamu useful and current, but public information can change. If you find an error, please send the page URL and official correction source through the contact page.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Acceptable Use</h2>
                <p class="mt-3 leading-7 text-neutral-600">Do not misuse Chamu, attempt to access another account, upload harmful files, submit false information, scrape the service aggressively, or interfere with site security and availability.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Advertising and External Links</h2>
                <p class="mt-3 leading-7 text-neutral-600">Chamu may display advertising and link to external websites. External websites have their own terms, privacy policies, and accuracy standards. A link does not mean Chamu controls or guarantees that external content.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Contact</h2>
                <p class="mt-3 leading-7 text-neutral-600">For questions about these terms, contact <a href="mailto:support@chamu.co.za" class="font-semibold text-[#01225E] underline">support@chamu.co.za</a>.</p>
            </article>
        </section>
    </main>
@endsection

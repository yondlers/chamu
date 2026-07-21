@extends('layouts.app')

@section('title', 'Contact Chamu')

@push('head')
    <x-seo-meta
        title="Contact Chamu"
        description="Contact Chamu for corrections, content updates, bursary information, university requirement updates, and support questions."
        :canonical="route('contact')"
    />
@endpush

@section('content')
    <main class="bg-[#f8fafc]">
        <section class="border-b border-neutral-200 bg-white">
            <div class="mx-auto max-w-5xl px-5 py-12 lg:px-8">
                <p class="text-sm font-bold uppercase text-[#01225E]">Contact</p>
                <h1 class="mt-3 max-w-3xl text-4xl font-black leading-tight text-neutral-950 sm:text-5xl">Help us keep Chamu useful and accurate.</h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-neutral-600">Send corrections, missing bursaries, university requirement updates, or account support questions to the Chamu team.</p>
            </div>
        </section>

        <section class="mx-auto grid max-w-5xl gap-6 px-5 py-10 lg:grid-cols-[1fr_320px] lg:px-8">
            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Email</h2>
                <p class="mt-3 leading-7 text-neutral-600">For support, corrections, and partnership questions, email:</p>
                <a href="mailto:support@chamu.co.za" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 text-sm font-bold text-white">
                    support@chamu.co.za <i data-lucide="mail" style="width:16px;height:16px;"></i>
                </a>

                <div class="mt-8 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                        <h3 class="font-bold text-neutral-950">Corrections</h3>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">Send the page URL, the corrected requirement, and the official source link if you have it.</p>
                    </div>
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                        <h3 class="font-bold text-neutral-950">Bursaries</h3>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">Share the funder name, closing date, eligibility rules, and official application link.</p>
                    </div>
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                        <h3 class="font-bold text-neutral-950">Learner Support</h3>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">Include the page you were using, your browser, and what you expected to happen.</p>
                    </div>
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                        <h3 class="font-bold text-neutral-950">Policy Reports</h3>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">Report content that looks misleading, unsafe, inappropriate, or incorrectly attributed.</p>
                    </div>
                </div>
            </article>

            <aside class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-neutral-950">Before You Write</h2>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-neutral-600">
                    <li class="flex gap-2"><i data-lucide="check" class="mt-0.5 shrink-0 text-emerald-700" style="width:16px;height:16px;"></i>Include the exact page URL.</li>
                    <li class="flex gap-2"><i data-lucide="check" class="mt-0.5 shrink-0 text-emerald-700" style="width:16px;height:16px;"></i>Use official sources for requirement changes.</li>
                    <li class="flex gap-2"><i data-lucide="check" class="mt-0.5 shrink-0 text-emerald-700" style="width:16px;height:16px;"></i>Never send passwords or full banking details.</li>
                </ul>
            </aside>
        </section>
    </main>
@endsection

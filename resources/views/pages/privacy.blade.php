@extends('layouts.app')

@section('title', 'Privacy Policy - Chamu')

@push('head')
    <x-seo-meta
        title="Privacy Policy - Chamu"
        description="Read how Chamu handles account information, cookies, advertising partners, analytics, and privacy choices."
        :canonical="route('privacy')"
    />
@endpush

@section('content')
    <main class="bg-[#f8fafc]">
        <section class="border-b border-neutral-200 bg-white">
            <div class="mx-auto max-w-4xl px-5 py-12 lg:px-8">
                <p class="text-sm font-bold uppercase text-[#01225E]">Privacy Policy</p>
                <h1 class="mt-3 text-4xl font-black leading-tight text-neutral-950 sm:text-5xl">How Chamu Handles Privacy</h1>
                <p class="mt-5 text-lg leading-8 text-neutral-600">This policy explains the main types of information Chamu may collect, how the site uses cookies and advertising partners, and the choices visitors have.</p>
                <p class="mt-3 text-sm font-semibold text-neutral-500">Last updated: 21 July 2026</p>
            </div>
        </section>

        <section class="mx-auto grid max-w-4xl gap-5 px-5 py-10 lg:px-8">
            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Information We Collect</h2>
                <p class="mt-3 leading-7 text-neutral-600">Chamu may collect account details such as name, email address, grade, curriculum, selected subjects, saved marks, uploaded application documents, and site activity needed to provide learning, APS, and bursary tools.</p>
                <p class="mt-3 leading-7 text-neutral-600">Visitors can also browse public pages without creating an account. Public pages may still use cookies, server logs, and security tools to keep the site available and understand broad usage patterns.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">How We Use Information</h2>
                <ul class="mt-4 grid gap-3 leading-7 text-neutral-600">
                    <li>To show saved subjects, marks, progress, applications, and study activity.</li>
                    <li>To improve APS matching, bursary discovery, site reliability, and learner experience.</li>
                    <li>To respond to support, correction, safety, and policy reports.</li>
                    <li>To protect the site against spam, abuse, invalid traffic, and security threats.</li>
                </ul>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Google Advertising Cookies</h2>
                <p class="mt-3 leading-7 text-neutral-600">Third-party vendors, including Google, use cookies to serve ads based on a visitor's prior visits to Chamu or other websites. Google's use of advertising cookies enables Google and its partners to serve ads based on visits to this site and other sites on the internet.</p>
                <p class="mt-3 leading-7 text-neutral-600">Visitors can opt out of personalised advertising through <a href="https://www.google.com/settings/ads" class="font-semibold text-[#01225E] underline" rel="noreferrer">Google Ads Settings</a>. Visitors can also learn about opting out of some third-party vendors' use of cookies for personalised advertising at <a href="https://www.aboutads.info/" class="font-semibold text-[#01225E] underline" rel="noreferrer">aboutads.info</a>.</p>
                <p class="mt-3 leading-7 text-neutral-600">Other third-party vendors or ad networks may also use cookies to serve ads if they participate in ad serving on Chamu. Their privacy practices are controlled by those vendors.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Cookies and Consent</h2>
                <p class="mt-3 leading-7 text-neutral-600">Cookies help keep users signed in, remember preferences, protect sessions, measure site performance, and support advertising. Browser settings can usually block or delete cookies, but some account features may stop working correctly without them.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Data Sharing</h2>
                <p class="mt-3 leading-7 text-neutral-600">Chamu does not sell learner account information. Information may be shared with service providers that help operate the site, comply with law, prevent abuse, or process user-requested actions such as application emails or document handling.</p>
            </article>

            <article class="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-neutral-950">Contact</h2>
                <p class="mt-3 leading-7 text-neutral-600">For privacy questions or correction requests, contact <a href="mailto:support@chamu.co.za" class="font-semibold text-[#01225E] underline">support@chamu.co.za</a>.</p>
            </article>
        </section>
    </main>
@endsection

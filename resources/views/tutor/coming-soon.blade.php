@extends('layouts.app')

@section('title', 'Tutor section coming soon · Chamu')

@section('content')
    @php
        $firstName = $user->first_name ?: Str::of($user->name)->before(' ');
        $heroImage = asset('images/tutors/hero-session-2.png');
    @endphp

    <main class="bg-[#f3f6fb] text-neutral-950">
        <section class="relative isolate min-h-[calc(100vh-4rem)] overflow-hidden bg-[#07111f] text-white">
            <div class="absolute inset-0 -z-10">
                <img src="{{ $heroImage }}" alt="" class="h-full w-full object-cover object-[center_40%] tutor-soon-image">
                <div class="absolute inset-0 bg-[linear-gradient(115deg,rgba(4,10,22,.93)_0%,rgba(4,10,22,.62)_50%,rgba(4,10,22,.28)_100%)]"></div>
            </div>

            <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-7xl items-end px-5 pb-16 pt-20 sm:pb-24 lg:px-8">
                <div class="max-w-2xl tutor-soon-copy">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-white/70">Application received</p>
                    <h1 class="mt-4 text-4xl font-black leading-[1.05] sm:text-6xl">Coming soon.</h1>
                    <p class="mt-5 max-w-xl text-base font-medium leading-7 text-white/75 sm:text-lg">
                        Thanks{{ $firstName ? ', '.$firstName : '' }}. Your tutor status is {{ $application->tutorStatus?->name ?? 'pending' }}. We will let you know when the Tutor section is ready.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('dashboard.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3.5 text-sm font-black text-[#01225E] hover:bg-white/90">
                            Back to dashboard
                        </a>
                        <a href="{{ route('bursaries.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 px-5 py-3.5 text-sm font-black text-white hover:bg-white/10">
                            Explore funding
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('styles')
    <style>
        .tutor-soon-image { animation: tutorSoonZoom 14s ease-out both; }
        .tutor-soon-copy { animation: tutorSoonFade .85s ease both; }

        @keyframes tutorSoonZoom {
            from { transform: scale(1.07); }
            to { transform: scale(1); }
        }

        @keyframes tutorSoonFade {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .tutor-soon-image,
            .tutor-soon-copy { animation: none; }
        }
    </style>
@endpush

@extends('layouts.app')

@section('title', 'Become a Tutor · Chamu')

@section('content')
    @php
        $firstName = $user->first_name ?: Str::of($user->name)->before(' ');
        $heroImage = asset('images/tutors/hero-session-1.png');
        $hasProgress = filled($application->headline) || filled($application->tutoring_bio) || $application->subjects()->exists();
    @endphp

    <main class="bg-[#f3f6fb] text-neutral-950">
        <section class="relative isolate min-h-[calc(100vh-4rem)] overflow-hidden bg-[#07111f] text-white">
            <div class="absolute inset-0 -z-10">
                <img src="{{ $heroImage }}" alt="" class="h-full w-full object-cover object-[center_35%] tutor-hero-image">
                <div class="absolute inset-0 bg-[linear-gradient(105deg,rgba(4,10,22,.94)_0%,rgba(4,10,22,.68)_46%,rgba(4,10,22,.22)_100%)]"></div>
                <div class="absolute inset-x-0 bottom-0 h-36 bg-gradient-to-t from-[#f3f6fb] via-[#f3f6fb]/55 to-transparent"></div>
            </div>

            <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-7xl flex-col justify-end px-5 pb-16 pt-16 sm:pb-20 sm:pt-20 lg:px-8">
                <div class="max-w-2xl tutor-hero-copy">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-white/70">Tutor application</p>
                    <h1 class="mt-4 text-4xl font-black leading-[1.05] sm:text-6xl">
                        Welcome{{ $firstName ? ', '.$firstName : '' }}.
                    </h1>
                    <p class="mt-5 max-w-xl text-base font-medium leading-7 text-white/75 sm:text-lg">
                        Set up your tutor profile next — photo, subjects, hourly rates, and a contact number learners can use. Province, phone, and study details from your bursary pack are reused automatically. Save anytime and continue later.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('tutor.application.show', ['step' => $application->current_step ?: 1]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3.5 text-sm font-black text-[#01225E] hover:bg-white/90">
                            {{ $hasProgress ? 'Continue application' : 'Start application' }}
                            <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                        </a>
                        <a href="{{ route('dashboard.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 px-5 py-3.5 text-sm font-black text-white hover:bg-white/10">
                            Skip for now
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('styles')
    <style>
        .tutor-hero-image {
            animation: tutorHeroZoom 12s ease-out both;
        }

        .tutor-hero-copy {
            animation: tutorHeroFade .8s ease both;
        }

        @keyframes tutorHeroZoom {
            from { transform: scale(1.08); }
            to { transform: scale(1); }
        }

        @keyframes tutorHeroFade {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .tutor-hero-image,
            .tutor-hero-copy { animation: none; }
        }
    </style>
@endpush

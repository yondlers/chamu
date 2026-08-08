@extends('layouts.app')

@section('title', 'Welcome · Subjects · Chamu')

@section('content')
    @php
        $firstName = $user->first_name ?: Str::of($user->name)->before(' ');
        $heroImage = asset('images/subjects/studying-welcome.png');
    @endphp

    <main class="bg-[#f5f7fb] text-neutral-950">
        <section class="relative isolate min-h-[calc(100vh-4rem)] overflow-hidden bg-[#07111f] text-white">
            <div class="absolute inset-0 -z-10">
                <img src="{{ $heroImage }}" alt="" class="h-full w-full object-cover object-[center_42%]">
                <div class="absolute inset-0 bg-[linear-gradient(105deg,rgba(4,10,22,.94)_0%,rgba(4,10,22,.72)_48%,rgba(4,10,22,.28)_100%)]"></div>
                <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-[#f5f7fb] via-[#f5f7fb]/50 to-transparent"></div>
            </div>

            <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-7xl flex-col justify-end px-5 pb-16 pt-16 sm:pb-20 sm:pt-20 lg:px-8">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-white/70">Pupil setup</p>
                    <h1 class="mt-4 text-4xl font-black leading-[1.05] sm:text-6xl">
                        Welcome{{ $firstName ? ', '.$firstName : '' }}.
                    </h1>
                    <p class="mt-5 max-w-xl text-base font-medium leading-7 text-white/75 sm:text-lg">
                        Add your grade, latest term, subjects, and marks on one screen. Course browsing stays open even if you skip for now.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('subjects.index', ['continue' => 1]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3.5 text-sm font-black text-[#01225E] hover:bg-white/90">
                            Add subjects & marks <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                        </a>
                        <a href="{{ route('course-match.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/25 px-5 py-3.5 text-sm font-black text-white hover:bg-white/10">
                            Browse courses first
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

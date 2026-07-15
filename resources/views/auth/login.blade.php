@extends('layouts.app')

@section('title', 'Log in · Chamu')

@section('content')
    <main class="min-h-screen grid lg:grid-cols-[1fr_480px] bg-white">
        <section class="hidden lg:flex flex-col justify-between p-10 bg-[#F3F7FC] border-r border-blue-100">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/brand/chamu-logo.png') }}" alt="Chamu logo" class="h-10 w-10 rounded-xl object-contain">
                <span class="font-bold text-xl">Chamu</span>
            </a>
            <div class="max-w-xl">
                <p class="text-sm font-semibold uppercase text-[#01225E] mb-3">Welcome back</p>
                <h1 class="text-5xl font-bold tracking-normal text-neutral-950">Pick up your streak where you left it.</h1>
                <p class="mt-4 text-lg text-neutral-600">Your points, grade, and subjects follow your account.</p>
            </div>
            <p class="text-sm text-neutral-500">Built for South African Grade 10-12 learners.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10">
            <div class="w-full max-w-md">
                <a href="{{ url('/') }}" class="lg:hidden inline-flex items-center gap-2 mb-8">
                    <img src="{{ asset('images/brand/chamu-logo.png') }}" alt="Chamu logo" class="h-10 w-10 rounded-xl object-contain">
                    <span class="font-bold text-xl">Chamu</span>
                </a>

                <h1 class="text-3xl font-bold">Log in</h1>
                <p class="mt-2 text-neutral-500">Track streaks, points, and your saved learning path.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="username" class="block text-sm font-semibold mb-2">Username</label>
                        <input id="username" name="username" value="{{ old('username') }}" required autofocus class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('username')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold mb-2">Password</label>
                        <input id="password" name="password" type="password" required class="w-full rounded-xl border border-neutral-300 px-4 py-3 outline-none focus:border-[#01225E]">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm font-medium text-neutral-600">
                        <input name="remember" type="checkbox" class="accent-[#01225E]">
                        Remember me
                    </label>

                    <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3.5 font-semibold text-white hover:bg-[#001A48]">
                        Log in <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                    </button>
                </form>

                <p class="mt-6 text-sm text-neutral-600">
                    New here?
                    <a href="{{ route('register') }}" class="font-semibold text-[#01225E]">Create an account</a>
                </p>
            </div>
        </section>
    </main>
@endsection

@extends('layouts.app')

@section('title', 'Tools · Chamu')

@section('content')
    <main class="max-w-5xl mx-auto px-5 lg:px-8 py-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-8">
            <div>
                <p class="text-sm font-semibold text-[#01225E]">Student tools</p>
                <h1 class="mt-1 text-3xl font-bold">Tools</h1>
                <p class="mt-2 text-neutral-500">Simple helpers for studying without leaving Chamu.</p>
            </div>
            <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                <i data-lucide="layout-dashboard" style="width:16px;height:16px;"></i>
                Dashboard
            </a>
        </div>

        <section class="grid gap-5 lg:grid-cols-[1fr_320px]">
            <article class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#01225E] text-white">
                        <i data-lucide="calculator" style="width:22px;height:22px;"></i>
                    </span>
                    <div>
                        <h2 class="text-xl font-bold">Calculator</h2>
                        <p class="mt-1 text-sm text-neutral-500">Add, subtract, multiply, or divide two numbers.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('tools.index') }}" class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="first_number" class="mb-2 block text-xs font-bold uppercase text-neutral-500">First number</label>
                        <input
                            id="first_number"
                            name="first_number"
                            type="number"
                            step="any"
                            value="{{ $calculator['first_number'] }}"
                            class="w-full rounded-xl border border-neutral-300 px-4 py-3 text-lg font-semibold outline-none focus:border-[#01225E]"
                            required
                        >
                    </div>

                    <div>
                        <label for="second_number" class="mb-2 block text-xs font-bold uppercase text-neutral-500">Second number</label>
                        <input
                            id="second_number"
                            name="second_number"
                            type="number"
                            step="any"
                            value="{{ $calculator['second_number'] }}"
                            class="w-full rounded-xl border border-neutral-300 px-4 py-3 text-lg font-semibold outline-none focus:border-[#01225E]"
                            required
                        >
                    </div>

                    <div>
                        <label for="operation" class="mb-2 block text-xs font-bold uppercase text-neutral-500">Operation</label>
                        <select id="operation" name="operation" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#01225E]">
                            <option value="add" @selected($calculator['operation'] === 'add')>Addition (+)</option>
                            <option value="subtract" @selected($calculator['operation'] === 'subtract')>Subtraction (-)</option>
                            <option value="multiply" @selected($calculator['operation'] === 'multiply')>Multiplication (×)</option>
                            <option value="divide" @selected($calculator['operation'] === 'divide')>Division (÷)</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#01225E] px-5 py-3 font-semibold text-white hover:bg-[#001A48]">
                            Calculate <i data-lucide="equal" style="width:18px;height:18px;"></i>
                        </button>
                    </div>
                </form>

                @if (isset($errors) && $errors->any())
                    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                        Please enter two valid numbers.
                    </div>
                @endif
            </article>

            <aside class="rounded-2xl border border-neutral-200 bg-white p-5">
                <p class="text-xs font-bold uppercase text-neutral-500">Result</p>

                @if ($calculator['error'])
                    <div class="mt-4 rounded-xl bg-rose-50 px-4 py-3 font-semibold text-rose-700">
                        {{ $calculator['error'] }}
                    </div>
                @elseif ($calculator['result'] !== null)
                    <p class="mt-4 break-words text-4xl font-bold text-neutral-950">
                        {{ rtrim(rtrim(number_format($calculator['result'], 8, '.', ''), '0'), '.') }}
                    </p>
                @else
                    <p class="mt-4 text-sm text-neutral-500">Enter two numbers and choose an operation.</p>
                @endif
            </aside>
        </section>
    </main>
@endsection

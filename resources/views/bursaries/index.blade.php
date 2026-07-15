@extends('layouts.app')

@section('title', 'Bursaries · Matric Hub')

@section('content')
    <main class="mx-auto max-w-7xl px-5 py-8 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#E8425B]">Funding match</p>
                <h1 class="mt-1 text-3xl font-bold">Bursaries</h1>
                <p class="mt-2 max-w-3xl text-neutral-500">Browse funding opportunities and compare listed academic requirements against your marks when available.</p>
            </div>
            @auth
                <a href="{{ route('marks.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 font-semibold hover:bg-neutral-50">
                    Marks <i data-lucide="line-chart" style="width:16px;height:16px;"></i>
                </a>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#E8425B] px-4 py-2 font-semibold text-white hover:bg-[#d73550]">
                    Match with marks <i data-lucide="log-in" style="width:16px;height:16px;"></i>
                </a>
            @endauth
        </div>

        <form method="GET" action="{{ route('bursaries.index') }}" class="mb-6 rounded-2xl border border-neutral-200 bg-white p-4 soft-card">
            <div class="grid gap-3 lg:grid-cols-[1.3fr_1fr_1fr_auto]">
                <div>
                    <label for="search" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="search" style="width:14px;height:14px;"></i>
                        Search
                    </label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Bursary, company, field" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#E8425B]">
                </div>

                <div>
                    <label for="category" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="tags" style="width:14px;height:14px;"></i>
                        Category
                    </label>
                    <select id="category" name="category" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#E8425B]">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="company_id" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase text-neutral-500">
                        <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                        Company
                    </label>
                    <select id="company_id" name="company_id" class="w-full rounded-xl border border-neutral-300 px-4 py-3 font-semibold outline-none focus:border-[#E8425B]">
                        <option value="">All companies</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((int) $filters['company_id'] === $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#E8425B] px-5 py-3 font-semibold text-white hover:bg-[#d73550]">
                        Filter <i data-lucide="sliders-horizontal" style="width:18px;height:18px;"></i>
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-2 border-t border-neutral-100 pt-4 text-sm font-semibold text-neutral-500 sm:flex-row sm:items-center sm:justify-between">
                <span>{{ $bursaries->total() }} bursaries found</span>
                <a href="{{ route('bursaries.index') }}" class="text-[#E8425B] hover:underline">Reset filters</a>
            </div>
        </form>

        @auth
            @unless ($hasMarks)
                <section class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm font-semibold text-sky-800">
                    Add marks to unlock bursary-match checks for opportunities with academic requirements.
                </section>
            @endunless
        @endauth

        <section class="grid gap-4">
            @forelse ($bursaries as $bursary)
                @php
                    $toneClasses = [
                        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
                        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
                        'neutral' => 'border-neutral-200 bg-neutral-100 text-neutral-700',
                    ][$bursary->match['tone']] ?? 'border-neutral-200 bg-neutral-100 text-neutral-700';
                @endphp
                <article class="rounded-2xl border border-neutral-200 bg-white p-5 soft-card">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-bold text-neutral-700">{{ $bursary->category ?? 'Bursary' }}</span>
                                <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $toneClasses }}">{{ $bursary->match['status'] }}</span>
                            </div>
                            <h2 class="mt-3 text-xl font-bold text-neutral-950">{{ $bursary->title }}</h2>
                            <p class="mt-1 text-sm font-semibold text-neutral-500">{{ $bursary->company_name ?? 'Provider not listed' }}</p>
                            @if ($bursary->summary)
                                <p class="mt-3 text-sm text-neutral-600">{{ $bursary->summary }}</p>
                            @endif
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('bursaries.show', $bursary->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                    Details <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                </a>
                                <a href="{{ $bursary->apply_url ?: $bursary->source_url }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 px-4 py-2 text-sm font-semibold hover:bg-neutral-50">
                                    Apply link <i data-lucide="external-link" style="width:16px;height:16px;"></i>
                                </a>
                            </div>
                        </div>

                        <div class="grid min-w-full grid-cols-2 gap-2 sm:min-w-[420px] sm:grid-cols-3">
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                <p class="text-xs font-bold uppercase text-neutral-500">Closes</p>
                                <p class="mt-1 text-sm font-bold">{{ $bursary->closing_date_label ?? 'Not listed' }}</p>
                            </div>
                            <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                                <p class="text-xs font-bold uppercase text-neutral-500">Requirements</p>
                                <p class="mt-1 text-2xl font-bold">{{ $bursary->match['requirements_count'] }}</p>
                            </div>
                            <div class="col-span-2 rounded-xl border border-neutral-200 bg-neutral-50 p-3 sm:col-span-1">
                                <p class="text-xs font-bold uppercase text-neutral-500">Coverage</p>
                                <p class="mt-1 text-sm font-bold">{{ $bursary->coverage_value ? Str::limit($bursary->coverage_value, 70) : 'See details' }}</p>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <section class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center">
                    <h2 class="text-xl font-bold">No bursaries found</h2>
                    <p class="mt-2 text-neutral-500">Try changing your search or filters.</p>
                </section>
            @endforelse
        </section>

        @if ($bursaries->hasPages())
            <div class="mt-6 rounded-2xl border border-neutral-200 bg-white p-4">
                {{ $bursaries->onEachSide(1)->links() }}
            </div>
        @endif
    </main>
@endsection

@extends('layouts.app')

@section('title', 'Bursaries · Chamu')

@section('content')
    @php
        $activeFilterCount = collect([$search, $filters['category'], $filters['company_id']])
            ->filter(fn ($value) => filled($value))
            ->count();
        $selectedCompany = $filters['company_id'] ? $companies->firstWhere('id', (int) $filters['company_id']) : null;
        $featuredCategories = $categories->take(8);
        $heroImage = asset('images/bursaries/graduates-celebrating.png');
        $bursaryNoun = Str::plural('bursary', $bursaries->total());
        $opportunityNoun = Str::plural('funding opportunity', $bursaries->total());
        $filterSummary = $activeFilterCount > 0
            ? trim(collect([$search ? '"'.$search.'"' : null, $filters['category'], $selectedCompany?->name])->filter()->implode(' · '))
            : 'All funding opportunities';
    @endphp

    <main class="bg-[#f5f7fb] text-neutral-950">
        <section class="relative isolate overflow-hidden bg-[#07111f] text-white">
            <div class="absolute inset-0 -z-10">
                <img src="{{ $heroImage }}" alt="" class="h-full w-full object-cover object-[center_48%] opacity-80">
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(4,10,22,.96)_0%,rgba(4,10,22,.76)_43%,rgba(4,10,22,.35)_100%)]"></div>
                <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[#f5f7fb] via-[#f5f7fb]/45 to-transparent"></div>
            </div>

            <div class="mx-auto max-w-7xl px-5 pb-12 pt-12 sm:pb-16 sm:pt-16 lg:px-8 lg:pb-20 lg:pt-20">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-end">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-white/85 backdrop-blur">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            Funding match
                        </div>
                        <h1 class="mt-5 max-w-3xl text-4xl font-black leading-[1.02] text-white sm:text-6xl">
                            Find a bursary that fits your marks and your future.
                        </h1>
                        <p class="mt-5 max-w-2xl text-base font-medium leading-7 text-white/75 sm:text-lg">
                            Browse {{ number_format($bursaries->total()) }} {{ $opportunityNoun }}, compare academic requirements, then open details before applying or visiting a provider link.
                        </p>

                        <div class="mt-8 grid max-w-2xl grid-cols-3 divide-x divide-white/15 border-y border-white/15 py-4">
                            <div class="pr-4">
                                <p class="text-2xl font-black">{{ number_format($bursaries->total()) }}</p>
                                <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-white/55">Bursaries</p>
                            </div>
                            <div class="px-4">
                                <p class="text-2xl font-black">{{ number_format($categories->count()) }}</p>
                                <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-white/55">Fields</p>
                            </div>
                            <div class="pl-4">
                                <p class="text-2xl font-black">{{ number_format($companies->count()) }}</p>
                                <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-white/55">Providers</p>
                            </div>
                        </div>
                    </div>

                    <div class="hidden rounded-lg border border-white/15 bg-white/10 p-5 shadow-2xl backdrop-blur lg:block">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-white/55">Current view</p>
                                <p class="mt-2 text-lg font-black">{{ $filterSummary }}</p>
                            </div>
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-emerald-400 text-[#07111f]">
                                <i data-lucide="badge-dollar-sign" style="width:22px;height:22px;"></i>
                            </span>
                        </div>
                        <div class="mt-5 space-y-3 border-t border-white/15 pt-5">
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="font-semibold text-white/65">Academic match</span>
                                <span class="font-black">{{ auth()->check() ? 'Enabled' : 'Sign in' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <span class="font-semibold text-white/65">Active filters</span>
                                <span class="font-black">{{ $activeFilterCount }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('bursaries.index') }}" class="mt-8 rounded-lg border border-white/15 bg-white p-3 text-neutral-950 shadow-[0_24px_70px_rgba(0,0,0,0.22)]">
                    <div class="grid gap-2 lg:grid-cols-[1.35fr_1fr_1fr_auto]">
                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
                            <label for="search" class="flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                <i data-lucide="search" style="width:14px;height:14px;"></i>
                                Search
                            </label>
                            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Bursary, company, field" class="mt-2 w-full bg-transparent text-base font-bold outline-none placeholder:text-neutral-400">
                        </div>

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
                            <label for="category" class="flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                <i data-lucide="tags" style="width:14px;height:14px;"></i>
                                Category
                            </label>
                            <select id="category" name="category" class="mt-2 w-full bg-transparent text-base font-bold outline-none">
                                <option value="">All categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
                            <label for="company_id" class="flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                                Company
                            </label>
                            <select id="company_id" name="company_id" class="mt-2 w-full bg-transparent text-base font-bold outline-none">
                                <option value="">All companies</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected((int) $filters['company_id'] === $company->id)>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button class="inline-flex min-h-[76px] items-center justify-center gap-2 rounded-lg bg-[#01225E] px-6 text-base font-black text-white shadow-[0_12px_28px_rgba(1,34,94,0.28)] hover:bg-[#001A48]">
                            Filter <i data-lucide="sliders-horizontal" style="width:18px;height:18px;"></i>
                        </button>
                    </div>

                    <div class="mt-3 flex flex-col gap-3 border-t border-neutral-100 px-1 pt-3 text-sm font-bold text-neutral-500 sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ number_format($bursaries->total()) }} {{ $bursaryNoun }} found</span>
                        <a href="{{ route('bursaries.index') }}" class="inline-flex items-center gap-1.5 text-[#01225E] hover:text-[#001A48]">
                            Reset filters <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i>
                        </a>
                    </div>
                </form>

                @if ($featuredCategories->isNotEmpty())
                    <div class="no-scrollbar mt-4 flex gap-2 overflow-x-auto pb-1">
                        @foreach ($featuredCategories as $category)
                            <a
                                href="{{ route('bursaries.index', ['search' => $search ?: null, 'category' => $category, 'company_id' => $filters['company_id']]) }}"
                                @class([
                                    'inline-flex shrink-0 items-center rounded-full border px-3 py-1.5 text-xs font-black transition',
                                    'border-emerald-300 bg-emerald-300 text-[#07111f]' => $filters['category'] === $category,
                                    'border-white/20 bg-white/10 text-white/80 hover:bg-white/20' => $filters['category'] !== $category,
                                ])
                            >
                                {{ $category }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-8 lg:px-8">
            @include('partials.adsense-home-placement', ['class' => 'mb-6'])

            @auth
                @unless ($hasMarks)
                    <section class="mb-6 rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm font-bold text-sky-900">
                        Add marks to unlock bursary-match checks for opportunities with academic requirements.
                    </section>
                @endunless
            @endauth

            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Funding shortlist</p>
                    <h2 class="mt-1 text-2xl font-black text-neutral-950">Best matches to explore</h2>
                </div>
                @auth
                    <a href="{{ route('marks.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-black text-neutral-950 shadow-sm hover:bg-neutral-50">
                        Marks <i data-lucide="line-chart" style="width:16px;height:16px;"></i>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#01225E] px-4 py-2.5 text-sm font-black text-white shadow-sm hover:bg-[#001A48]">
                        Match with marks <i data-lucide="log-in" style="width:16px;height:16px;"></i>
                    </a>
                @endauth
            </div>

            <section class="grid gap-4">
                @forelse ($bursaries as $bursary)
                    @php
                        $tone = [
                            'emerald' => [
                                'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                'bar' => 'bg-emerald-500',
                                'icon' => 'check-circle-2',
                            ],
                            'amber' => [
                                'badge' => 'border-amber-200 bg-amber-50 text-amber-800',
                                'bar' => 'bg-amber-400',
                                'icon' => 'alert-circle',
                            ],
                            'sky' => [
                                'badge' => 'border-sky-200 bg-sky-50 text-sky-800',
                                'bar' => 'bg-sky-500',
                                'icon' => 'log-in',
                            ],
                            'neutral' => [
                                'badge' => 'border-neutral-200 bg-neutral-100 text-neutral-700',
                                'bar' => 'bg-neutral-300',
                                'icon' => 'info',
                            ],
                        ][$bursary->match['tone']] ?? [
                            'badge' => 'border-neutral-200 bg-neutral-100 text-neutral-700',
                            'bar' => 'bg-neutral-300',
                            'icon' => 'info',
                        ];
                        $logoSrc = null;

                        if ($bursary->company_logo) {
                            $logoSrc = Str::startsWith($bursary->company_logo, ['http://', 'https://', '/'])
                                ? $bursary->company_logo
                                : asset($bursary->company_logo);
                        }

                        $initials = collect(explode(' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', (string) ($bursary->company_name ?? $bursary->title))))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
                            ->implode('');
                    @endphp
                    <article class="group overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-[0_16px_45px_rgba(15,23,42,0.06)] transition hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-[0_22px_60px_rgba(15,23,42,0.10)]">
                        <div class="grid lg:grid-cols-[minmax(0,1fr)_360px]">
                            <div class="relative p-5 sm:p-6">
                                <div class="absolute inset-y-0 left-0 w-1.5 {{ $tone['bar'] }}"></div>
                                <div class="flex gap-4 pl-2">
                                    <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50 text-sm font-black text-[#01225E]">
                                        @if ($logoSrc)
                                            <img src="{{ $logoSrc }}" alt="{{ $bursary->company_name ?? $bursary->title }} logo" class="h-full w-full object-contain p-2">
                                        @else
                                            {{ $initials ?: 'BF' }}
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-black text-neutral-700">{{ $bursary->category ?? 'Bursary' }}</span>
                                            <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-black {{ $tone['badge'] }}">
                                                <i data-lucide="{{ $tone['icon'] }}" style="width:13px;height:13px;"></i>
                                                {{ $bursary->match['status'] }}
                                            </span>
                                        </div>
                                        <h3 class="mt-3 text-xl font-black leading-tight text-neutral-950 sm:text-2xl">{{ $bursary->title }}</h3>
                                        <p class="mt-1 text-sm font-bold text-neutral-500">{{ $bursary->company_name ?? 'Provider not listed' }}</p>
                                    </div>
                                </div>

                                @if ($bursary->summary)
                                    <p class="mt-5 max-w-3xl pl-2 text-sm font-medium leading-6 text-neutral-600 sm:text-base">{{ $bursary->summary }}</p>
                                @endif

                                <div class="mt-5 flex flex-wrap gap-2 pl-2">
                                    <a href="{{ route('bursaries.show', $bursary->id) }}" class="inline-flex items-center gap-2 rounded-lg bg-[#01225E] px-4 py-2 text-sm font-black text-white hover:bg-[#001A48]">
                                        Details <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                                    </a>
                                    <span class="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm font-bold text-neutral-500">
                                        Review first <i data-lucide="file-search" style="width:16px;height:16px;"></i>
                                    </span>
                                </div>
                            </div>

                            <dl class="divide-y divide-neutral-200 border-t border-neutral-200 bg-neutral-50/70 p-5 lg:border-l lg:border-t-0">
                                <div class="flex items-start justify-between gap-4 py-3 first:pt-0">
                                    <dt class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                        <i data-lucide="calendar-days" style="width:14px;height:14px;"></i>
                                        Closes
                                    </dt>
                                    <dd class="max-w-[160px] text-right text-sm font-black text-neutral-950">{{ $bursary->closing_date_label ?? 'Not listed' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-3">
                                    <dt class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                        <i data-lucide="list-checks" style="width:14px;height:14px;"></i>
                                        Requirements
                                    </dt>
                                    <dd class="text-right text-lg font-black text-neutral-950">{{ $bursary->match['requirements_count'] }}</dd>
                                </div>
                                <div class="py-3 last:pb-0">
                                    <dt class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                        <i data-lucide="wallet" style="width:14px;height:14px;"></i>
                                        Coverage
                                    </dt>
                                    <dd class="mt-2 text-sm font-bold leading-5 text-neutral-800">{{ $bursary->coverage_value ? Str::limit($bursary->coverage_value, 105) : 'See details' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </article>
                @empty
                    <section class="rounded-lg border border-dashed border-neutral-300 bg-white p-10 text-center shadow-sm">
                        <div class="mx-auto grid h-12 w-12 place-items-center rounded-lg bg-neutral-100 text-neutral-500">
                            <i data-lucide="search-x" style="width:22px;height:22px;"></i>
                        </div>
                        <h2 class="mt-4 text-xl font-black">No bursaries found</h2>
                        <p class="mt-2 text-sm font-semibold text-neutral-500">Try changing your search or filters.</p>
                        <a href="{{ route('bursaries.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-[#01225E] px-4 py-2 text-sm font-black text-white hover:bg-[#001A48]">
                            Reset filters <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i>
                        </a>
                    </section>
                @endforelse
            </section>

            @if ($bursaries->hasPages())
                <div class="mt-6 rounded-lg border border-neutral-200 bg-white p-4 shadow-sm">
                    {{ $bursaries->onEachSide(1)->links() }}
                </div>
            @endif
        </section>
    </main>
@endsection

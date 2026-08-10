@extends('layouts.app')

@section('title', 'Bursaries · Chamu')

@push('head')
    <x-seo-meta
        title="South African Bursaries and Funding Opportunities - Chamu"
        description="Browse South African bursaries by provider, field, closing date, eligibility context, and application details on Chamu."
        :canonical="route('bursaries.index')"
    />
@endpush

@section('content')
    @php
        $activeFilterCount = $selectedFilters->count() + (filled($search) ? 1 : 0);
        $featuredCategories = $categories->take(8);
        $heroImage = asset('images/bursaries/graduates-celebrating.png');
        $bursaryNoun = Str::plural('bursary', $bursaries->total());
        $opportunityNoun = Str::plural('funding opportunity', $bursaries->total());
        $filterSummary = $activeFilterCount > 0
            ? trim(collect([
                $search ? '"'.$search.'"' : null,
                ...$selectedFilters->pluck('label')->all(),
            ])->filter()->implode(' · '))
            : 'All funding opportunities';

        $filterQuery = function (array $tokens, ?string $searchValue = null) use ($search): array {
            $query = [];

            if (filled($searchValue ?? $search)) {
                $query['search'] = $searchValue ?? $search;
            }

            if ($tokens !== []) {
                $query['filter'] = array_values($tokens);
            }

            return $query;
        };
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

                <form method="GET" action="{{ route('bursaries.index') }}" class="mt-8 rounded-lg border border-white/15 bg-white p-3 text-neutral-950 shadow-[0_24px_70px_rgba(0,0,0,0.22)]" data-bursary-filter-form>
                    <div class="grid gap-2 lg:grid-cols-[minmax(0,1fr)_auto]">
                        <div class="relative rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3" data-bursary-filter>
                            <label for="bursary-filter-input" class="flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.14em] text-neutral-500">
                                <i data-lucide="search" style="width:14px;height:14px;"></i>
                                Search
                            </label>

                            <div class="mt-2 flex min-h-[28px] flex-wrap items-center gap-2" data-bursary-filter-control>
                                <div class="contents" data-bursary-filter-tags>
                                    @foreach ($selectedFilters as $selectedFilter)
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-xs font-black text-neutral-800" data-bursary-filter-tag data-token="{{ $selectedFilter['token'] }}">
                                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] {{ $selectedFilter['type'] === 'company' ? 'bg-sky-100 text-sky-800' : 'bg-emerald-100 text-emerald-800' }}">
                                                {{ $selectedFilter['type'] === 'company' ? 'Company' : 'Category' }}
                                            </span>
                                            <span>{{ $selectedFilter['label'] }}</span>
                                            <button type="button" class="grid h-4 w-4 place-items-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" data-bursary-filter-remove aria-label="Remove {{ $selectedFilter['label'] }}">
                                                <i data-lucide="x" style="width:12px;height:12px;"></i>
                                            </button>
                                            <input type="hidden" name="filter[]" value="{{ $selectedFilter['token'] }}">
                                        </span>
                                    @endforeach
                                </div>

                                <input
                                    id="bursary-filter-input"
                                    name="search"
                                    type="search"
                                    value="{{ $search }}"
                                    autocomplete="off"
                                    placeholder="{{ $selectedFilters->isEmpty() ? 'Search category or company' : 'Add another…' }}"
                                    class="min-w-[12rem] flex-1 bg-transparent text-base font-bold outline-none placeholder:text-neutral-400"
                                    data-bursary-filter-input
                                    aria-expanded="false"
                                    aria-controls="bursary-filter-panel"
                                    role="combobox"
                                >
                            </div>

                            <div id="bursary-filter-panel" class="absolute left-0 right-0 top-[calc(100%+0.5rem)] z-50 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white text-neutral-950 shadow-2xl" data-bursary-filter-panel>
                                <div class="max-h-80 overflow-y-auto p-2">
                                    <div data-bursary-filter-group data-group="category">
                                        <p class="px-3 py-2 text-[11px] font-black uppercase tracking-[0.16em] text-neutral-400">Categories</p>
                                        @foreach ($categories as $category)
                                            @php
                                                $token = $filterTypeCategory.':'.$category;
                                                $isSelected = in_array($category, $selectedCategories, true);
                                            @endphp
                                            <button
                                                type="button"
                                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold hover:bg-neutral-50 {{ $isSelected ? 'bg-emerald-50' : '' }}"
                                                data-bursary-filter-option
                                                data-index="{{ $filterTypeCategory }}"
                                                data-type="category"
                                                data-value="{{ $category }}"
                                                data-label="{{ $category }}"
                                                data-token="{{ $token }}"
                                                data-search="{{ $category }}"
                                                data-selected="{{ $isSelected ? 'true' : 'false' }}"
                                            >
                                                <span class="min-w-0">
                                                    <span class="block truncate text-neutral-950">{{ $category }}</span>
                                                    <span class="mt-0.5 inline-flex rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] text-emerald-800">Category · {{ $filterTypeCategory }}</span>
                                                </span>
                                                <i data-lucide="check" class="{{ $isSelected ? '' : 'hidden' }} shrink-0 text-emerald-600" style="width:16px;height:16px;" data-bursary-filter-check></i>
                                            </button>
                                        @endforeach
                                    </div>

                                    <div class="mt-1 border-t border-neutral-100 pt-1" data-bursary-filter-group data-group="company">
                                        <p class="px-3 py-2 text-[11px] font-black uppercase tracking-[0.16em] text-neutral-400">Companies</p>
                                        @foreach ($companies as $company)
                                            @php
                                                $token = $filterTypeCompany.':'.$company->id;
                                                $isSelected = in_array((int) $company->id, $selectedCompanyIds, true);
                                            @endphp
                                            <button
                                                type="button"
                                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold hover:bg-neutral-50 {{ $isSelected ? 'bg-sky-50' : '' }}"
                                                data-bursary-filter-option
                                                data-index="{{ $filterTypeCompany }}"
                                                data-type="company"
                                                data-value="{{ $company->id }}"
                                                data-label="{{ $company->name }}"
                                                data-token="{{ $token }}"
                                                data-search="{{ $company->name }}"
                                                data-selected="{{ $isSelected ? 'true' : 'false' }}"
                                            >
                                                <span class="min-w-0">
                                                    <span class="block truncate text-neutral-950">{{ $company->name }}</span>
                                                    <span class="mt-0.5 inline-flex rounded-full bg-sky-100 px-1.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] text-sky-800">Company · {{ $filterTypeCompany }}</span>
                                                </span>
                                                <i data-lucide="check" class="{{ $isSelected ? '' : 'hidden' }} shrink-0 text-sky-700" style="width:16px;height:16px;" data-bursary-filter-check></i>
                                            </button>
                                        @endforeach
                                    </div>

                                    <p class="hidden px-3 py-2 text-sm font-semibold text-neutral-500" data-bursary-filter-empty>No matches</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="inline-flex min-h-[76px] items-center justify-center gap-2 rounded-lg bg-[#01225E] px-6 text-base font-black text-white shadow-[0_12px_28px_rgba(1,34,94,0.28)] hover:bg-[#001A48]">
                            Search <i data-lucide="search" style="width:18px;height:18px;"></i>
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
                    <div class="no-scrollbar mt-4 flex gap-2 overflow-x-auto pb-1" data-bursary-filter-pills>
                        @foreach ($featuredCategories as $category)
                            @php
                                $categoryToken = $filterTypeCategory.':'.$category;
                                $isSelectedCategory = in_array($category, $selectedCategories, true);
                                $pillTokens = $isSelectedCategory
                                    ? $selectedFilters->pluck('token')->reject(fn ($token) => $token === $categoryToken)->values()->all()
                                    : $selectedFilters->pluck('token')->push($categoryToken)->unique()->values()->all();
                            @endphp
                            <a
                                href="{{ route('bursaries.index', $filterQuery($pillTokens)) }}"
                                data-bursary-filter-pill
                                data-token="{{ $categoryToken }}"
                                data-index="{{ $filterTypeCategory }}"
                                data-type="category"
                                data-value="{{ $category }}"
                                data-label="{{ $category }}"
                                @class([
                                    'inline-flex shrink-0 items-center rounded-full border px-3 py-1.5 text-xs font-black transition',
                                    'border-emerald-300 bg-emerald-300 text-[#07111f]' => $isSelectedCategory,
                                    'border-white/20 bg-white/10 text-white/80 hover:bg-white/20' => ! $isSelectedCategory,
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
                    <a href="{{ route('subjects.index', ['manage' => 1]) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-black text-neutral-950 shadow-sm hover:bg-neutral-50">
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

@push('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-bursary-filter]');
            const form = document.querySelector('[data-bursary-filter-form]');
            if (! root || ! form) return;

            const input = root.querySelector('[data-bursary-filter-input]');
            const panel = root.querySelector('[data-bursary-filter-panel]');
            const tags = root.querySelector('[data-bursary-filter-tags]');
            const empty = root.querySelector('[data-bursary-filter-empty]');
            const options = Array.from(root.querySelectorAll('[data-bursary-filter-option]'));
            const groups = Array.from(root.querySelectorAll('[data-bursary-filter-group]'));
            const pills = Array.from(document.querySelectorAll('[data-bursary-filter-pill]'));

            const normalise = (value) => String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();

            const refreshIcons = () => {
                if (window.lucide) window.lucide.createIcons();
            };

            const selectedTokens = () => Array.from(tags.querySelectorAll('[data-bursary-filter-tag]')).map((tag) => tag.dataset.token);

            const open = () => {
                panel.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
                filterOptions();
            };

            const close = () => {
                panel.classList.add('hidden');
                input.setAttribute('aria-expanded', 'false');
            };

            const syncOptionState = (token, selected) => {
                const option = options.find((item) => item.dataset.token === token);
                if (! option) return;

                option.dataset.selected = selected ? 'true' : 'false';
                option.classList.toggle('bg-emerald-50', selected && option.dataset.type === 'category');
                option.classList.toggle('bg-sky-50', selected && option.dataset.type === 'company');
                option.querySelector('[data-bursary-filter-check]')?.classList.toggle('hidden', ! selected);
            };

            const syncPillState = (token, selected) => {
                const pill = pills.find((item) => item.dataset.token === token);
                if (! pill) return;

                pill.classList.toggle('border-emerald-300', selected);
                pill.classList.toggle('bg-emerald-300', selected);
                pill.classList.toggle('text-[#07111f]', selected);
                pill.classList.toggle('border-white/20', ! selected);
                pill.classList.toggle('bg-white/10', ! selected);
                pill.classList.toggle('text-white/80', ! selected);
                pill.classList.toggle('hover:bg-white/20', ! selected);
            };

            const updatePlaceholder = () => {
                input.placeholder = selectedTokens().length === 0
                    ? 'Search category or company'
                    : 'Add another…';
            };

            const addTag = (option) => {
                const token = option.dataset.token;
                if (! token || selectedTokens().includes(token)) return;

                const typeLabel = option.dataset.type === 'company' ? 'Company' : 'Category';
                const typeClass = option.dataset.type === 'company'
                    ? 'bg-sky-100 text-sky-800'
                    : 'bg-emerald-100 text-emerald-800';

                const tag = document.createElement('span');
                tag.className = 'inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-xs font-black text-neutral-800';
                tag.dataset.bursaryFilterTag = '';
                tag.dataset.token = token;
                tag.innerHTML = `
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-black uppercase tracking-[0.12em] ${typeClass}">${typeLabel}</span>
                    <span></span>
                    <button type="button" class="grid h-4 w-4 place-items-center rounded-full text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" data-bursary-filter-remove aria-label="Remove filter">
                        <i data-lucide="x" style="width:12px;height:12px;"></i>
                    </button>
                    <input type="hidden" name="filter[]" value="">
                `;
                tag.querySelector('span:nth-child(2)').textContent = option.dataset.label || '';
                tag.querySelector('input[type="hidden"]').value = token;
                tags.appendChild(tag);

                syncOptionState(token, true);
                syncPillState(token, true);
                updatePlaceholder();
                refreshIcons();
            };

            const removeTag = (token) => {
                Array.from(tags.querySelectorAll('[data-bursary-filter-tag]'))
                    .find((tag) => tag.dataset.token === token)
                    ?.remove();
                syncOptionState(token, false);
                syncPillState(token, false);
                updatePlaceholder();
            };

            const toggleOption = (option) => {
                const token = option.dataset.token;
                if (! token) return;

                if (selectedTokens().includes(token)) {
                    removeTag(token);
                    return;
                }

                addTag(option);
            };

            const filterOptions = () => {
                const query = normalise(input.value);
                let visibleCount = 0;

                options.forEach((option) => {
                    const haystack = normalise(option.dataset.search || option.dataset.label);
                    const isVisible = query === '' || haystack.includes(query);
                    option.classList.toggle('hidden', ! isVisible);
                    if (isVisible) visibleCount += 1;
                });

                groups.forEach((group) => {
                    const hasVisible = Array.from(group.querySelectorAll('[data-bursary-filter-option]'))
                        .some((option) => ! option.classList.contains('hidden'));
                    group.classList.toggle('hidden', ! hasVisible);
                });

                empty.classList.toggle('hidden', visibleCount > 0);
            };

            const firstVisibleOption = () => options.find((option) => ! option.classList.contains('hidden'));

            root.querySelector('[data-bursary-filter-control]')?.addEventListener('click', (event) => {
                if (event.target.closest('[data-bursary-filter-remove]')) return;
                open();
                input.focus();
            });

            input.addEventListener('focus', open);
            input.addEventListener('input', () => {
                open();
                filterOptions();
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    close();
                    return;
                }

                if (event.key === 'Backspace' && input.value === '') {
                    const current = selectedTokens();
                    const last = current[current.length - 1];
                    if (last) {
                        event.preventDefault();
                        removeTag(last);
                    }
                    return;
                }

                if (event.key === 'Enter') {
                    const option = firstVisibleOption();
                    if (option && ! panel.classList.contains('hidden') && normalise(input.value) !== '') {
                        event.preventDefault();
                        toggleOption(option);
                        input.value = '';
                        filterOptions();
                        return;
                    }
                    // Let the form submit when Search / Enter applies the current tags + text.
                }
            });

            options.forEach((option) => {
                option.addEventListener('click', () => {
                    toggleOption(option);
                    input.value = '';
                    filterOptions();
                    input.focus();
                });
            });

            tags.addEventListener('click', (event) => {
                const button = event.target.closest('[data-bursary-filter-remove]');
                if (! button) return;

                const tag = button.closest('[data-bursary-filter-tag]');
                if (! tag) return;

                event.preventDefault();
                removeTag(tag.dataset.token);
                input.focus();
            });

            pills.forEach((pill) => {
                pill.addEventListener('click', (event) => {
                    event.preventDefault();

                    const option = options.find((item) => item.dataset.token === pill.dataset.token);
                    if (option) {
                        toggleOption(option);
                    } else if (selectedTokens().includes(pill.dataset.token)) {
                        removeTag(pill.dataset.token);
                    } else {
                        addTag({
                            dataset: {
                                token: pill.dataset.token,
                                type: pill.dataset.type,
                                label: pill.dataset.label,
                            },
                        });
                    }

                    input.value = '';
                    form.requestSubmit();
                });
            });

            document.addEventListener('click', (event) => {
                if (! root.contains(event.target)) {
                    close();
                }
            });

            updatePlaceholder();
        })();
    </script>
@endpush

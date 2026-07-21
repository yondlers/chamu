<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Chamu')</title>
    @php
        $adsenseEnabled = request()->routeIs(
            'aps.index',
            'aps-calculator.index',
            'bursaries.index',
            'bursaries.show',
            'learn.index',
            'guides.*',
            'public.universities.show',
            'public.qualifications.show',
        ) || (request()->routeIs('content.index') && request()->filled('subject_id'));
    @endphp
    @stack('head')
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @if ($adsenseEnabled)
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4352231193802470"
            crossorigin="anonymous"></script>
    @endif
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; background: #fafafa; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .soft-card { box-shadow: 0 6px 20px rgba(0,0,0,0.06); transition: box-shadow .2s ease, transform .2s ease; }
        .soft-card:hover { box-shadow: 0 10px 30px rgba(0,0,0,0.10); transform: translateY(-2px); }
        .surface { background: rgba(255,255,255,.92); border: 1px solid rgba(229,229,229,.9); }
        .filter-select {
            appearance: none; -webkit-appearance: none; background: transparent;
            border: none; outline: none; font-size: .95rem; color: #222; cursor: pointer;
            font-weight: 600; width: 100%; padding-right: 1rem;
        }
        .line-clamp-2 {
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .filter-select:focus-visible { outline: 2px solid #01225E; outline-offset: 4px; border-radius: 6px; }
        .fade-in { animation: fadeUp .6s ease both; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
        #toast { transition: opacity .3s ease, transform .3s ease; }
        .adsense-placement:has(.adsbygoogle[data-ad-status="unfilled"]) { display: none; }
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible { outline: 2px solid #01225E; outline-offset: 3px; border-radius: 8px; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen text-neutral-900 bg-white">
    @php
        $isAdminPortal = auth()->check() && auth()->user()->is_super_admin && request()->routeIs('admin.*');
        $navLinkBase = 'inline-flex shrink-0 items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition';
        $navLinkIdle = 'border-neutral-200 bg-white text-neutral-900 hover:bg-neutral-50';
        $navLinkActive = 'border-neutral-950 bg-neutral-950 text-white shadow-sm';
        $studentNavItems = [
            [
                'label' => 'APS',
                'href' => route('course-match.index'),
                'icon' => 'target',
                'active' => request()->routeIs('aps.*') || request()->routeIs('aps-calculator.*') || request()->routeIs('course-match.*') || request()->routeIs('courses.*') || request()->routeIs('universities.*'),
            ],
            [
                'label' => 'Funding',
                'href' => route('funding.index'),
                'icon' => 'badge-dollar-sign',
                'active' => request()->routeIs('funding.*') || request()->routeIs('bursaries.*'),
            ],
            [
                'label' => 'Dashboard',
                'href' => route('dashboard.index'),
                'icon' => 'home',
                'active' => request()->routeIs('dashboard.index'),
            ],
            [
                'label' => 'Applications',
                'href' => route('applications.index'),
                'icon' => 'folder-check',
                'active' => request()->routeIs('applications.*'),
            ],
        ];
        $adminNavItems = [
            [
                'label' => 'Dashboard',
                'href' => route('admin.index'),
                'icon' => 'layout-dashboard',
                'active' => request()->routeIs('admin.index'),
            ],
            [
                'label' => 'Facebook',
                'href' => route('admin.facebook.index'),
                'icon' => 'messages-square',
                'active' => request()->routeIs('admin.facebook.*'),
            ],
            [
                'label' => 'Instagram',
                'href' => route('admin.instagram.index'),
                'icon' => 'camera',
                'active' => request()->routeIs('admin.instagram.*'),
            ],
            [
                'label' => 'Threads',
                'href' => route('admin.threads.index'),
                'icon' => 'at-sign',
                'active' => request()->routeIs('admin.threads.*'),
            ],
            [
                'label' => 'LinkedIn',
                'href' => route('admin.linkedin.index'),
                'icon' => 'briefcase',
                'active' => request()->routeIs('admin.linkedin.*'),
            ],
            [
                'label' => 'Activity',
                'href' => route('admin.activity-logs.index'),
                'icon' => 'activity',
                'active' => request()->routeIs('admin.activity-logs.*'),
            ],
            [
                'label' => 'Audit',
                'href' => route('admin.audit-logs.index'),
                'icon' => 'file-search',
                'active' => request()->routeIs('admin.audit-logs.*'),
            ],
            [
                'label' => 'Accounts',
                'href' => route('admin.accounts.index'),
                'icon' => 'users',
                'active' => request()->routeIs('admin.accounts.*'),
            ],
            [
                'label' => 'Visits',
                'href' => route('admin.site-visits.index'),
                'icon' => 'mouse-pointer-click',
                'active' => request()->routeIs('admin.site-visits.*'),
            ],
        ];
        $navItems = $isAdminPortal ? $adminNavItems : $studentNavItems;
    @endphp

    <header class="sticky top-0 z-40 w-full border-b border-neutral-200 bg-white/95 backdrop-blur">
        <nav class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-5 lg:px-8">
            <a href="{{ $isAdminPortal ? route('admin.index') : url('/') }}" class="flex shrink-0 items-center gap-2">
                <img src="{{ asset('images/brand/chamu-logo.png') }}" alt="Chamu logo" class="h-9 w-9 rounded-xl object-contain">
                <span class="font-bold text-lg">Chamu</span>
                @if ($isAdminPortal)
                    <span class="hidden rounded-full bg-[#F3F7FC] px-2.5 py-1 text-xs font-bold text-[#01225E] sm:inline-flex">Admin</span>
                @endif
            </a>

            <div class="no-scrollbar ml-auto flex min-w-0 items-center gap-2 overflow-x-auto whitespace-nowrap pb-1">
                @auth
                    @unless ($isAdminPortal)
                        <span class="hidden shrink-0 items-center rounded-full bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-700 sm:inline-flex">{{ auth()->user()->streak }} day streak</span>
                        <span class="hidden shrink-0 items-center rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 sm:inline-flex">{{ number_format(auth()->user()->points) }} pts</span>
                    @endunless

                    @foreach ($navItems as $item)
                        <a
                            href="{{ $item['href'] }}"
                            @class([$navLinkBase, $item['active'] ? $navLinkActive : $navLinkIdle])
                            @if ($item['active']) aria-current="page" @endif
                        >
                            <i data-lucide="{{ $item['icon'] }}" style="width:16px;height:16px;"></i>
                            {{ $item['label'] }}
                        </a>
                    @endforeach

                    @php
                        $profileActive = request()->routeIs('profile.*');
                    @endphp
                    @if (auth()->user()->is_super_admin && ! $isAdminPortal)
                        <a
                            href="{{ route('admin.index') }}"
                            @class([$navLinkBase, request()->routeIs('admin.*') ? $navLinkActive : $navLinkIdle])
                            @if (request()->routeIs('admin.*')) aria-current="page" @endif
                        >
                            <i data-lucide="shield-check" style="width:16px;height:16px;"></i>
                            Admin
                        </a>
                    @endif
                    <a
                        href="{{ route('profile.edit') }}"
                        @class([$navLinkBase, $profileActive ? $navLinkActive : $navLinkIdle])
                        @if ($profileActive) aria-current="page" @endif
                    >
                        <i data-lucide="user-cog" style="width:16px;height:16px;"></i>
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button class="{{ $navLinkBase }} {{ $navLinkIdle }}">
                            <i data-lucide="log-out" style="width:16px;height:16px;"></i>
                            Sign out
                        </button>
                    </form>
                @else
                    <a href="{{ route('aps.index') }}" class="{{ $navLinkBase }} {{ request()->routeIs('aps.*') || request()->routeIs('aps-calculator.*') ? $navLinkActive : $navLinkIdle }}" @if (request()->routeIs('aps.*') || request()->routeIs('aps-calculator.*')) aria-current="page" @endif>
                        <i data-lucide="target" style="width:16px;height:16px;"></i>
                        APS
                    </a>
                    <a href="{{ route('funding.index') }}" class="{{ $navLinkBase }} {{ request()->routeIs('funding.*') || request()->routeIs('bursaries.*') ? $navLinkActive : $navLinkIdle }}" @if (request()->routeIs('funding.*') || request()->routeIs('bursaries.*')) aria-current="page" @endif>
                        <i data-lucide="badge-dollar-sign" style="width:16px;height:16px;"></i>
                        Funding
                    </a>
                    <a href="{{ route('learn.index') }}" class="{{ $navLinkBase }} {{ request()->routeIs('learn.*') || request()->routeIs('content.*') ? $navLinkActive : $navLinkIdle }}" @if (request()->routeIs('learn.*') || request()->routeIs('content.*')) aria-current="page" @endif>
                        <i data-lucide="book-open" style="width:16px;height:16px;"></i>
                        Learn
                    </a>
                    <a href="{{ route('guides.index') }}" class="{{ $navLinkBase }} {{ request()->routeIs('guides.*') ? $navLinkActive : $navLinkIdle }}" @if (request()->routeIs('guides.*')) aria-current="page" @endif>
                        <i data-lucide="library" style="width:16px;height:16px;"></i>
                        Guides
                    </a>
                    <a href="{{ route('about') }}" class="{{ $navLinkBase }} {{ request()->routeIs('about') ? $navLinkActive : $navLinkIdle }}" @if (request()->routeIs('about')) aria-current="page" @endif>
                        <i data-lucide="info" style="width:16px;height:16px;"></i>
                        About
                    </a>
                    <a href="{{ route('login') }}" class="{{ $navLinkBase }} {{ request()->routeIs('login') ? $navLinkActive : $navLinkIdle }}" @if (request()->routeIs('login')) aria-current="page" @endif>
                        <i data-lucide="log-in" style="width:16px;height:16px;"></i>
                        Log in
                    </a>
                    <a href="{{ route('register') }}" class="{{ $navLinkBase }} {{ request()->routeIs('register') ? $navLinkActive : 'border-[#01225E] bg-[#01225E] text-white hover:bg-[#001A48]' }}" @if (request()->routeIs('register')) aria-current="page" @endif>
                        Sign up
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    @yield('content')

    <footer class="border-t border-neutral-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-6 px-5 py-8 text-sm text-neutral-600 sm:grid-cols-[1fr_auto] sm:items-center lg:px-8">
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 font-bold text-neutral-950">
                    <img src="{{ asset('images/brand/chamu-logo.png') }}" alt="Chamu logo" class="h-8 w-8 rounded-lg object-contain">
                    Chamu
                </a>
                <p class="mt-2 max-w-2xl leading-6">South African learner tools for APS planning, study practice, bursary discovery, and university requirement checks.</p>
            </div>
            <nav aria-label="Footer" class="flex flex-wrap gap-x-4 gap-y-2 font-semibold">
                <a href="{{ route('guides.index') }}" class="hover:text-neutral-950">Guides</a>
                <a href="{{ route('about') }}" class="hover:text-neutral-950">About</a>
                <a href="{{ route('contact') }}" class="hover:text-neutral-950">Contact</a>
                <a href="{{ route('privacy') }}" class="hover:text-neutral-950">Privacy</a>
                <a href="{{ route('terms') }}" class="hover:text-neutral-950">Terms</a>
            </nav>
        </div>
    </footer>

    <div id="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 opacity-0 pointer-events-none bg-neutral-900 text-white px-5 py-3 rounded-xl text-sm font-medium shadow-lg max-w-[90vw] text-center"></div>

    <script>
        if (window.lucide) {
            lucide.createIcons();
        }

        let toastTimer;
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.opacity = '1';
            toast.style.transform = 'translate(-50%, 0)';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translate(-50%, 8px)';
            }, 2600);
        }

        document.querySelectorAll('.js-btn').forEach((button) => {
            button.addEventListener('click', () => showToast(button.getAttribute('data-action') || 'Opening'));
        });
    </script>
    @stack('scripts')
</body>
</html>

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-18379876418"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'AW-18379876418');
    </script>
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
    <script type="text/javascript">
        var infolinks_pid = 3447035;
        var infolinks_wsid = 0;
    </script>
    <script type="text/javascript" src="//resources.infolinks.com/js/infolinks_main.js"></script>
    
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
        [data-site-menu] [data-site-menu-panel] {
            transform: translateX(100%);
            transition: transform .28s cubic-bezier(.22,1,.36,1);
        }
        [data-site-menu][data-open="true"] [data-site-menu-panel] {
            transform: translateX(0);
        }
        [data-site-menu] [data-site-menu-backdrop] {
            opacity: 0;
            pointer-events: none;
            transition: opacity .22s ease;
        }
        [data-site-menu][data-open="true"] [data-site-menu-backdrop] {
            opacity: 1;
            pointer-events: auto;
        }
        body.site-menu-open {
            overflow: hidden;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen text-neutral-900 bg-white">
    @php
        $isAdminPortal = auth()->check() && auth()->user()->is_super_admin && request()->routeIs('admin.*');
        $authUser = auth()->user();
        $tutorApplicationsReady = Illuminate\Support\Facades\Schema::hasTable('tutor_applications');
        $tutorApplication = ($authUser && $tutorApplicationsReady) ? $authUser->tutorApplication : null;
        $showBecomeTutor = $tutorApplicationsReady && ! $isAdminPortal && (
            ! auth()->check()
            || ! ($tutorApplication?->isSubmitted() ?? false)
        );
        $becomeTutorHref = ! auth()->check()
            ? route('register', ['type' => 'tutor'])
            : (
                ($tutorApplication?->isSubmitted() ?? false)
                    ? route('tutor.application.coming-soon')
                    : route('tutor.application.welcome')
            );
        $becomeTutorLabel = auth()->check() && $tutorApplication && $tutorApplication->isDraft() && filled($tutorApplication->headline)
            ? 'Continue Tutor App'
            : 'Become Tutor';

        $exploreNavItems = $isAdminPortal
            ? [
                ['label' => 'Dashboard', 'href' => route('admin.index'), 'icon' => 'layout-dashboard', 'active' => request()->routeIs('admin.index')],
                ['label' => 'Facebook', 'href' => route('admin.facebook.index'), 'icon' => 'messages-square', 'active' => request()->routeIs('admin.facebook.*')],
                ['label' => 'Instagram', 'href' => route('admin.instagram.index'), 'icon' => 'camera', 'active' => request()->routeIs('admin.instagram.*')],
                ['label' => 'Threads', 'href' => route('admin.threads.index'), 'icon' => 'at-sign', 'active' => request()->routeIs('admin.threads.*')],
                ['label' => 'LinkedIn', 'href' => route('admin.linkedin.index'), 'icon' => 'briefcase', 'active' => request()->routeIs('admin.linkedin.*')],
                ['label' => 'Activity', 'href' => route('admin.activity-logs.index'), 'icon' => 'activity', 'active' => request()->routeIs('admin.activity-logs.*')],
                ['label' => 'Audit', 'href' => route('admin.audit-logs.index'), 'icon' => 'file-search', 'active' => request()->routeIs('admin.audit-logs.*')],
                ['label' => 'Emails', 'href' => route('admin.emails.index'), 'icon' => 'mail-check', 'active' => request()->routeIs('admin.emails.*')],
                ['label' => 'Accounts', 'href' => route('admin.accounts.index'), 'icon' => 'users', 'active' => request()->routeIs('admin.accounts.*')],
                ['label' => 'Visits', 'href' => route('admin.site-visits.index'), 'icon' => 'mouse-pointer-click', 'active' => request()->routeIs('admin.site-visits.*')],
            ]
            : (
                auth()->check()
                    ? [
                        ['label' => 'Course', 'href' => route('aps.index'), 'icon' => 'target', 'active' => request()->routeIs('aps.*') || request()->routeIs('aps-calculator.*') || request()->routeIs('courses.*') || request()->routeIs('universities.*') || request()->routeIs('public.universities.*') || request()->routeIs('public.qualifications.*')],
                        ['label' => 'Funding', 'href' => route('funding.index'), 'icon' => 'badge-dollar-sign', 'active' => request()->routeIs('funding.*') || request()->routeIs('bursaries.*')],
                        ['label' => 'Lemo AI', 'href' => route('lemo-ai.index'), 'icon' => 'sparkles', 'active' => request()->routeIs('lemo-ai.*')],
                        ['label' => 'Dashboard', 'href' => route('dashboard.index'), 'icon' => 'home', 'active' => request()->routeIs('dashboard.index')],
                        ['label' => 'Applications', 'href' => route('applications.index'), 'icon' => 'folder-check', 'active' => request()->routeIs('applications.*')],
                    ]
                    : [
                        ['label' => 'Course', 'href' => route('aps.index'), 'icon' => 'target', 'active' => request()->routeIs('aps.*') || request()->routeIs('aps-calculator.*')],
                        ['label' => 'Funding', 'href' => route('funding.index'), 'icon' => 'badge-dollar-sign', 'active' => request()->routeIs('funding.*') || request()->routeIs('bursaries.*')],
                        ['label' => 'Lemo AI', 'href' => route('lemo-ai.index'), 'icon' => 'sparkles', 'active' => request()->routeIs('lemo-ai.*')],
                        ['label' => 'Guides', 'href' => route('guides.index'), 'icon' => 'library', 'active' => request()->routeIs('guides.*')],
                        ['label' => 'About', 'href' => route('about'), 'icon' => 'info', 'active' => request()->routeIs('about')],
                    ]
            );

        $accountNavItems = [];
        if (auth()->check() && ! $isAdminPortal) {
            if (auth()->user()->is_super_admin) {
                $accountNavItems[] = [
                    'label' => 'Admin',
                    'href' => route('admin.index'),
                    'icon' => 'shield-check',
                    'active' => request()->routeIs('admin.*'),
                ];
            }
            $accountNavItems[] = [
                'label' => 'Profile',
                'href' => route('profile.edit'),
                'icon' => 'user-cog',
                'active' => request()->routeIs('profile.*'),
            ];
        }
        if ($showBecomeTutor) {
            $accountNavItems[] = [
                'label' => $becomeTutorLabel,
                'href' => $becomeTutorHref,
                'icon' => 'presentation',
                'active' => request()->routeIs('tutor.application.*'),
                'emphasis' => true,
            ];
        }
    @endphp

    <div data-site-menu data-open="false">
        <header class="sticky top-0 z-40 w-full border-b border-neutral-200 bg-white/95 backdrop-blur">
            <nav class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-5 lg:px-8" aria-label="Primary">
                <a href="{{ $isAdminPortal ? route('admin.index') : url('/') }}" class="flex shrink-0 items-center gap-2">
                    <img src="{{ asset('images/brand/chamu-logo.png') }}" alt="Chamu logo" class="h-9 w-9 rounded-xl object-contain">
                    <span class="font-bold text-lg">Chamu</span>
                    @if ($isAdminPortal)
                        <span class="hidden rounded-full bg-[#F3F7FC] px-2.5 py-1 text-xs font-bold text-[#01225E] sm:inline-flex">Admin</span>
                    @endif
                </a>

                <div class="ml-auto flex items-center gap-2 sm:gap-3">
                    @auth
                        @unless ($isAdminPortal)
                            <span class="hidden items-center rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700 sm:inline-flex">{{ auth()->user()->streak }} day streak</span>
                            <span class="hidden items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 sm:inline-flex">{{ number_format(auth()->user()->points) }} pts</span>
                        @endunless
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm font-semibold text-neutral-900 hover:bg-neutral-50 sm:inline-flex">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="hidden rounded-xl bg-[#01225E] px-3 py-2 text-sm font-semibold text-white hover:bg-[#001A48] sm:inline-flex">
                            Sign up
                        </a>
                    @endauth

                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-neutral-200 bg-white text-neutral-900 shadow-sm transition hover:bg-neutral-50"
                        data-site-menu-toggle
                        aria-expanded="false"
                        aria-controls="site-menu-panel"
                        aria-label="Open menu"
                    >
                        <i data-lucide="menu" data-site-menu-icon-open style="width:20px;height:20px;"></i>
                        <i data-lucide="x" class="hidden" data-site-menu-icon-close style="width:20px;height:20px;"></i>
                    </button>
                </div>
            </nav>
        </header>

        <div class="fixed inset-0 z-50" data-site-menu-layer hidden>
            <button type="button" class="absolute inset-0 bg-neutral-950/45 backdrop-blur-[2px]" data-site-menu-backdrop data-site-menu-close aria-label="Close menu"></button>

            <aside
                id="site-menu-panel"
                class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl"
                data-site-menu-panel
                role="dialog"
                aria-modal="true"
                aria-label="Site menu"
            >
                <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-neutral-400">Menu</p>
                        @auth
                            <p class="mt-1 truncate text-base font-black text-neutral-950">{{ $authUser->first_name ?: $authUser->name }}</p>
                        @else
                            <p class="mt-1 text-base font-black text-neutral-950">Explore Chamu</p>
                        @endauth
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 text-neutral-700 hover:bg-neutral-50"
                        data-site-menu-close
                        aria-label="Close menu"
                    >
                        <i data-lucide="x" style="width:18px;height:18px;"></i>
                    </button>
                </div>

                @auth
                    @unless ($isAdminPortal)
                        <div class="flex gap-2 border-b border-neutral-100 px-5 py-3">
                            <span class="inline-flex items-center rounded-full bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700">{{ auth()->user()->streak }} day streak</span>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">{{ number_format(auth()->user()->points) }} pts</span>
                        </div>
                    @endunless
                @endauth

                <div class="flex-1 overflow-y-auto px-3 py-4">
                    <p class="px-2 pb-2 text-[11px] font-black uppercase tracking-[0.16em] text-neutral-400">
                        {{ $isAdminPortal ? 'Admin' : 'Explore' }}
                    </p>
                    <div class="space-y-1">
                        @foreach ($exploreNavItems as $item)
                            <a
                                href="{{ $item['href'] }}"
                                @class([
                                    'flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold transition',
                                    'bg-neutral-950 text-white' => $item['active'],
                                    'text-neutral-800 hover:bg-neutral-50' => ! $item['active'],
                                ])
                                @if ($item['active']) aria-current="page" @endif
                            >
                                <span @class([
                                    'grid h-9 w-9 place-items-center rounded-lg',
                                    'bg-white/10 text-white' => $item['active'],
                                    'bg-neutral-100 text-[#01225E]' => ! $item['active'],
                                ])>
                                    <i data-lucide="{{ $item['icon'] }}" style="width:16px;height:16px;"></i>
                                </span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>

                    @if ($accountNavItems !== [])
                        <p class="mt-6 px-2 pb-2 text-[11px] font-black uppercase tracking-[0.16em] text-neutral-400">Account</p>
                        <div class="space-y-1">
                            @foreach ($accountNavItems as $item)
                                <a
                                    href="{{ $item['href'] }}"
                                    @class([
                                        'flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold transition',
                                        'bg-[#01225E] text-white' => ! empty($item['emphasis']) && ! $item['active'],
                                        'bg-neutral-950 text-white' => $item['active'],
                                        'text-neutral-800 hover:bg-neutral-50' => empty($item['emphasis']) && ! $item['active'],
                                    ])
                                    @if ($item['active']) aria-current="page" @endif
                                >
                                    <span @class([
                                        'grid h-9 w-9 place-items-center rounded-lg',
                                        'bg-white/10 text-white' => $item['active'] || ! empty($item['emphasis']),
                                        'bg-neutral-100 text-[#01225E]' => ! $item['active'] && empty($item['emphasis']),
                                    ])>
                                        <i data-lucide="{{ $item['icon'] }}" style="width:16px;height:16px;"></i>
                                    </span>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="border-t border-neutral-200 p-4">
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm font-bold text-neutral-800 hover:bg-neutral-50">
                                <i data-lucide="log-out" style="width:16px;height:16px;"></i>
                                Sign out
                            </button>
                        </form>
                    @else
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm font-bold text-neutral-800 hover:bg-neutral-50">
                                <i data-lucide="log-in" style="width:16px;height:16px;"></i>
                                Login
                            </a>
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-[#01225E] px-4 py-3 text-sm font-bold text-white hover:bg-[#001A48]">
                                Sign up
                            </a>
                        </div>
                    @endauth
                </div>
            </aside>
        </div>
    </div>

    @yield('content')

    @hasSection('hide_footer')
    @else
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
    @endif

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

        (() => {
            const root = document.querySelector('[data-site-menu]');
            if (! root) return;

            const layer = root.querySelector('[data-site-menu-layer]');
            const toggle = root.querySelector('[data-site-menu-toggle]');
            const openIcon = root.querySelector('[data-site-menu-icon-open]');
            const closeIcon = root.querySelector('[data-site-menu-icon-close]');
            const closeButtons = Array.from(root.querySelectorAll('[data-site-menu-close]'));

            const setOpen = (open) => {
                document.body.classList.toggle('site-menu-open', open);
                toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle?.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
                openIcon?.classList.toggle('hidden', open);
                closeIcon?.classList.toggle('hidden', ! open);

                if (open) {
                    layer?.removeAttribute('hidden');
                    void layer?.offsetWidth;
                    root.dataset.open = 'true';
                    return;
                }

                root.dataset.open = 'false';
                window.setTimeout(() => {
                    if (root.dataset.open !== 'true') {
                        layer?.setAttribute('hidden', '');
                    }
                }, 280);
            };

            toggle?.addEventListener('click', () => {
                setOpen(root.dataset.open !== 'true');
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', () => setOpen(false));
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && root.dataset.open === 'true') {
                    setOpen(false);
                }
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>

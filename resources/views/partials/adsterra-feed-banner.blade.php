@php
    $feed = config('adsterra.feed', []);
    $banners = adsterra_banners();
    $mobileSize = $feed['mobile'] ?? '320x50';
    $tabletSize = $feed['tablet'] ?? '468x60';
    $desktopSize = $feed['desktop'] ?? '728x90';
    $tabletBreakpoint = (int) ($feed['breakpoints']['tablet'] ?? 640);
    $desktopBreakpoint = (int) ($feed['breakpoints']['desktop'] ?? 1024);
@endphp

<aside class="adsterra-placement overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50/80 {{ $class ?? '' }}" aria-label="Advertisement" data-adsterra-feed>
    <div class="flex min-h-[50px] items-center justify-center px-3 py-3 sm:min-h-[60px] sm:px-4 lg:min-h-[90px]">
        <div data-adsterra-mount class="flex max-w-full items-center justify-center overflow-hidden"></div>
    </div>
</aside>

@once('adsterra-feed-script')
    @push('scripts')
        <script>
            (() => {
                const banners = @json($banners);
                const mobileSize = @json($mobileSize);
                const tabletSize = @json($tabletSize);
                const desktopSize = @json($desktopSize);
                const tabletBreakpoint = @json($tabletBreakpoint);
                const desktopBreakpoint = @json($desktopBreakpoint);

                const pickSize = () => {
                    const width = window.innerWidth || document.documentElement.clientWidth || 0;
                    if (width >= desktopBreakpoint) return desktopSize;
                    if (width >= tabletBreakpoint) return tabletSize;
                    return mobileSize;
                };

                const mountBanner = (slot) => new Promise((resolve) => {
                    if (!slot || slot.dataset.adsterraMounted === '1') {
                        resolve();
                        return;
                    }

                    const mount = slot.querySelector('[data-adsterra-mount]');
                    const size = pickSize();
                    const banner = banners[size];
                    if (!mount || !banner || !banner.key) {
                        resolve();
                        return;
                    }

                    slot.dataset.adsterraMounted = '1';
                    mount.replaceChildren();

                    window.atOptions = {
                        key: banner.key,
                        format: banner.format || 'iframe',
                        height: Number(banner.height) || 0,
                        width: Number(banner.width) || 0,
                        params: {},
                    };

                    const script = document.createElement('script');
                    script.src = `https://www.highperformanceformat.com/${banner.key}/invoke.js`;
                    script.onload = () => resolve();
                    script.onerror = () => resolve();
                    mount.appendChild(script);
                });

                const mountAll = async () => {
                    const slots = [...document.querySelectorAll('[data-adsterra-feed]')];
                    for (const slot of slots) {
                        await mountBanner(slot);
                    }
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', mountAll, { once: true });
                } else {
                    mountAll();
                }
            })();
        </script>
    @endpush
@endonce

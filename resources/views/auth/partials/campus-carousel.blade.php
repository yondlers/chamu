@php
    $campusImages = [
        'images/auth-campus/rhodes-uni.jpg',
        'images/auth-campus/ukzn3.png',
        'images/auth-campus/campus-green.jpg',
        'images/auth-campus/uj-apk-campus.jpg',
        'images/auth-campus/wits-great-hall.png',
        'images/auth-campus/university-of-cape-town.jpg',
        'images/auth-campus/humanities.jpg',
    ];
@endphp

<section class="auth-campus-carousel relative hidden min-h-screen overflow-hidden border-r border-neutral-200 bg-neutral-950 lg:block" data-auth-campus-carousel>
    @foreach ($campusImages as $index => $image)
        <img
            src="{{ asset($image) }}"
            alt=""
            aria-hidden="true"
            @class([
                'auth-campus-slide absolute inset-0 h-full w-full object-cover',
                'is-active' => $index === 0,
            ])
            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
        >
    @endforeach

    <div class="absolute inset-0 bg-gradient-to-b from-black/55 via-black/20 to-black/65"></div>
    <div class="absolute inset-0 bg-[#01225E]/15"></div>

    <div class="relative z-10 flex min-h-screen flex-col justify-between p-10 text-white">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/brand/chamu-logo.png') }}" alt="Chamu logo" class="h-10 w-10 rounded-xl bg-white object-contain">
            <span class="font-bold text-xl">Chamu</span>
        </a>

        <div class="max-w-xl">
            <p class="mb-3 text-sm font-semibold uppercase text-white/75">{{ $eyebrow }}</p>
            <h1 class="text-5xl font-bold tracking-normal text-white">{{ $heading }}</h1>
            <p class="mt-4 text-lg text-white/80">{{ $copy }}</p>
        </div>

        <p class="text-sm font-semibold text-white/70">Built for South African Grade 10-12 learners.</p>
    </div>
</section>

@once
    @push('styles')
        <style>
            .auth-campus-slide {
                opacity: 0;
                transform: scale(1.03);
                transition: opacity 900ms ease, transform 6800ms ease;
            }

            .auth-campus-slide.is-active {
                opacity: 1;
                transform: scale(1);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.querySelectorAll('[data-auth-campus-carousel]').forEach((carousel) => {
                const slides = Array.from(carousel.querySelectorAll('.auth-campus-slide'));
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (slides.length < 2 || reduceMotion) {
                    return;
                }

                let activeIndex = 0;

                window.setInterval(() => {
                    slides[activeIndex].classList.remove('is-active');
                    activeIndex = (activeIndex + 1) % slides.length;
                    slides[activeIndex].classList.add('is-active');
                }, 5200);
            });
        </script>
    @endpush
@endonce

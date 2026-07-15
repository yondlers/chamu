




<?php
    $locations = \App\Models\Suburb::get();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <?php if (isset($component)) { $__componentOriginal1a590bee94ab2d9c08b342367154fca0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1a590bee94ab2d9c08b342367154fca0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.authentication-card-logo','data' => ['class' => 'block h-[23px] w-auto']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('authentication-card-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-[23px] w-auto']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1a590bee94ab2d9c08b342367154fca0)): ?>
<?php $attributes = $__attributesOriginal1a590bee94ab2d9c08b342367154fca0; ?>
<?php unset($__attributesOriginal1a590bee94ab2d9c08b342367154fca0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1a590bee94ab2d9c08b342367154fca0)): ?>
<?php $component = $__componentOriginal1a590bee94ab2d9c08b342367154fca0; ?>
<?php unset($__componentOriginal1a590bee94ab2d9c08b342367154fca0); ?>
<?php endif; ?>
                    <span class="text-2xl font-bold text-orange-400">Chamu</span>
                </div>

                <!-- Search Bar -->
                <form method="POST" action="<?php echo e(route('search')); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('GET'); ?>
                    <div class=" flex items-center ">

                        <div class="">
                            <select id="search" name="search[]" multiple placeholder="Search Location">
                                <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($location->id); ?>"><?php echo e($location->location); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <button type="submit" class="p-2 m-2 bg-orange-400 text-white rounded-full hover:bg-orange-400 transition-colors">
                            Search
                        </button>

                    </div>
                </form>

                <div class="flex items-center space-x-4">
                    <?php if(auth()->guard()->check()): ?>
                        <a href="<?php echo e(url('/dashboard')); ?>" class="bg-orange-400 text-white px-4 py-2 rounded-full hover:bg-orange-400 transition-colors">Dashboard</a>
                    <?php else: ?>
                        <a href="<?php echo e(route('login')); ?>" class="text-gray-700 hover:bg-orange-400 transition-colors">Log in</a>
                        <?php if(Route::has('register')): ?>
                            <a href="<?php echo e(route('register')); ?>" class="bg-orange-400 text-white px-4 py-2 rounded-full hover:bg-orange-400 transition-colors">Sign Up</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </header>

    <!-- Hero Section with Search -->
    <section class="bg-gradient-to-r from-orange-500 to-orange-600 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-5xl font-bold mb-6">Find Your Dream Home</h2>



















        </div>
    </section>

    <!-- Listings Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h3 class="text-3xl font-bold text-gray-900 mb-8">Popular destinations</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Listing 1 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer">
                <div class="h-48 bg-gradient-to-br from-blue-400 to-blue-600 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-white text-6xl">🏖️</span>
                    </div>
                    <div class="absolute top-3 right-3 bg-white rounded-full p-2 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 mb-1">Beachfront Villa</h4>
                    <p class="text-gray-600 text-sm mb-2">Malibu, California</p>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <span class="text-gray-600 text-sm ml-2">4.9 (127)</span>
                    </div>
                    <p class="text-gray-900 font-semibold">$450 <span class="font-normal text-gray-600">night</span></p>
                </div>
            </div>

            <!-- Listing 2 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer">
                <div class="h-48 bg-gradient-to-br from-green-400 to-green-600 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-white text-6xl">🏔️</span>
                    </div>
                    <div class="absolute top-3 right-3 bg-white rounded-full p-2 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 mb-1">Mountain Cabin</h4>
                    <p class="text-gray-600 text-sm mb-2">Aspen, Colorado</p>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <span class="text-gray-600 text-sm ml-2">4.8 (89)</span>
                    </div>
                    <p class="text-gray-900 font-semibold">$320 <span class="font-normal text-gray-600">night</span></p>
                </div>
            </div>

            <!-- Listing 3 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer">
                <div class="h-48 bg-gradient-to-br from-purple-400 to-purple-600 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-white text-6xl">🏙️</span>
                    </div>
                    <div class="absolute top-3 right-3 bg-white rounded-full p-2 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 mb-1">Downtown Loft</h4>
                    <p class="text-gray-600 text-sm mb-2">New York, NY</p>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <span class="text-gray-600 text-sm ml-2">4.7 (203)</span>
                    </div>
                    <p class="text-gray-900 font-semibold">$280 <span class="font-normal text-gray-600">night</span></p>
                </div>
            </div>

            <!-- Listing 4 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer">
                <div class="h-48 bg-gradient-to-br from-orange-400 to-orange-600 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-white text-6xl">🏡</span>
                    </div>
                    <div class="absolute top-3 right-3 bg-white rounded-full p-2 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 mb-1">Cozy Cottage</h4>
                    <p class="text-gray-600 text-sm mb-2">Napa Valley, CA</p>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <span class="text-gray-600 text-sm ml-2">4.9 (156)</span>
                    </div>
                    <p class="text-gray-900 font-semibold">$380 <span class="font-normal text-gray-600">night</span></p>
                </div>
            </div>

            <!-- Listing 5 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer">
                <div class="h-48 bg-gradient-to-br from-teal-400 to-teal-600 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-white text-6xl">🏝️</span>
                    </div>
                    <div class="absolute top-3 right-3 bg-white rounded-full p-2 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 mb-1">Island Retreat</h4>
                    <p class="text-gray-600 text-sm mb-2">Maui, Hawaii</p>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <span class="text-gray-600 text-sm ml-2">5.0 (78)</span>
                    </div>
                    <p class="text-gray-900 font-semibold">$520 <span class="font-normal text-gray-600">night</span></p>
                </div>
            </div>

            <!-- Listing 6 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer">
                <div class="h-48 bg-gradient-to-br from-red-400 to-red-600 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-white text-6xl">🏰</span>
                    </div>
                    <div class="absolute top-3 right-3 bg-white rounded-full p-2 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 mb-1">Historic Castle</h4>
                    <p class="text-gray-600 text-sm mb-2">Edinburgh, Scotland</p>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <span class="text-gray-600 text-sm ml-2">4.6 (94)</span>
                    </div>
                    <p class="text-gray-900 font-semibold">$680 <span class="font-normal text-gray-600">night</span></p>
                </div>
            </div>

            <!-- Listing 7 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer">
                <div class="h-48 bg-gradient-to-br from-indigo-400 to-indigo-600 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-white text-6xl">🏕️</span>
                    </div>
                    <div class="absolute top-3 right-3 bg-white rounded-full p-2 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 mb-1">Glamping Site</h4>
                    <p class="text-gray-600 text-sm mb-2">Yellowstone, Montana</p>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <span class="text-gray-600 text-sm ml-2">4.8 (112)</span>
                    </div>
                    <p class="text-gray-900 font-semibold">$180 <span class="font-normal text-gray-600">night</span></p>
                </div>
            </div>

            <!-- Listing 8 -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer">
                <div class="h-48 bg-gradient-to-br from-pink-400 to-pink-600 relative">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-white text-6xl">🏖️</span>
                    </div>
                    <div class="absolute top-3 right-3 bg-white rounded-full p-2 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 mb-1">Tropical Bungalow</h4>
                    <p class="text-gray-600 text-sm mb-2">Bali, Indonesia</p>
                    <div class="flex items-center mb-2">
                        <div class="flex text-yellow-400">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <span class="text-gray-600 text-sm ml-2">4.9 (167)</span>
                    </div>
                    <p class="text-gray-900 font-semibold">$220 <span class="font-normal text-gray-600">night</span></p>
                </div>
            </div>
        </div>

        <!-- Load More Button -->
        <div class="text-center mt-12">
            <button class="bg-gray-900 text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition-colors font-semibold">
                Show more stays
            </button>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-100 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class=" border-gray-300 pt-8 flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-4 text-sm text-airbnb-gray">
                    <span>© 2025 Chamu</span>
                </div>
                <div class="flex items-center space-x-4 mt-4 md:mt-0">
                    <button class="flex items-center space-x-2 text-sm text-airbnb-gray hover:text-airbnb-dark">
                        <span>🌐</span>
                        <span>English (RSA)</span>
                    </button>
                    <button class="flex items-center space-x-2 text-sm text-airbnb-gray hover:text-airbnb-dark">
                        <span>R</span>
                        <span>ZAR</span>
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script>
        new TomSelect('#search', {
            plugins: ['remove_button'],
            maxItems: null,         // unlimited
            persist: false,
            closeAfterSelect: false,
            allowEmptyOption: true,
            sortField: { field: 'text', direction: 'asc' },
        });
    </script>

    <script>
        // Fixed search bar functionality
        window.addEventListener('scroll', function() {
            const searchBar = document.getElementById('searchBar');
            const scrollPosition = window.scrollY;

            if (scrollPosition > 300) {
                searchBar.style.opacity = '1';
                searchBar.style.transform = 'translateX(-50%) translateY(0)';
            } else {
                searchBar.style.opacity = '0';
                searchBar.style.transform = 'translateX(-50%) translateY(-20px)';
            }
        });

        // Make all search functionality work
        // const searchButtons = document.querySelectorAll('button');
        // searchButtons.forEach(button => {
        //     if (button.textContent.includes('Search')) {
        //         button.addEventListener('click', function() {
        //             alert('Search functionality activated! In a real app, this would filter the listings based on your criteria.');
        //         });
        //     }
        // });

        // Make listing cards clickable
        const listingCards = document.querySelectorAll('.cursor-pointer');
        listingCards.forEach(card => {
            card.addEventListener('click', function() {
                const title = card.querySelector('h4').textContent;
                alert(`Opening details for: ${title}`);
            });
        });

        // Make heart icons work
        const heartButtons = document.querySelectorAll('svg');
        heartButtons.forEach(heart => {
            if (heart.querySelector('path[d*="4.318"]')) {
                heart.parentElement.addEventListener('click', function(e) {
                    e.stopPropagation();
                    heart.classList.toggle('text-rose-500');
                    heart.classList.toggle('fill-current');
                });
            }
        });

        // Make load more button work
        const loadMoreBtn = document.querySelector('button');
        if (loadMoreBtn && loadMoreBtn.textContent.includes('Show more')) {
            loadMoreBtn.addEventListener('click', function() {
                alert('Loading more amazing stays for you!');
            });
        }
    </script>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'960bf96222720d06',t:'MTc1Mjc3ODc0My4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>
<?php /**PATH /Users/slx/Code/chamu2/resources/views/welcome.blade.php ENDPATH**/ ?>
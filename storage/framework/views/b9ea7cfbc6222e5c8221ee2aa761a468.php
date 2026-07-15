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
                        colors: {
                            'airbnb-red': '#FF385C',
                            'airbnb-dark': '#222222',
                            'airbnb-gray': '#717171',
                        }
                    }
                }
            }
        </script>
        <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">

        <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>">
    </head>
    <body class="bg-white">

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
                    <form method="GET" action="<?php echo e(route('search')); ?>">
                        <?php echo csrf_field(); ?>
                        <div class=" flex items-center ">

                            <div class="">
                                <select id="search" name="search[]" multiple required>
                                    <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($location->id); ?>"
                                            <?php echo e(in_array($location->id, $search ?? []) ? 'selected' : ''); ?>>
                                            <?php echo e($location->location); ?>

                                        </option>
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


        <!-- Search Results Section -->
        <section id="searchResults" class="">
            <!-- Filters Bar -->












































            <!-- Filter Dropdowns -->
            <div id="filterDropdowns" class="relative">
                <!-- Price Filter -->
                <div id="priceFilter" class="hidden absolute top-0 left-4 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-200 p-6 z-50">
                    <h3 class="font-medium text-airbnb-dark mb-4">Price range</h3>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1">
                                <label class="block text-sm text-airbnb-gray mb-1">Minimum</label>
                                <input type="number" placeholder="$0" class="w-full p-3 border border-gray-300 rounded-lg">
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm text-airbnb-gray mb-1">Maximum</label>
                                <input type="number" placeholder="$1000+" class="w-full p-3 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button class="px-4 py-2 text-sm font-medium text-airbnb-dark hover:bg-gray-100 rounded-lg">Clear</button>
                            <button class="px-4 py-2 text-sm font-medium bg-airbnb-dark text-white rounded-lg hover:bg-gray-800">Save</button>
                        </div>
                    </div>
                </div>

                <!-- Type Filter -->
                <div id="typeFilter" class="hidden absolute top-0 left-32 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-200 p-6 z-50">
                    <h3 class="font-medium text-airbnb-dark mb-4">Type of place</h3>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-3 rounded">
                            <span>Entire place</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-3 rounded">
                            <span>Private room</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-3 rounded">
                            <span>Shared room</span>
                        </label>
                    </div>
                </div>

                <!-- Amenities Filter -->
                <div id="amenitiesFilter" class="hidden absolute top-0 left-64 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-200 p-6 z-50">
                    <h3 class="font-medium text-airbnb-dark mb-4">Amenities</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 rounded">
                            <span class="text-sm">WiFi</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 rounded">
                            <span class="text-sm">Kitchen</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 rounded">
                            <span class="text-sm">Pool</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 rounded">
                            <span class="text-sm">Parking</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 rounded">
                            <span class="text-sm">AC</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-2 rounded">
                            <span class="text-sm">Hot tub</span>
                        </label>
                    </div>
                </div>
            </div>
            <!-- Fixed Search Bar -->
            <div id="searchBar" class="fixed top-20 left-1/2 transform -translate-x-1/2 z-50 transition-all duration-300 opacity-0">
                <div class="bg-white rounded-full shadow-lg border p-2 flex items-center space-x-4 min-w-96">
                    <div class="flex-1">
                        <input type="text" placeholder="Search" class="w-full px-4 py-2 rounded-full border-0 focus:outline-none text-sm">
                    </div>
                    <button class="bg-rose-500 text-white p-2 rounded-full hover:bg-rose-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Results Header -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-airbnb-dark">Results</h1>
                        <p class="text-airbnb-gray mt-1">We found <?php echo e($listings->count()); ?> Home</p>
                    </div>






                </div>
            </div>

            <!-- Main Content Area nemo-->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex">
                    <!-- Property Listings -->
                    <div id="propertyList" class="flex-1 pr-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                            <?php $__currentLoopData = $listings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $listing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                <!-- Search Result Property  -->
                                <a href="<?php echo e(route('place', ['listing_id' => $listing->id, 'search' => implode(',', (array) $search)])); ?>"
                                   class="cursor-pointer group border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                                    <div class="relative">
                                        <div class="w-full  bg-gradient-to-br from-blue-400 to-blue-600">
                                            <div class="w-full h-full flex items-center justify-center text-white text-4xl">
                                                <img src="<?php echo e(asset('storage/'.$listing->listingPictures[0]->image_1)); ?>" />
                                            </div>
                                        </div>
                                        <button class="absolute top-3 right-3 p-2 hover:scale-110 transition-transform">
                                            <svg class="w-6 h-6 text-white hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="p-4 space-y-2">
                                        <div class="flex justify-between items-start">
                                            <h3 class="font-medium text-airbnb-dark"><?php echo e($listing->title); ?></h3>
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 text-airbnb-dark mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                </svg>
                                                <span class="text-sm text-airbnb-dark">4.9</span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-airbnb-gray">Entire home · <?php echo e($listing->unit->assetInformation->number_of_bedrooms); ?> bedrooms · <?php echo e($listing->unit->assetInformation->number_of_bathrooms); ?> bathrooms</p>
                                        <p class="text-sm text-airbnb-gray"><?php echo e($listing->unit->assetInformation->has_balcony == 1 ? 'Balcony · ' : ''); ?><?php echo e($listing->unit->assetInformation->has_pool == 1 ? 'Pool · ' : ''); ?><?php echo e($listing->unit->assetInformation->is_furnished == 1 ? 'Furnished · ' : 'Not Furnished · '); ?><?php echo e($listing->unit->assetInformation->is_pet_friendly == 1 ? 'Pet Friendly · ' : 'Not Pet Friendly · '); ?></p>
                                        <p class="font-medium text-airbnb-dark">R <?php echo e($listing->unit->monthly_rent); ?></p>
                                    </div>
                                </a>

                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>



                        </div>
                    </div>

                    <!-- Map -->
                    <div id="mapContainer" class="hidden w-1/2 sticky top-32 h-screen">
                        <div class="w-full h-full bg-gray-100 rounded-xl overflow-hidden relative">
                            <!-- Map Background -->
                            <div class="w-full h-full bg-gradient-to-br from-green-200 to-blue-200 relative">
                                <!-- Map Markers -->
                                <div class="absolute top-1/4 left-1/3 transform -translate-x-1/2 -translate-y-1/2">
                                    <div class="map-marker bg-airbnb-red text-white px-3 py-1 rounded-full text-sm font-medium shadow-lg cursor-pointer hover:scale-110 transition-transform" data-price="$450">
                                        $450
                                    </div>
                                </div>
                                <div class="absolute top-1/2 left-1/4 transform -translate-x-1/2 -translate-y-1/2">
                                    <div class="map-marker bg-airbnb-red text-white px-3 py-1 rounded-full text-sm font-medium shadow-lg cursor-pointer hover:scale-110 transition-transform" data-price="$180">
                                        $180
                                    </div>
                                </div>
                                <div class="absolute top-1/3 left-2/3 transform -translate-x-1/2 -translate-y-1/2">
                                    <div class="map-marker bg-airbnb-red text-white px-3 py-1 rounded-full text-sm font-medium shadow-lg cursor-pointer hover:scale-110 transition-transform" data-price="$220">
                                        $220
                                    </div>
                                </div>
                                <div class="absolute top-2/3 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                    <div class="map-marker bg-airbnb-red text-white px-3 py-1 rounded-full text-sm font-medium shadow-lg cursor-pointer hover:scale-110 transition-transform" data-price="$95">
                                        $95
                                    </div>
                                </div>

                                <!-- Map Controls -->
                                <div class="absolute top-4 right-4 flex flex-col space-y-2">
                                    <button class="w-10 h-10 bg-white rounded-lg shadow-md flex items-center justify-center hover:bg-gray-50">
                                        <span class="text-lg font-bold">+</span>
                                    </button>
                                    <button class="w-10 h-10 bg-white rounded-lg shadow-md flex items-center justify-center hover:bg-gray-50">
                                        <span class="text-lg font-bold">-</span>
                                    </button>
                                </div>

                                <!-- Map Legend -->
                                <div class="absolute bottom-4 left-4 bg-white rounded-lg shadow-md p-3">
                                    <div class="text-xs text-airbnb-gray mb-1">Paris, France</div>
                                    <div class="text-sm font-medium text-airbnb-dark">300+ stays</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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


        <script>
            // Add interactive functionality
            document.addEventListener('DOMContentLoaded', function() {
                // Search state
                let searchData = {
                    location: 'Anywhere',
                    checkin: '',
                    checkout: '',
                    adults: 2,
                    children: 0,
                    infants: 0
                };

                // Get elements
                const locationBtn = document.getElementById('locationBtn');
                const dateBtn = document.getElementById('dateBtn');
                const guestBtn = document.getElementById('guestBtn');
                const searchBtn = document.getElementById('searchBtn');

                const locationDropdown = document.getElementById('locationDropdown');
                const dateDropdown = document.getElementById('dateDropdown');
                const guestDropdown = document.getElementById('guestDropdown');

                // Toggle dropdowns
                function hideAllDropdowns() {
                    locationDropdown.classList.add('hidden');
                    dateDropdown.classList.add('hidden');
                    guestDropdown.classList.add('hidden');
                }

                locationBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    hideAllDropdowns();
                    locationDropdown.classList.toggle('hidden');
                });

                dateBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    hideAllDropdowns();
                    dateDropdown.classList.toggle('hidden');
                });

                guestBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    hideAllDropdowns();
                    guestDropdown.classList.toggle('hidden');
                });

                // Close dropdowns when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.relative')) {
                        hideAllDropdowns();
                    }
                });

                // Location selection
                const locationOptions = document.querySelectorAll('.location-option');
                locationOptions.forEach(option => {
                    option.addEventListener('click', function() {
                        const location = this.getAttribute('data-location');
                        searchData.location = location;
                        document.getElementById('locationText').textContent = location;
                        hideAllDropdowns();
                    });
                });

                // Location input search
                const locationInput = document.getElementById('locationInput');
                locationInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    locationOptions.forEach(option => {
                        const text = option.textContent.toLowerCase();
                        if (text.includes(query)) {
                            //option.style.display = 'block';
                        } else {
                            //option.style.display = 'none';
                        }
                    });
                });

                // Date selection
                const checkinDate = document.getElementById('checkinDate');
                const checkoutDate = document.getElementById('checkoutDate');

                checkinDate.addEventListener('change', function() {
                    searchData.checkin = this.value;
                    updateDateText();
                });

                checkoutDate.addEventListener('change', function() {
                    searchData.checkout = this.value;
                    updateDateText();
                });

                function updateDateText() {
                    if (searchData.checkin && searchData.checkout) {
                        const checkin = new Date(searchData.checkin);
                        const checkout = new Date(searchData.checkout);
                        const nights = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
                        document.getElementById('dateText').textContent = `${nights} nights`;
                    } else if (searchData.checkin) {
                        document.getElementById('dateText').textContent = 'Add checkout';
                    } else {
                        document.getElementById('dateText').textContent = 'Any week';
                    }
                }

                // Date presets
                const datePresets = document.querySelectorAll('.date-preset');
                datePresets.forEach(preset => {
                    preset.addEventListener('click', function() {
                        const days = parseInt(this.getAttribute('data-days'));
                        const today = new Date();
                        const checkin = new Date(today.getTime() + (7 * 24 * 60 * 60 * 1000)); // 1 week from now
                        const checkout = new Date(checkin.getTime() + (days * 24 * 60 * 60 * 1000));

                        checkinDate.value = checkin.toISOString().split('T')[0];
                        checkoutDate.value = checkout.toISOString().split('T')[0];
                        searchData.checkin = checkinDate.value;
                        searchData.checkout = checkoutDate.value;
                        updateDateText();
                    });
                });

                // Guest selection
                const guestButtons = document.querySelectorAll('.guest-btn');
                guestButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const action = this.getAttribute('data-action');
                        const type = this.getAttribute('data-type');

                        if (action === 'increase') {
                            searchData[type]++;
                        } else if (action === 'decrease' && searchData[type] > 0) {
                            if (type === 'adults' && searchData[type] === 1) return; // At least 1 adult
                            searchData[type]--;
                        }

                        document.getElementById(type + 'Count').textContent = searchData[type];
                        updateGuestText();
                    });
                });

                function updateGuestText() {
                    const total = searchData.adults + searchData.children;
                    let text = '';

                    if (total === 1) {
                        text = '1 guest';
                    } else if (total > 1) {
                        text = `${total} guests`;
                    } else {
                        text = 'Add guests';
                    }

                    if (searchData.infants > 0) {
                        text += `, ${searchData.infants} infant${searchData.infants > 1 ? 's' : ''}`;
                    }

                    document.getElementById('guestText').textContent = text || 'Add guests';
                }

                // Search functionality
                searchBtn.addEventListener('click', function() {
                    hideAllDropdowns();

                    // Show search results page
                    showSearchResults();
                });

                // Map toggle functionality
                const mapToggle = document.getElementById('mapToggle');
                const mapContainer = document.getElementById('mapContainer');
                const propertyList = document.getElementById('propertyList');
                const mapToggleText = document.getElementById('mapToggleText');

                mapToggle.addEventListener('click', function() {
                    if (mapContainer.classList.contains('hidden')) {
                        // Show map
                        mapContainer.classList.remove('hidden');
                        propertyList.classList.remove('flex-1');
                        propertyList.classList.add('w-1/2');
                        mapToggleText.textContent = 'Hide map';
                    } else {
                        // Hide map
                        mapContainer.classList.add('hidden');
                        propertyList.classList.remove('w-1/2');
                        propertyList.classList.add('flex-1');
                        mapToggleText.textContent = 'Show map';
                    }
                });

                // Map marker interactions
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('map-marker')) {
                        const price = e.target.getAttribute('data-price');
                        // alert(`Property details for ${price}/night would open here!`);
                    }
                });

                // Property card interactions
                const propertyCards = document.querySelectorAll('.cursor-pointer.group');
                propertyCards.forEach(card => {
                    card.addEventListener('click', function(e) {
                        if (!e.target.closest('button')) {
                            // alert('Property details would open here!');
                        }
                    });
                });

                // Heart button functionality
                const heartButtons = document.querySelectorAll('button svg[viewBox="0 0 24 24"]');
                heartButtons.forEach(button => {
                    if (button.querySelector('path[d*="4.318"]')) {
                        button.parentElement.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const svg = this.querySelector('svg');
                            if (svg.classList.contains('text-red-500')) {
                                svg.classList.remove('text-red-500');
                                svg.classList.add('text-white');
                            } else {
                                svg.classList.remove('text-white');
                                svg.classList.add('text-red-500');
                            }
                        });
                    }
                });

                // Category filters
                const categoryItems = document.querySelectorAll('section:nth-of-type(2) > div > div');
                categoryItems.forEach(item => {
                    item.addEventListener('click', function() {
                        // Remove active state from all items
                        categoryItems.forEach(i => i.classList.remove('text-airbnb-dark', 'border-b-2', 'border-airbnb-dark'));
                        // Add active state to clicked item
                        this.classList.add('text-airbnb-dark');
                        // alert(`Filtering by: ${this.querySelector('span').textContent}`);
                    });
                });

                // Experience buttons
                const experienceButtons = document.querySelectorAll('section:nth-of-type(4) button');
                experienceButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.stopPropagation();
                        // alert(`${this.textContent} would open here!`);
                    });
                });
            });
        </script>
        <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'96be51e9d3e673ee',t:'MTc1NDY0ODgzMy4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script>

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

    </body>




</html>
<?php /**PATH /Users/slx/Code/chamu2/resources/views/search/search.blade.php ENDPATH**/ ?>
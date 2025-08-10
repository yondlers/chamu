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
                    <form method="POST" action="<?php echo e(route('search')); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('GET'); ?>
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


        <!-- Property Title -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 mt-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-airbnb-dark mb-2"><?php echo e($listing->title); ?></h1>
                    <div class="flex items-center space-x-4 text-sm">
                        <span class="text-airbnb-gray underline"><?php echo e($listing->unit->address); ?></span>
                    </div>
                </div>
                <div class="flex items-center space-x-4 mt-4 md:mt-0">
                    <button class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                        </svg>
                        <span class="underline font-medium">Share</span>
                    </button>
                    <button class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span class="underline font-medium">Save</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Photo Gallery -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 h-96 rounded-xl overflow-hidden">
                <!-- Main Photo -->
                <div class="md:col-span-1 lg:col-span-2 lg:row-span-2 cursor-pointer" onclick="openGallery(0)">
                    <div class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-6xl hover:scale-105 transition-transform">
                        <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_1)); ?>" />
                    </div>
                </div>
                <!-- Secondary Photos -->
                <div class="hidden lg:block cursor-pointer" onclick="openGallery(1)">
                    <div class="w-full h-48 bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center text-white text-3xl hover:scale-105 transition-transform">
                        <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_2)); ?>" />
                    </div>
                </div>
                <?php if($listing->listingPictures[0]->image_3): ?>
                    <div class="hidden lg:block cursor-pointer" onclick="openGallery(2)">
                        <div class="w-full h-48 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white text-3xl hover:scale-105 transition-transform">
                            <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_3)); ?>" />
                        </div>
                    </div>
                <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_4): ?>
                    <div class="hidden lg:block cursor-pointer" onclick="openGallery(3)">
                        <div class="w-full h-48 bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center text-white text-3xl hover:scale-105 transition-transform">
                            <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_4)); ?>" />
                        </div>
                    </div>
                <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_5): ?>
                    <div class="hidden lg:block relative cursor-pointer" onclick="openGallery(4)">
                        <div class="w-full h-48 bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white text-3xl hover:scale-105 transition-transform">
                            <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_5)); ?>" />
                        </div>
    
    
    
    
    
    
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Gallery Modal -->
        <div id="galleryModal" class="hidden fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center">
            <div class="relative w-full h-full flex items-center justify-center p-4">
                <!-- Close Button -->
                <button onclick="closeGallery()" class="absolute top-6 right-6 text-white hover:text-gray-300 z-10">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- Image Counter -->
                <div class="absolute top-6 left-6 text-white text-lg font-medium z-10">
                    <span id="currentImageNumber">1</span> / <span id="totalImages">12</span>
                </div>

                <!-- Previous Button -->
                <button onclick="previousImage()" class="absolute left-6 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 z-10">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <!-- Next Button -->
                <button onclick="nextImage()" class="absolute right-6 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 z-10">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <!-- Main Image -->
                <div id="galleryImage" class="max-w-4xl max-h-[80vh] bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center text-white text-8xl">
                    🏖️
                </div>

                <!-- Thumbnail Strip -->
                <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex space-x-2 overflow-x-auto max-w-full px-4">
                    <div id="thumbnailStrip" class="flex space-x-2">
                        <!-- Thumbnails will be generated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Left Column - Property Details -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Host Info -->
                    <div class="flex items-center justify-between pb-6 border-b border-gray-200">
                        <div>
                            <h2 class="text-xl font-bold text-airbnb-dark mb-2">Rental by <?php echo e($listing->team->name); ?></h2>
                            <p class="text-airbnb-gray"><?php echo e($listing->unit->assetInformation->has_balcony == 1 ? 'Balcony · ' : ''); ?><?php echo e($listing->unit->assetInformation->has_pool == 1 ? 'Pool · ' : ''); ?><?php echo e($listing->unit->assetInformation->is_furnished == 1 ? 'Furnished · ' : 'Not Furnished · '); ?><?php echo e($listing->unit->assetInformation->is_pet_friendly == 1 ? 'Pet Friendly · ' : 'Not Pet Friendly · '); ?></p>

                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-pink-400 to-pink-600 rounded-full flex items-center justify-center text-white text-2xl">
                            👩‍🦰
                        </div>
                    </div>


                    <!-- Description -->
                    <div class="pb-8 border-b border-gray-200">
                        <?php echo $listing->description; ?>

                    </div>

                    <!-- Sleeping Arrangements -->
                    <div class="pb-8 border-b border-gray-200">
                        <h3 class="text-xl font-bold text-airbnb-dark mb-6">Home Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border border-gray-200 rounded-xl p-6">
                                <h4 class="font-medium text-airbnb-dark mb-1">Bedrooms <?php echo e($listing->unit->assetInformation->number_of_bedrooms); ?></h4>
                            </div>
                            <div class="border border-gray-200 rounded-xl p-6">
                                <h4 class="font-medium text-airbnb-dark mb-1">Bathrooms <?php echo e($listing->unit->assetInformation->number_of_bathrooms); ?></h4>
                            </div>
                            <div class="border border-gray-200 rounded-xl p-6">
                                <h4 class="font-medium text-airbnb-dark mb-1">Bedroom <?php echo e($listing->unit->assetInformation->number_of_kitchens); ?></h4>
                            </div>
                            <div class="border border-gray-200 rounded-xl p-6">
                                <h4 class="font-medium text-airbnb-dark mb-1">Garages <?php echo e($listing->unit->assetInformation->number_of_garage); ?></h4>
                            </div>
                            <div class="border border-gray-200 rounded-xl p-6">
                                <h4 class="font-medium text-airbnb-dark mb-1">Parking <?php echo e($listing->unit->assetInformation->number_of_parking); ?></h4>
                            </div>
                        </div>
                    </div>

                    <!-- Amenities -->
                    <div class="pb-8 border-b border-gray-200">
                        <h3 class="text-xl font-bold text-airbnb-dark mb-6">What this place offers</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php if($listing->unit->assetInformation->has_communal_pool == 1): ?>
                                    <div class="flex items-center space-x-4">
                                        <div class="w-6 h-6">🏖️</div>
                                        <span class="text-airbnb-dark">Communal Pool</span>
                                    </div>
                            <?php endif; ?>
                            <?php if($listing->unit->assetInformation->has_pool == 1): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6">🏖️</div>
                                    <span class="text-airbnb-dark">Private Pool</span>
                                </div>
                            <?php endif; ?>
                            <?php if($listing->unit->assetInformation->has_wifi == 1): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6">📶</div>
                                    <span class="text-airbnb-dark">Wifi</span>
                                </div>
                            <?php endif; ?>
                            <?php if($listing->unit->assetInformation->has_study == 1): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6">💻</div>
                                    <span class="text-airbnb-dark">Study</span>
                                </div>
                            <?php endif; ?>
                            <?php if($listing->unit->assetInformation->has_gym == 1): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6">🏋️</div>
                                    <span class="text-airbnb-dark">Gym</span>
                                </div>
                            <?php endif; ?>
                            <?php if($listing->unit->assetInformation->has_laundry_room == 1): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6">🧺</div>
                                    <span class="text-airbnb-dark">Laundry Room</span>
                                </div>
                            <?php endif; ?>
                            <?php if($listing->unit->assetInformation->has_braai_area == 1): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6">🔥</div>
                                    <span class="text-airbnb-dark">Private Braai Area</span>
                                </div>
                            <?php endif; ?>
                            <?php if($listing->unit->assetInformation->has_communal_braai == 1): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6">🔥</div>
                                    <span class="text-airbnb-dark">Private Communal Braai Area</span>
                                </div>
                            <?php endif; ?>
                            <?php if($listing->unit->assetInformation->has_tennis_court == 1): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="w-6 h-6">🎾</div>
                                    <span class="text-airbnb-dark">Tennis Court</span>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>

                <!-- Right Column - Booking Card -->
                <div class="lg:col-span-1">
                    <div class="sticky top-32">
                        <div class="border border-gray-200 rounded-xl p-6 shadow-lg">
                            <!-- Price -->
                            <div class="flex items-baseline space-x-2 mb-6">
                                <span class="text-2xl font-bold text-airbnb-dark">R <?php echo e($listing->unit->monthly_rent); ?></span>
                                <span class="text-airbnb-gray">Monthly Rental</span>
                            </div>

                            <!-- Reserve Button -->
                            <button class="w-full bg-airbnb-red text-white py-4 rounded-lg font-medium text-lg hover:bg-red-600 transition-colors mb-4">
                                Apply
                            </button>



                            <!-- Price Breakdown -->
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between">
                                    <span class="text-airbnb-dark underline">Rent</span>
                                    <span class="text-airbnb-dark">R <?php echo e($listing->unit->monthly_rent); ?></span>
                                </div>
                                <?php
                                    $total = $listing->unit->monthly_rent;
                                ?>
                                <?php $__currentLoopData = $listing->unit->deposits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deposit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $total += $deposit->deposit_amount;
                                    ?>
                                    <div class="flex justify-between">
                                        <span class="text-airbnb-dark underline"><?php echo e($deposit->deposit_name); ?></span>
                                        <span class="text-airbnb-dark">R <?php echo e($deposit->deposit_amount); ?></span>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>

                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-airbnb-dark">Total</span>
                                    <span class="font-bold text-airbnb-dark">R <?php echo e($total); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Report Listing -->
                        <div class="mt-6 text-center">
                            <button class="text-airbnb-gray underline text-sm hover:text-airbnb-dark">
                                🚩 Report this listing
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="mt-12 pb-12  border-gray-200">


                <!-- Individual Reviews -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php $__currentLoopData = $listing->comments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="space-y-4 border rounded-md p-2">
                            <div class="flex items-center space-x-3">
                                <div>
                                    <h4 class="font-medium text-airbnb-dark"><?php echo e($comment->user->name); ?></h4>

                                    <p class="text-sm text-airbnb-gray">
                                        Rating:
                                        <?php for($i=0; $i<$comment->rating; $i++): ?>
                                            ⭐
                                        <?php endfor; ?>
                                    </p>
                                    <p class="text-sm text-airbnb-gray"><?php echo e($comment->created_at); ?></p>
                                </div>
                            </div>
                            <p class="text-airbnb-dark">
                                <?php echo e($comment->content); ?>

                            </p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </div>


            </div>

            <!-- Leave a Comment Section -->
            <div class="mt-12 pb-12 border-b border-gray-200">
                <h3 class="text-2xl font-bold text-airbnb-dark mb-6">Leave a Comment</h3>
                <div class="bg-gray-50 rounded-xl p-6">
                    <form action="<?php echo e(route('comments.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>

                        <input name="listing_id" value="<?php echo e($listing->id); ?>" type="hidden" />

                        <!-- Rating Selection -->
                        <div>
                            <div class="flex items-center space-x-2">
                                <div id="starRating" class="flex space-x-1">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="1" class="sr-only">
                                        <span class="star text-3xl text-gray-300 hover:text-yellow-400 transition-colors">⭐</span>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="2" class="sr-only">
                                        <span class="star text-3xl text-gray-300 hover:text-yellow-400 transition-colors">⭐</span>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="3" class="sr-only">
                                        <span class="star text-3xl text-gray-300 hover:text-yellow-400 transition-colors">⭐</span>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="4" class="sr-only">
                                        <span class="star text-3xl text-gray-300 hover:text-yellow-400 transition-colors">⭐</span>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="5" class="sr-only">
                                        <span class="star text-3xl text-gray-300 hover:text-yellow-400 transition-colors">⭐</span>
                                    </label>
                                </div>
                                <span id="ratingText" class="text-sm text-airbnb-gray ml-4">Click to rate</span>
                            </div>
                        </div>

                        <!-- Comment Text -->
                        <div>
                            <label for="commentText" class="block text-sm font-medium text-airbnb-dark mb-2">Your Review</label>
                            <textarea id="commentText" name="content" rows="4" required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-airbnb-red focus:border-transparent resize-none"
                                      placeholder="Share what you know about this home"></textarea>
                            <div class="text-right text-sm text-airbnb-gray mt-1">
                                <span id="charCount">0</span>/500 characters
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-airbnb-gray">
                                Your review will be posted publicly and help other home finders.
                            </p>
                            <?php if(auth()->user()): ?>
                                <button type="submit"
                                        class="px-8 py-3 bg-airbnb-red text-white rounded-lg font-medium hover:bg-red-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
                                    Post Comment
                                </button>
                            <?php else: ?>
                                <a href="<?php echo e(route('login')); ?>"
                                        class="px-8 py-3 bg-airbnb-red text-white rounded-lg font-medium hover:bg-red-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
                                    Login To Comment
                                </a>
                            <?php endif; ?>

                        </div>
                    </form>
                </div>

                <!-- User Comments Displayxxx -->
                <?php if(count($listing->comments) > 1): ?>
                        <div id="userComments" class="mt-8 space-y-6">
                        <h4 class="text-lg font-medium text-airbnb-dark">Recent Comments</h4>
                        <div id="commentsList" class="space-y-4">
                            <!-- Comments will be added here dynamically -->
                        </div>
                        <div id="noComments" class="text-center py-8 text-airbnb-gray">
                            <div class="text-4xl mb-2">💬</div>
                            <p>Be the first to leave a comment about this home!</p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Location -->


























        </div>


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
            // Gallery functionality
            const images = [
                <?php if($listing->listingPictures[0]->image_1): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_1) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 1' }, <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_2): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_2) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 2' }, <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_3): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_3) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 3' }, <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_4): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_4) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 4' }, <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_5): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_5) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 5' }, <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_6): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_6) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 6' }, <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_7): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_7) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 7' }, <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_8): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_8) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 8' }, <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_9): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_9) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 9' }, <?php endif; ?>
                <?php if($listing->listingPictures[0]->image_10): ?>  { emoji: <?php echo json_encode(asset('storage/' . $listing->listingPictures[0]->image_10) , 15, 512) ?>, gradient: 'from-blue-400 to-blue-600', title: 'Image 10' }, <?php endif; ?>

            ];

            let currentImageIndex = 0;

            function isImageValue(v) {
                return typeof v === 'string' && (
                    v.startsWith('http') ||
                    v.startsWith('/storage') ||
                    v.startsWith('data:image')
                );
            }

            function openGallery(index) {
                currentImageIndex = index;
                const modal = document.getElementById('galleryModal');
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';

                updateGalleryImage();
                createThumbnails();

                document.getElementById('currentImageNumber').textContent = currentImageIndex + 1;
                document.getElementById('totalImages').textContent = images.length;
            }

            function closeGallery() {
                document.getElementById('galleryModal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            function nextImage() {
                currentImageIndex = (currentImageIndex + 1) % images.length;
                updateGalleryImage();
                document.getElementById('currentImageNumber').textContent = currentImageIndex + 1;
            }

            function previousImage() {
                currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
                updateGalleryImage();
                document.getElementById('currentImageNumber').textContent = currentImageIndex + 1;
            }

            function updateGalleryImage() {
                const galleryImage = document.getElementById('galleryImage');
                const current = images[currentImageIndex];

                // base container styles
                galleryImage.className = 'max-w-full max-h-full rounded-lg flex items-center justify-center overflow-hidden';

                if (isImageValue(current.emoji)) {
                    // show actual image
                    galleryImage.innerHTML = `<img src="${current.emoji}" alt="${current.title ?? ''}" class="max-w-full max-h-full object-contain rounded-lg">`;
                } else {
                    // show emoji bubble
                    galleryImage.classList.add('bg-gradient-to-br', ...(current.gradient ?? '').split(' '), 'text-white', 'text-8xl');
                    galleryImage.textContent = current.emoji || '🖼️';
                }

                // active thumb ring
                document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
                    thumb.classList.toggle('ring-4', i === currentImageIndex);
                    thumb.classList.toggle('ring-white', i === currentImageIndex);
                });
            }

            function createThumbnails() {
                const strip = document.getElementById('thumbnailStrip');
                strip.innerHTML = '';

                images.forEach((img, i) => {
                    const isImg = isImageValue(img.emoji);
                    const thumb = document.createElement('div');
                    thumb.className = 'thumbnail w-16 h-16 rounded-lg cursor-pointer hover:scale-110 transition-transform overflow-hidden flex items-center justify-center';

                    if (isImg) {
                        thumb.innerHTML = `<img src="${img.emoji}" alt="${img.title ?? ''}" class="w-full h-full object-cover">`;
                    } else {
                        thumb.classList.add('bg-gradient-to-br', ...(img.gradient ?? '').split(' '), 'text-white', 'text-xl');
                        thumb.textContent = img.emoji || '🖼️';
                    }

                    thumb.onclick = () => {
                        currentImageIndex = i;
                        updateGalleryImage();
                        document.getElementById('currentImageNumber').textContent = currentImageIndex + 1;
                    };

                    if (i === currentImageIndex) thumb.classList.add('ring-4', 'ring-white');
                    strip.appendChild(thumb);
                });
            }
            // Reviews functionality
            function toggleReviews() {
                const additionalReviews = document.getElementById('additionalReviews');
                const button = document.getElementById('showMoreReviews');

                if (additionalReviews.classList.contains('hidden')) {
                    additionalReviews.classList.remove('hidden');
                    button.textContent = 'Show fewer reviews';
                } else {
                    additionalReviews.classList.add('hidden');
                    button.textContent = 'Show all 127 reviews';
                }
            }

            // Keyboard navigation for gallery
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('galleryModal');
                if (!modal.classList.contains('hidden')) {
                    if (e.key === 'ArrowRight') {
                        nextImage();
                    } else if (e.key === 'ArrowLeft') {
                        previousImage();
                    } else if (e.key === 'Escape') {
                        closeGallery();
                    }
                }
            });

            // Close gallery when clicking outside the image
            document.getElementById('galleryModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeGallery();
                }
            });

            // Comment system functionality
            let userRating = 0;
            let userComments = [];

            function initializeCommentSystem() {
                // Star rating functionality
                const radioButtons = document.querySelectorAll('input[name="rating"]');
                const stars = document.querySelectorAll('.star');
                const ratingText = document.getElementById('ratingText');

                radioButtons.forEach((radio, index) => {
                    radio.addEventListener('change', function() {
                        if (this.checked) {
                            userRating = parseInt(this.value);
                            updateStarDisplay(userRating);
                            updateRatingText(userRating);
                        }
                    });
                });

                stars.forEach((star, index) => {
                    star.addEventListener('mouseenter', function() {
                        updateStarDisplay(index + 1);
                    });
                });

                document.getElementById('starRating').addEventListener('mouseleave', function() {
                    updateStarDisplay(userRating);
                });

                // Character counter
                const commentText = document.getElementById('commentText');
                const charCount = document.getElementById('charCount');

                commentText.addEventListener('input', function() {
                    const length = this.value.length;
                    charCount.textContent = length;

                    if (length > 500) {
                        this.value = this.value.substring(0, 500);
                        charCount.textContent = 500;
                    }

                    if (length > 450) {
                        charCount.classList.add('text-red-500');
                    } else {
                        charCount.classList.remove('text-red-500');
                    }
                });

                // Form submission
                const commentForm = document.getElementById('commentForm');
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitComment();
                });
            }

            function updateStarDisplay(rating) {
                const stars = document.querySelectorAll('.star');
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.remove('text-gray-300');
                        star.classList.add('text-yellow-400');
                    } else {
                        star.classList.remove('text-yellow-400');
                        star.classList.add('text-gray-300');
                    }
                });
            }

            function updateRatingText(rating) {
                const ratingText = document.getElementById('ratingText');
                const ratingLabels = {
                    1: 'Poor',
                    2: 'Fair',
                    3: 'Good',
                    4: 'Very Good',
                    5: 'Excellent'
                };
                ratingText.textContent = rating > 0 ? ratingLabels[rating] : 'Click to rate';
            }

            function submitComment() {
                const userName = document.getElementById('userName').value.trim();
                const userEmail = document.getElementById('userEmail').value.trim();
                const commentText = document.getElementById('commentText').value.trim();

                if (!userName || !commentText || userRating === 0) {
                    alert('Please fill in your name, rating, and comment before submitting.');
                    return;
                }

                const newComment = {
                    id: Date.now(),
                    name: userName,
                    email: userEmail,
                    rating: userRating,
                    text: commentText,
                    date: new Date().toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    }),
                    avatar: getRandomAvatar()
                };

                userComments.unshift(newComment);
                displayComments();
                resetForm();

                // Show success message
                showSuccessMessage();
            }

            function getRandomAvatar() {
                const avatars = ['👨‍💼', '👩‍🎨', '👨‍🎓', '👩‍💻', '👨‍🍳', '👩‍🔬', '👨‍🎤', '👩‍🏫', '👨‍⚕️', '👩‍🚀'];
                return avatars[Math.floor(Math.random() * avatars.length)];
            }

            function displayComments() {
                const commentsList = document.getElementById('commentsList');
                const noComments = document.getElementById('noComments');

                if (userComments.length === 0) {
                    noComments.style.display = 'block';
                    commentsList.innerHTML = '';
                    return;
                }

                noComments.style.display = 'none';

                commentsList.innerHTML = userComments.map(comment => `
                <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-purple-600 rounded-full flex items-center justify-center text-white text-xl">
                            ${comment.avatar}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h5 class="font-medium text-airbnb-dark">${comment.name}</h5>
                                    <p class="text-sm text-airbnb-gray">${comment.date}</p>
                                </div>
                                <div class="flex items-center space-x-1">
                                    ${Array.from({length: 5}, (_, i) =>
                    `<span class="text-sm ${i < comment.rating ? 'text-yellow-400' : 'text-gray-300'}">⭐</span>`
                ).join('')}
                                    <span class="text-sm text-airbnb-gray ml-2">${comment.rating}/5</span>
                                </div>
                            </div>
                            <p class="text-airbnb-dark leading-relaxed">${comment.text}</p>
                        </div>
                    </div>
                </div>
            `).join('');
            }

            function resetForm() {
                document.getElementById('commentForm').reset();
                userRating = 0;
                updateStarDisplay(0);
                updateRatingText(0);
                document.getElementById('charCount').textContent = '0';
            }

            function showSuccessMessage() {
                const successDiv = document.createElement('div');
                successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform';
                successDiv.innerHTML = `
                <div class="flex items-center space-x-2">
                    <span>✅</span>
                    <span>Comment posted successfully!</span>
                </div>
            `;

                document.body.appendChild(successDiv);

                // Animate in
                setTimeout(() => {
                    successDiv.classList.remove('translate-x-full');
                }, 100);

                // Remove after 3 seconds
                setTimeout(() => {
                    successDiv.classList.add('translate-x-full');
                    setTimeout(() => {
                        document.body.removeChild(successDiv);
                    }, 300);
                }, 3000);
            }

            // Add interactive functionality
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize comment system
                initializeCommentSystem();
                // Heart button functionality for property page
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

                // Reserve button functionality
                const reserveButton = document.querySelector('.bg-airbnb-red');
                if (reserveButton && reserveButton.textContent.includes('Reserve')) {
                    reserveButton.addEventListener('click', function() {
                        alert('Redirecting to booking confirmation...');
                    });
                }

                // Contact host functionality
                const contactButtons = document.querySelectorAll('button');
                contactButtons.forEach(button => {
                    if (button.textContent.includes('Contact Host')) {
                        button.addEventListener('click', function() {
                            alert('Opening message dialog with Marie...');
                        });
                    }
                    if (button.textContent.includes('Show profile')) {
                        button.addEventListener('click', function() {
                            alert('Opening Marie\'s host profile...');
                        });
                    }
                });

                // Show more buttons functionality
                const showMoreButtons = document.querySelectorAll('button');
                showMoreButtons.forEach(button => {
                    if (button.textContent.includes('Show more') && !button.id) {
                        button.addEventListener('click', function() {
                            if (button.textContent.includes('amenities')) {
                                alert('Opening full amenities list...');
                            } else {
                                alert('Expanding content...');
                            }
                        });
                    }
                });

                // Report listing functionality
                const reportButton = document.querySelector('button[class*="underline"]');
                if (reportButton && reportButton.textContent.includes('Report')) {
                    reportButton.addEventListener('click', function() {
                        alert('Opening report form...');
                    });
                }

                // Share and Save buttons
                const actionButtons = document.querySelectorAll('button');
                actionButtons.forEach(button => {
                    if (button.textContent.includes('Share')) {
                        button.addEventListener('click', function() {
                            if (navigator.share) {
                                navigator.share({
                                    title: 'Beachfront Villa in Nice',
                                    text: 'Check out this amazing villa!',
                                    url: window.location.href
                                });
                            } else {
                                alert('Share link copied to clipboard!');
                            }
                        });
                    }
                    if (button.textContent.includes('Save')) {
                        button.addEventListener('click', function() {
                            const svg = this.querySelector('svg');
                            if (svg.classList.contains('text-red-500')) {
                                svg.classList.remove('text-red-500');
                                alert('Removed from saved!');
                            } else {
                                svg.classList.add('text-red-500');
                                alert('Added to saved!');
                            }
                        });
                    }
                });

                // Smooth scrolling for anchor links
                const anchorLinks = document.querySelectorAll('a[href^="#"]');
                anchorLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth' });
                        }
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
<?php /**PATH /Users/slx/Code/chamu2/resources/views/search/place.blade.php ENDPATH**/ ?>
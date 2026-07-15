<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <?php echo e(__('View Listing')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="py-2 text-center">

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('listings.index')); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Back
                            </a>
                        </div>
                    </div>

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('listings.edit', ['listing' => $listing])); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Edit
                            </a>
                        </div>
                    </div>

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('listings.asset_info', ['listing' => $listing])); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Listing Information
                            </a>
                        </div>
                    </div>

                    <?php if($listing->listingPictures[0]->image_1): ?>
                        <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                            <div class="w-full text-center">
                                <a href="<?php echo e(route('listing_pictures.edit', ['listing_picture' => $listing->listingPictures[0]])); ?>"
                                   class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                                >
                                    Update Pictures
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                            <div class="w-full text-center">
                                <a href="<?php echo e(route('listing_pictures.upload', ['listing_id' => $listing->id])); ?>"
                                   class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                                >
                                    Upload Pictures
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>


                </div>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 flex flex-wrap gap-4">
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_1)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_2)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_3)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_4)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_5)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_6)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_7)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_8)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_9)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                    <img src="<?php echo e(asset('storage/' . $listing->listingPictures[0]->image_10)); ?>" class="h-40 mx-2 rounded-md" alt="" />
                </div>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800"><?php echo e($listing->title); ?></h1>
                            <p class="text-gray-600 mt-1">Listing Title</p>
                        </div>
                        <div class="text-right">
                            <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                <?php if($listing->active == 1): ?>
                                    Active
                                <?php else: ?>
                                    Not Active
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Lease Reg: <?php echo e($listing->created_at); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class=" items-center justify-between">
                        <div class="text-center">

                            <h2 class=" text-gray-500 mt-1 mb-2">Financials</h2>
                        </div>
                        <div class="mt-2 mb-2">
                            <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                <h1 class="text-3xl font-bold text-gray-800">R <?php echo e($listing->unit->monthly_rent); ?></h1>

                            </div>
                            <p class="text-gray-600 mt-1">Monthly Rental</p>
                        </div>
                        <?php $__currentLoopData = $listing->unit->deposits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deposit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="mt-2 mb-2">
                            <div class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium">
                                <h1 class="text-3xl font-bold text-gray-800">R <?php echo e($deposit->deposit_amount); ?></h1>

                            </div>
                            <p class="text-gray-600 mt-1"><?php echo e($deposit->deposit_name); ?></p>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    </div>
                </div>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class=" text-center justify-between">
                        <?php echo $assetInfoHtml; ?>

                    </div>
                </div>
            </div>



        </div>
    </div>




 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>

<?php /**PATH /Users/slx/Code/chamu2/resources/views/listings/show.blade.php ENDPATH**/ ?>
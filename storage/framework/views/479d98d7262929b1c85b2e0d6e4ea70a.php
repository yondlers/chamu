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
            <?php echo e(__('Listing Information')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="py-2 text-center">
                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">


                        <div class="w-full text-center">
                            <a href="<?php echo e(route('listings.show', ['listing' => $listing])); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Back
                            </a>
                        </div>

                    </div>
                </div>

            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="p-6">

                    <?php if(session('success')): ?>
                        <div class="flex items-center justify-center h-4">
                            <span class="inline-block px-2 py-1 text-xs font-semibold text-white bg-green-500 rounded-full">
                                <?php echo e(session('success')); ?>

                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="flex items-center justify-center h-4 mb-4">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-white bg-red-500 rounded-full">
                                            <?php echo e($error); ?>

                                        </span>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <hr class="mt-2">

                    <form action="<?php echo e(route('listings.asset_post', ['listing' => $listing, 'createNotUpdate' => $createNotUpdate])); ?>" method="POST" class="space-y-6">
                        <?php echo csrf_field(); ?>

                        <?php echo $assetInfoHtml; ?>


                    </form>

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
<?php /**PATH /Users/slx/Code/chamu2/resources/views/listings/asset_info.blade.php ENDPATH**/ ?>
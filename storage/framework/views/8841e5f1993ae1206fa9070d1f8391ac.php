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
            <?php echo e(__('Manage Asset Information')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-8">

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

    </div>

    <div class="container mx-auto px-4 py-4 max-w-6xl">
        <div id="tasksContainer1" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <?php $__currentLoopData = $asset_informations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $asset_information): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                <a href="<?php echo e(route('properties.edit', ['asset_informations' => $asset_information])); ?>" class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 border-l-4 border-l-yellow-500 animate-fade-in ${cardLayout} transform hover:scale-105">
                    <div class="container px-4 mb-4 mt-4">

                        <div class="flex-grow min-w-0">
                            <h3 class="font-semibold text-gray-800 line-through truncate"><?php echo e($property->address_line_1); ?>, <?php echo e($property->address_line_2); ?></h3>
                            <p class="text-gray-600 text-sm truncate"><?php echo e($property->suburb->name); ?>, <?php echo e($property->city->name); ?>, <?php echo e($property->province->name); ?></p>
                        </div>

                        <hr>

                        <div class="flex-grow min-w-0">
                            <p class="text-gray-600 text-sm truncate"><?php echo e($property->propertyType->name); ?></p>
                        </div>
                        <div class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded">
                            Created <?php echo e($property->created_at); ?>

                        </div>
                        <div class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded">
                            Modified <?php echo e($property->created_at); ?>

                        </div>
                    </div>

                </a>
                <!-- Sample tasks will be added here -->

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

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

<?php /**PATH /Users/slx/Code/chamu2/resources/views/asset_informations/index.blade.php ENDPATH**/ ?>
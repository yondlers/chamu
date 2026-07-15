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
            <?php echo e(__('View Properties')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="py-2 text-center">

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('properties.index')); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Back
                            </a>
                        </div>
                    </div>

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('properties.edit', ['property' => $property])); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Edit
                            </a>
                        </div>
                    </div>

                    <?php if($property->asset_information_id): ?>
                        <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                            <div class="w-full text-center">
                                <a href="<?php echo e(route('asset_informations.edit', ['asset_information' => $property->asset_information])); ?>"
                                   class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                                >
                                    Edit Property Information
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                            <div class="w-full text-center">
                                <a href="<?php echo e(route('asset_informations.createView', ['property_id' => $property, 'unit_id' => 0])); ?>"
                                   class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                                >
                                    Add Property Information
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>


                </div>

            </div>

            <div class="mt-6 mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="py-5 text-center">

                    <section class="p-2">
                        <?php echo $propertyHtml; ?>

                    </section>

                </div>

            </div>

            <?php if($property->units[0]->unitType->name === 'Whole'): ?>

            <?php else: ?>
                <div class="mt-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="mt-6 mb-6 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('units.create')); ?>" class="w-full block card rounded-lg border border-gray-300 bg-white shadow-md text-black">
                                Add Unit +
                            </a>
                        </div>
                    </div>
                </div>

                <div class="container mx-auto px-4 py-4 max-w-6xl">
                <div id="tasksContainer1" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    <?php $__currentLoopData = $property->units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                        <a href="<?php echo e(route('units.show', ['unit' => $unit])); ?>" class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 border-l-4 border-l-yellow-500 animate-fade-in ${cardLayout} transform hover:scale-105">
                            <div class="container px-4 mb-4 mt-4">

                                <div class="flex-grow min-w-0">
                                    <h3 class="font-semibold text-gray-800 line-through truncate"><?php echo e($unit->property->address_line_1); ?>, <?php echo e($unit->property->address_line_2); ?></h3>
                                    <p class="text-gray-600 text-sm truncate"><?php echo e($unit->property->suburb->name); ?>, <?php echo e($unit->property->city->name); ?>, <?php echo e($unit->property->province->name); ?></p>
                                </div>

                                <hr>

                                <div class="flex-grow min-w-0">
                                    <h3 class="font-semibold text-gray-800 line-through truncate">R<?php echo e($unit->monthly_rent); ?></h3>
                                    <p class="text-gray-600 text-sm truncate">Monthly Rental</p>
                                </div>

                                <hr>

                                <div class="flex-grow min-w-0">
                                    <p class="text-gray-600 text-sm truncate"><?php echo e($unit->unitType->name); ?></p>
                                </div>
                                <div class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded">
                                    Created <?php echo e($unit->created_at); ?>

                                </div>
                                <div class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded">
                                    Modified <?php echo e($unit->created_at); ?>

                                </div>
                            </div>

                        </a>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </div>
            </div>

            <?php endif; ?>

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

<?php /**PATH /Users/slx/Code/chamu2/resources/views/properties/show.blade.php ENDPATH**/ ?>
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
            <?php echo e(__('View Lease Template')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="py-2 text-center">

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('lease_templates.index')); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Back
                            </a>
                        </div>
                    </div>

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('lease_templates.edit', ['lease_template' => $lease_template])); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Edit
                            </a>
                        </div>
                    </div>

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('lease_templates.download', ['lease_template' => $lease_template])); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Download Preview
                            </a>
                        </div>
                    </div>



                </div>

            </div>

            <div class="mt-6 mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="py-5 text-center">

                    <section class="p-2">
                        <?php echo $leaseTemplateHtml; ?>

                    </section>

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

<?php /**PATH /Users/slx/Code/chamu2/resources/views/lease_templates/show.blade.php ENDPATH**/ ?>
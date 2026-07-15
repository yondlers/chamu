
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
            Update Your Listing Profile
        </h2>
     <?php $__env->endSlot(); ?>


    <div class="px-4 container">

    <div class="max-w-2xl mx-auto px-4">

        <!-- Profile Form -->
        <div class="mt-6 mb-6 max-w-6xl mx-auto sm:px-5 lg:px-7">
            <?php if($tenant->id_number == null): ?>
                <form action="<?php echo e(route('tenants.store')); ?>" method="POST" class="space-y-6">
                    <?php echo csrf_field(); ?>
            <?php else: ?>
                <form action="<?php echo e(route('tenants.update', ['tentant' => $tenant])); ?>" method="POST" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
            <?php endif; ?>
                    <input type="hidden" name="user_id" value="<?php echo e(auth()->user()->id); ?>" />

                    <?php echo $tenantForm; ?>


                </form>
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


<?php /**PATH /Users/slx/Code/chamu2/resources/views/tenants/user.blade.php ENDPATH**/ ?>
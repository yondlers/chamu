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
            <?php echo e(__('Edit Tenants')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="py-2 text-center">
                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">


                        <div class="w-full text-center">
                            <a href="<?php echo e(route('tenants.index')); ?>"
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

                    <!-- Display Existing Documents -->
                    <div class="mt-2">
                        <h3 class="text-lg font-medium text-gray-900">Uploaded Documents</h3>
                        <ul class="list-disc pl-5 space-y-2 mt-3">
                            <?php if($tenant->id_document_file_name): ?>
                                <li>
                                    <a href="<?php echo e(asset('storage/' . $tenant->id_document_file_name)); ?>" target="_blank" class="text-blue-600 underline">
                                        View ID Document
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if($tenant->bank_statements_file_name): ?>
                                <li>
                                    <a href="<?php echo e(asset('storage/' . $tenant->bank_statements_file_name)); ?>" target="_blank" class="text-blue-600 underline">
                                        View Bank Statement
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if($tenant->proof_of_income_file_name): ?>
                                <li>
                                    <a href="<?php echo e(asset('storage/' . $tenant->proof_of_income_file_name)); ?>" target="_blank" class="text-blue-600 underline">
                                        View Proof of Income
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <hr class="mt-2">

                    <form action="<?php echo e(route('tenants.update', ['tenant' => $tenant])); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>

                        <!-- Form -->
                        <?php echo $tenantForm; ?>


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

<script>

    window.onload = function () {
        const selected_value = document.getElementById('rental_type').value;

        hideAll();

        if (selected_value === 'Whole') {
            showAll();
        }

    };

    document.getElementById('rental_type').addEventListener('change', function () {
        const selected_value = document.getElementById('rental_type').value;

        if (selected_value == "Whole") {
            showAll();
        } else {
            hideAll();
        }
    });

    function showAll () {
        document.getElementById('whole_display').style.display = 'block';
    }

    function hideAll () {
        document.getElementById('whole_display').style.display = 'none';
    }

</script>
<?php /**PATH /Users/slx/Code/chamu2/resources/views/tenants/edit.blade.php ENDPATH**/ ?>
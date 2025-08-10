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
            <?php echo e(__('View Lease')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="py-2 text-center">

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('leases.index')); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Back
                            </a>
                        </div>
                    </div>

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('leases.download', ['lease' => $lease])); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Download
                            </a>
                        </div>
                    </div>

                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('deposits.add', ['unit_id' => $lease->unit_id])); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Add Deposit
                            </a>
                        </div>
                    </div>

























                </div>

            </div>


            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800"><?php echo e($lease->name); ?></h1>
                            <p class="text-gray-600 mt-1">Lease Agreement</p>
                        </div>
                        <div class="text-right">
                            <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                <?php if($lease->active == 1): ?>
                                    Active
                                <?php else: ?>
                                    Not Active
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Lease Reg: <?php echo e($lease->created_at); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Property Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Property Address</label>
                            <p class="text-gray-800 font-medium"><?php echo e($lease->unit->address); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Unit Number</label>
                            <p class="text-gray-800">
                                <?php if($lease->unit->unitType->name == "Whole"): ?>
                                    <?php echo e($lease->unit->property->unit_number); ?>

                                <?php else: ?>
                                    <?php echo e($lease->unit->unit_number); ?>

                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Unit Type</label>
                            <p class="text-gray-800">
                                <?php if($lease->unit->unitType->name == "Whole"): ?>
                                    <?php echo e($lease->unit->property->propertyType->name); ?>

                                <?php else: ?>
                                    <?php echo e($lease->unit->unitType->name); ?>

                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Tenant Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Name</label>
                            <p class="text-gray-800 font-medium"><?php echo e($lease->tenant->name); ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Contact Number</label>
                            <p class="text-gray-800 font-medium"><?php echo e($lease->tenant->contact_number); ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <p class="text-gray-800 font-medium"><?php echo e($lease->tenant->email); ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Gender</label>
                            <p class="text-gray-800 font-medium"><?php echo e($lease->tenant->gender->name); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Lease Terms
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Name</label>
                            <p class="text-gray-800 font-medium"><?php echo e($lease->name); ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Debit Day</label>
                            <p class="text-gray-800 font-medium"><?php echo e($lease->debit_date); ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Start Date</label>
                            <p class="text-gray-800 font-medium"><?php echo e($lease->start_date); ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">End Date</label>
                            <p class="text-gray-800 font-medium"><?php echo e($lease->end_date); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        Financial Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-blue-600 mb-1">Monthly Rent</label>
                            <p class="text-2xl font-bold text-blue-800">R<?php echo e($lease->rent_amount); ?></p>
                        </div>
                        <?php if(count($lease->unit->deposits) > 0): ?>
                            <?php $__currentLoopData = $lease->unit->deposits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deposit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <label class="block text-sm font-medium text-green-600 mb-1"><?php echo e($deposit->deposit_name); ?></label>
                                    <p class="text-2xl font-bold text-green-800">R<?php echo e($deposit->deposit_amount); ?></p>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php else: ?>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium text-green-600 mb-1">Add Deposits on Unit</label>
                            </div>
                        <?php endif; ?>

                        <div class="bg-purple-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-purple-600 mb-1">Late Fee Days</label>
                            <p class="text-2xl font-bold text-purple-800"><?php echo e($lease->late_fee_days); ?></p>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-orange-600 mb-1">Late Fee</label>
                            <p class="text-2xl font-bold text-orange-800">R<?php echo e($lease->late_fee); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Utilities
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Electricity</span>
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm"><?php echo e($lease->utility_payer); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Gas</span>
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm"><?php echo e($lease->utility_payer); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Water/Sewer</span>
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm"><?php echo e($lease->utility_payer); ?></span>
                        </div>








                    </div>
                </div>
            </div>

            <div class="max-w-4xl mx-auto px-4">



                <!-- Utilities & Amenities -->




















































                <!-- Important Notes -->



































                <!-- Action Buttons -->





















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

<?php /**PATH /Users/slx/Code/chamu2/resources/views/leases/show.blade.php ENDPATH**/ ?>
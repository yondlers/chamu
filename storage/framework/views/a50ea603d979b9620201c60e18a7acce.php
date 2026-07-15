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
            <?php echo e(__('View Tenants')); ?>

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


                    <div class="mt-4 mb-4 max-w-6xl mx-auto sm:px-5 lg:px-7">
                        <div class="w-full text-center">
                            <a href="<?php echo e(route('tenants.edit', ['tenant', $tenant])); ?>"
                               class="w-full font-bold block card rounded-lg border border-gray-300 bg-white shadow-md text-black"
                            >
                                Edit
                            </a>
                        </div>
                    </div>

                </div>

            </div>

            <div class="mt-6 mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class=" p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        Personal Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">First Name</label>
                            <p class="text-gray-900"><?php echo e($tenant->first_name); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Last Name</label>
                            <p class="text-gray-900"><?php echo e($tenant->last_name); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">ID Number</label>
                            <p class="text-gray-900"><?php echo e($tenant->id_number); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Passport Number</label>
                            <p class="text-gray-900"><?php echo e($tenant->passport_number); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Date of Birth</label>
                            <p class="text-gray-900"><?php echo e($tenant->date_of_birth); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Gender</label>
                            <p class="text-gray-900"><?php echo e($tenant->gender->name); ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Martial Status</label>
                            <p class="text-gray-900"><?php echo e($tenant->maritalStatus->name); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Ethnicity</label>
                            <p class="text-gray-900"><?php echo e($tenant->ethnicity->name); ?></p>
                        </div>
                    </div>
                </div>
            </div>


            <div class="mt-6 mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class=" p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Contact Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <p class="text-gray-900"><?php echo e($tenant->email); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Phone</label>
                            <p class="text-gray-900"><?php echo e($tenant->contact_number); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Location</label>
                            <p class="text-gray-900"><?php echo e($tenant->address_line_1); ?>, <?php echo e($tenant->address_line_2); ?>, <?php echo e($tenant->suburb->name); ?>, <?php echo e($tenant->city->name); ?>, <?php echo e($tenant->province->name); ?>,  <?php echo e($tenant->country->name); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Postal Code</label>
                            <p class="text-gray-900"><?php echo e($tenant->postal_code); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class=" p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">

                        Rental Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Number of Occupants</label>
                            <p class="text-gray-900"><?php echo e($tenant->number_of_occupancies); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Has Pets</label>
                            <p class="text-gray-900"><?php echo e($tenant->has_pets); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Black Listed</label>
                            <p class="text-gray-900"><?php echo e($tenant->blacklisted); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Special Request</label>
                            <p class="text-gray-900"><?php echo e($tenant->special_requirements); ?></p>
                        </div>
                    </div>
                </div>
            </div>


            <div class="mt-6 mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class=" p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                        Professional Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Employer</label>
                            <p class="text-gray-900"><?php echo e($tenant->employer); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Work Number</label>
                            <p class="text-gray-900"><?php echo e($tenant->work_number); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Occupation</label>
                            <p class="text-gray-900"><?php echo e($tenant->occupation); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Monthly income</label>
                            <p class="text-gray-900">***</p>

                        </div>

                    </div>
                </div>
            </div>

            <div class="mt-6 mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class=" p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Emergency Contact
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Full Name</label>
                            <p class="text-gray-900"><?php echo e($tenant->emergency_name); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Contact Number</label>
                            <p class="text-gray-900"><?php echo e($tenant->emergency_number); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <p class="text-gray-900"><?php echo e($tenant->emergency_email); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Relationship</label>
                            <p class="text-gray-900"><?php echo e($tenant->emergency_relationship); ?></p>
                        </div>

                    </div>
                </div>
            </div>



            <div class="mt-6 mb-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="py-5 text-center">

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
    function adjustTableVisibility() {
        // const table = document.querySelector('table');
        const table = document.getElementById('display_large');
        const cards = document.getElementById('display_small');


        if (window.innerWidth < 768) {
            // Small screen (sm)
            table.classList.add('hidden');
            table.classList.remove('table');

            cards.classList.remove('hidden');
        } else {
            // Medium (md) or larger
            table.classList.remove('hidden');
            table.classList.add('table');

            cards.classList.add('hidden');

        }
    }

    // Run on initial load
    adjustTableVisibility();

    // Add a resize event listener to handle dynamic changes
    window.addEventListener('resize', adjustTableVisibility);

</script>

<?php /**PATH /Users/slx/Code/chamu2/resources/views/tenants/show.blade.php ENDPATH**/ ?>
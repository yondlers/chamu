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
            <?php echo e(__('Manage Deposits')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <script src="<?php echo e(asset('js/screen_view/index.js')); ?>"></script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mt-6 bg-white overflow-hidden shadow-xl sm:rounded-lg">

                <div class="mt-6 mb-6 max-w-6xl mx-auto sm:px-5 lg:px-7">
                    <div class="w-full text-center">
                        <a href="<?php echo e(route('deposits.create')); ?>" class="w-full block card rounded-lg border border-gray-300 bg-white shadow-md text-black">
                            Add Deposit +
                        </a>
                    </div>
                </div>

                <div class="mt-6 mb-6 max-w-6xl mx-auto sm:px-5 lg:px-7">

                    <div class="mt-2 card">
                        <div class=" relative overflow-x-auto shadow-md sm:rounded-lg">


                            <table id="display_large" class=" w-full text-sm text-left rtl:text-right text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">
                                        Deposit Name
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Deposit Amount
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Unit Number
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Action
                                    </th>
                                </tr>
                                </thead>
                                <tbody>

                                <?php $__currentLoopData = $deposits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deposit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b">

                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                            <?php echo e($deposit->deposit_name); ?>

                                        </th>
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                            <?php echo e($deposit->deposit_amount); ?>

                                        </th>
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                            <?php if($deposit->unit->name): ?>
                                                <?php echo e($deposit->unit->unit_number); ?>

                                            <?php else: ?>
                                                <?php echo e($deposit->unit->property->unit_number); ?>

                                            <?php endif; ?>
                                        </th>
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                            <a href="<?php echo e(route('deposits.edit', ['deposit' => $deposit])); ?>" class="w-full block card rounded-lg border border-gray-300 bg-white shadow-md text-black">
                                                Edit
                                            </a>
                                            <a href="<?php echo e(route('deposits.show', ['deposit' => $deposit])); ?>" class="w-full block card rounded-lg border border-gray-300 bg-white shadow-md text-black">
                                                Show
                                            </a>
                                        </th>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                <?php if(count($deposits) == 0): ?>
                                    <div class="mt-2 mb-2 text-center">
                                        No Records
                                    </div>
                                <?php endif; ?>

                                </tbody>
                            </table>



                        </div>

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

<?php /**PATH /Users/slx/Code/chamu2/resources/views/deposits/index.blade.php ENDPATH**/ ?>
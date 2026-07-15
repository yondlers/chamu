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
            <?php if($property): ?>
                <?php echo e($property->name); ?>

            <?php elseif($unit): ?>
                <?php echo e($unit->name); ?>

            <?php endif; ?>
        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


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

                    <form action="<?php echo e(route('asset_informations.store')); ?>" method="POST" class="space-y-6">
                        <?php echo csrf_field(); ?>

                        <?php echo $assetInformationHtml; ?>


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
        const selected_value = document.getElementById('property_type').value;

        hideAll();

        if (selected_value == 1) {
            group1('block');
            group2('none');
        }
        else if (selected_value == 2)
        {
            group1('none');
            group2('block');
        }

    };

    document.getElementById('property_type').addEventListener('change', function () {
        const selected_value = document.getElementById('property_type').value;

        hideAll();

        if (selected_value == 1) {
            group1('block');
            group2('none');
        }
        else if (selected_value == 2)
        {
            group1('none');
            group2('block');
        }
    });

    function group1 (value) {
        document.getElementById('number_of_units_hold').style.display = value;
        document.getElementById('gated_community_hold').style.display = value;
        document.getElementById('has_clubhouse_hold').style.display = value;
        document.getElementById('has_gym_hold').style.display = value;
        document.getElementById('has_tennis_court_hold').style.display = value;
        document.getElementById('has_golf_course_hold').style.display = value;
        document.getElementById('has_communal_pool_hold').style.display = value;
        document.getElementById('has_communal_braai_hold').style.display = value;
        document.getElementById('has_communal_area_hold').style.display = value;
        document.getElementById('has_parking_hold').style.display = value;
        document.getElementById('complex_description_hold').style.display = value;
        document.getElementById('number_of_buildings_in_complex_hold').style.display = value;
    }

    function group2 (value) {
        document.getElementById('number_of_bathrooms_hold').style.display = value;
        document.getElementById('number_of_garages_hold').style.display = value;
        document.getElementById('number_of_bedrooms_hold').style.display = value;
        document.getElementById('number_of_kitchens_hold').style.display = value;
        document.getElementById('number_of_parking_hold').style.display = value;
        document.getElementById('room_size_sqm_hold').style.display = value;
        document.getElementById('room_features_hold').style.display = value;
        document.getElementById('number_of_beds_in_room_hold').style.display = value;
        document.getElementById('has_private_bathroom_hold').style.display = value;
        document.getElementById('has_private_kitchen_hold').style.display = value;
        document.getElementById('is_room_sharing_hold').style.display = value;
        document.getElementById('number_of_occupants_in_room_hold').style.display = value;
        document.getElementById('room_sharing_gender_preference_hold').style.display = value;
        document.getElementById('room_sharing_rules_hold').style.display = value;
        document.getElementById('is_furnished_hold').style.display = value;
        document.getElementById('has_disability_access_hold').style.display = value;
        document.getElementById('has_balcony_hold').style.display = value;
        document.getElementById('has_air_conditioning_hold').style.display = value;
        document.getElementById('has_heating_hold').style.display = value;
        document.getElementById('has_built_in_cupboards_hold').style.display = value;
        document.getElementById('has_wifi_hold').style.display = value;
        document.getElementById('electricity_meter_hold').style.display = value;
        document.getElementById('water_meter_hold').style.display = value;
        document.getElementById('utility_type_hold').style.display = value;
        document.getElementById('fiber_ready_hold').style.display = value;
        document.getElementById('gas_hold').style.display = value;
    }

    function hideAll () {
        group1('none');
        group2('none');

    }

</script>
<?php /**PATH /Users/slx/Code/chamu2/resources/views/asset_informations/create_view.blade.php ENDPATH**/ ?>
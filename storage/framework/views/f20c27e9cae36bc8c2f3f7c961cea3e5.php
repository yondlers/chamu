<?php
    $user = auth()->user();
    $tenant = \App\Models\Tenant::where('user_id', $user->id)->get();
    if(count($tenant) == 0 && $user->current_team_id == null)
    {
        $new_user = true;
    }
    else {
        $new_user = false;
    }
?>

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

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <?php if($new_user): ?>
                    <?php if (isset($component)) { $__componentOriginal791d26948561d5a0da3d85fee400a7b6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal791d26948561d5a0da3d85fee400a7b6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.welcome','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('welcome'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal791d26948561d5a0da3d85fee400a7b6)): ?>
<?php $attributes = $__attributesOriginal791d26948561d5a0da3d85fee400a7b6; ?>
<?php unset($__attributesOriginal791d26948561d5a0da3d85fee400a7b6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal791d26948561d5a0da3d85fee400a7b6)): ?>
<?php $component = $__componentOriginal791d26948561d5a0da3d85fee400a7b6; ?>
<?php unset($__componentOriginal791d26948561d5a0da3d85fee400a7b6); ?>
<?php endif; ?>
                <?php else: ?>

                <?php endif; ?>
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
<?php /**PATH /Users/slx/Code/chamu2/resources/views/dashboard.blade.php ENDPATH**/ ?>
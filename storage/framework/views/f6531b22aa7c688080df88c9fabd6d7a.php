<?php if(!$justLinks): ?>
<li class="nav-item dropdown dropdown-laungauge d-none d-sm-flex">
    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="avascript:;" data-bs-toggle="dropdown"><span class="flag-icon <?php echo e($currentLangData); ?>"></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li>
                <a class="dropdown-item d-flex align-items-center py-2" href="<?php echo e(route('language.switch',['id'=>$language->id])); ?>">
                <span class="flag-icon <?php echo e($language->emoji); ?>"></span>
                <span class="ms-2"><?php echo e($language->name); ?></span>
            </a>
        </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    </li>
<?php elseif($justLinks): ?>
    <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

        <?php if (isset($component)) { $__componentOriginal08be1f47856809f4c6eda68811b93273 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08be1f47856809f4c6eda68811b93273 = $attributes; } ?>
<?php $component = App\View\Components\AnchorTag::resolve(['href' => ''.e(route('language.switch',['id'=>$language->id])).'','text' => '<span class=\'flag-icon '.e($language->emoji).'\'></span> '.e($language->name).''] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('anchor-tag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\AnchorTag::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal08be1f47856809f4c6eda68811b93273)): ?>
<?php $attributes = $__attributesOriginal08be1f47856809f4c6eda68811b93273; ?>
<?php unset($__attributesOriginal08be1f47856809f4c6eda68811b93273); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal08be1f47856809f4c6eda68811b93273)): ?>
<?php $component = $__componentOriginal08be1f47856809f4c6eda68811b93273; ?>
<?php unset($__componentOriginal08be1f47856809f4c6eda68811b93273); ?>
<?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?><?php /**PATH C:\xampp\htdocs\faster_system\resources\views/components/flag-toggle.blade.php ENDPATH**/ ?>
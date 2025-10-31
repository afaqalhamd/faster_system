<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="<?php echo e($appDirection); ?>">
    <?php echo $__env->make('layouts.head', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        
    <body class="">
        <?php echo $__env->yieldContent('container'); ?>

        <?php echo $__env->make('layouts.script', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </body>

    
</html>
<?php /**PATH C:\xampp\htdocs\faster_system\resources\views/layouts/guest.blade.php ENDPATH**/ ?>
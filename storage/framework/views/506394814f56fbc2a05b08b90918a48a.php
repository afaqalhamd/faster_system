<label class="form-label <?php echo e($extraClass ?? ''); ?>" for="<?php echo e($for); ?>" id="<?php echo e($id); ?>" data-name="<?php echo e($labelDataName); ?>">
<?php echo $name; ?>

<?php if($optionalText): ?>
	<small class="text-muted">(<?php echo e(__("app.optional")); ?>)</small>
<?php endif; ?>
</label><?php /**PATH C:\xampp\htdocs\faster_system\resources\views/components/label.blade.php ENDPATH**/ ?>
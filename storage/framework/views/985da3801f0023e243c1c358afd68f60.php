<!-- Alert : Start-->
<?php
    $session = session('record');
?>
<?php if(isset($session['type'])): ?>
<div class="alert alert-<?php echo e($session['type']); ?> border-0 bg-<?php echo e($session['type']); ?> alert-dismissible fade show py-2">
    <div class="d-flex align-items-center">
        <div class="font-35 text-white">
            <?php if($session['type']=='success'): ?>
            <i class='bx bxs-check-circle'></i>
            <?php endif; ?>
            <?php if($session['type']=='danger'): ?>
            <i class='bx bxs-message-square-x'></i>
            <?php endif; ?>
            <?php if($session['type']=='info'): ?>
            <i class='bx bxs-info-square'></i>
            <?php endif; ?>
        </div>
        <div class="ms-3">
            <h6 class="mb-0 text-white"><?php echo e($session['status']); ?></h6>
            <div class="text-white">
                <?php if(isset($session['sms']) && $session['sms']!=null): ?>
                    <?php echo e(__('message.sms_status')); ?> : <?php echo e($session['sms']); ?>

                <?php endif; ?>
                <?php if(isset($session['email']) && $session['email']!=null): ?>
                    <?php echo e(__('message.email_status')); ?> : <?php echo e($session['email']); ?>

                <?php endif; ?>

                <?php if(isset($session['message']) && $session['message']!=null): ?>
                    <div class="text-dark">
                    <?php echo $session['message']; ?>

                    </div>
                <?php endif; ?>

                <?php echo e(session()->forget('record')); ?>


            </div>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<!-- Alert : End -->
<?php /**PATH C:\xampp\htdocs\faster_system\resources\views/layouts/session.blade.php ENDPATH**/ ?>
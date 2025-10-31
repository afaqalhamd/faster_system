<?php if(config('demo.enabled')): ?>
    <div class="col-12">
        <div class="text-center">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr class="bg-secondary text-white text-center align-middle">
                        <th><?php echo e(__('app.email')); ?></th>
                        <th><?php echo e(__('app.password')); ?></th>
                        <th><?php echo e(__('app.action')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center align-middle">admin@example.com</td>
                        <td class="text-center align-middle">12345678</td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-outline-primary px-2 admin"><?php echo e(__('app.apply')); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-center align-middle">seller@example.com</td>
                        <td class="text-center align-middle">12345678</td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-outline-primary px-2 seller"><?php echo e(__('app.apply')); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-center align-middle">purchase@example.com</td>
                        <td class="text-center align-middle">12345678</td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-outline-primary px-2 purchase"><?php echo e(__('app.apply')); ?></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\faster_system\resources\views/auth/demo-login.blade.php ENDPATH**/ ?>
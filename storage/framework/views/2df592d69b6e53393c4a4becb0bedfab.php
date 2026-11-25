<?php $__env->startSection('title', 'Change password'); ?>
<?php $__env->startSection('breadcrumb', 'Account security'); ?>
<?php $__env->startSection('content'); ?>
<div class="page-heading">
    <div><div class="eyebrow">ACCOUNT SECURITY</div><h1>Change your password</h1><p>Update your password before continuing to the inventory workspace.</p></div>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="panel p-4">
            <form method="POST" action="<?php echo e(route('password.update')); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <div class="mb-3">
                    <label class="form-label" for="current_password">Current password</label>
                    <input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small mt-1"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required>
                    <div class="form-text">At least 12 characters, including uppercase, lowercase, and a number.</div>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="text-danger small mt-1"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirm new password</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="12" autocomplete="new-password" required>
                </div>
                <button class="btn btn-primary">Update password</button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/auth/change-password.blade.php ENDPATH**/ ?>
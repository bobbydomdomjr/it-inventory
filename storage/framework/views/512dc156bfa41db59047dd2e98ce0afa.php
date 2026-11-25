<?php $__env->startSection('title', 'My Profile'); ?>
<?php $__env->startSection('breadcrumb', 'My Profile'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-heading">
    <div>
        <div class="eyebrow">ACCOUNT</div>
        <h1>My Profile</h1>
        <p>View and update your personal information and account security.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <section class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2>Personal information</h2>
                    <p>Update the contact information associated with your account.</p>
                </div>
            </div>
            <div class="p-3">
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <span class="avatar" style="width:42px;height:42px;font-size:15px;flex:0 0 42px;"><?php echo e(strtoupper(mb_substr($user->name, 0, 1))); ?></span>
                    <div class="min-w-0">
                        <div class="fw-semibold" style="font-size:13px;color:#344054;"><?php echo e($user->name); ?></div>
                        <div class="small text-secondary"><?php echo e($user->roles->pluck('name')->filter()->implode(', ') ?: 'No role assigned'); ?></div>
                    </div>
                    <span class="badge ms-auto <?php echo e($user->is_active ? 'text-bg-success' : 'text-bg-secondary'); ?>"><?php echo e($user->is_active ? 'Active' : 'Inactive'); ?></span>
                </div>

                <form method="POST" action="<?php echo e(route('profile.update')); ?>" autocomplete="on">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="profile_name" class="form-label">Full name</label>
                            <input id="profile_name" name="name" type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('name', $user->name)); ?>" maxlength="120" required autocomplete="name">
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="profile_email" class="form-label">Email address / username</label>
                            <input id="profile_email" name="email" type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('email', $user->email)); ?>" maxlength="254" required autocomplete="email">
                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                      </div>
                        <div class="col-md-6">
                            <label for="profile_phone" class="form-label">Contact number</label>
                            <input id="profile_phone" name="phone" type="tel" class="form-control <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('phone', $user->phone)); ?>" maxlength="50" autocomplete="tel" placeholder="Enter contact number">
                            <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-12" id="email-password-confirmation" hidden>
                            <label for="profile_current_password" class="form-label">Current password (required to change email)</label>
                            <input id="profile_current_password" name="current_password" type="password" class="form-control <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" autocomplete="current-password">
                            <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end gap-2 mt-3 pt-3 border-top">
                        <a href="<?php echo e(route('profile.edit')); ?>" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Save changes</button>
                    </div>
                </form>
                <p class="small text-secondary mt-2 mb-0">Your role, office, department, and employee assignment are maintained by an authorized administrator.</p>
            </div>
        </section>
    </div>

    <div class="col-xl-4">
        <section class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2>Account details</h2>
                    <p>Information assigned by your organization.</p>
                </div>
            </div>
            <div class="p-3">
                <dl class="row mb-0" style="font-size:11px;row-gap:12px;">
                    <dt class="col-5 text-secondary fw-medium">Role</dt>
                    <dd class="col-7 mb-0 text-end text-body"><?php echo e($user->roles->pluck('name')->filter()->implode(', ') ?: '—'); ?></dd>
                    <dt class="col-5 text-secondary fw-medium">Office</dt>
                    <dd class="col-7 mb-0 text-end text-body"><?php echo e($user->office?->name ?? '—'); ?></dd>
                    <dt class="col-5 text-secondary fw-medium">Department</dt>
                    <dd class="col-7 mb-0 text-end text-body"><?php echo e($user->department?->name ?? $user->employee?->department?->name ?? '—'); ?></dd>
                    <dt class="col-5 text-secondary fw-medium">Employee ID</dt>
                    <dd class="col-7 mb-0 text-end text-body"><?php echo e($user->employee?->employee_no ?? '—'); ?></dd>
                    <dt class="col-5 text-secondary fw-medium">Account status</dt>
                    <dd class="col-7 mb-0 text-end"><span class="status-pill <?php echo e($user->is_active ? 'active' : 'inactive'); ?>"><?php echo e($user->is_active ? 'Active' : 'Inactive'); ?></span></dd>
                    <dt class="col-5 text-secondary fw-medium">Last sign-in</dt>
                    <dd class="col-7 mb-0 text-end text-body"><?php echo e($user->last_login_at?->format('d M Y, H:i') ?? '—'); ?></dd>
                    <dt class="col-5 text-secondary fw-medium">Member since</dt>
                    <dd class="col-7 mb-0 text-end text-body"><?php echo e($user->created_at?->format('d M Y') ?? '—'); ?></dd>
                </dl>
            </div>
        </section>
    </div>
</div>

<div class="row g-3 mt-0">
    <div class="col-xl-8">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Password and security</h2>
                    <p>Change your password. Use at least 12 characters, including uppercase, lowercase, and a number.</p>
                </div>
                <i class="bi bi-shield-lock text-secondary" aria-hidden="true"></i>
            </div>
            <div class="p-3">
                <form method="POST" action="<?php echo e(route('profile.password.update')); ?>" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="current_password" class="form-label">Current password</label>
                            <input id="current_password" name="current_password" type="password" class="form-control <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" autocomplete="current-password" required>
                            <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-4">
                            <label for="password" class="form-label">New password</label>
                            <input id="password" name="password" type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" autocomplete="new-password" required>
                            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-4">
                            <label for="password_confirmation" class="form-label">Confirm new password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3 pt-3 border-top">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-key me-1"></i>Update password</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="panel h-100">
            <div class="panel-header">
                <div><h2>Account security</h2><p>Manage sign-in protection.</p></div>
            </div>
            <div class="p-3 d-flex flex-column gap-2">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <span class="small text-secondary">Multi-factor authentication</span>
                    <span class="status-pill <?php echo e($user->mfa_enabled ? 'active' : 'pending'); ?>"><?php echo e($user->mfa_enabled ? 'Enabled' : 'Not enabled'); ?></span>
                </div>
                <a href="<?php echo e(route('mfa.settings')); ?>" class="btn btn-outline-secondary align-self-start"><i class="bi bi-shield-lock me-1"></i>Manage MFA</a>
            </div>
        </section>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const email = document.getElementById('profile_email');
    const emailConfirmation = document.getElementById('email-password-confirmation');
    const currentPassword = document.getElementById('profile_current_password');
    if (!email || !emailConfirmation || !currentPassword) return;

    const originalEmail = <?php echo json_encode($user->email, 15, 512) ?>;
    const syncEmailConfirmation = () => {
        const changed = email.value.trim().toLowerCase() !== String(originalEmail).trim().toLowerCase();
        emailConfirmation.hidden = !changed;
        currentPassword.required = changed;
        if (!changed) currentPassword.value = '';
    };
    email.addEventListener('input', syncEmailConfirmation);
    syncEmailConfirmation();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/profile/edit.blade.php ENDPATH**/ ?>
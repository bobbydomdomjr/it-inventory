<?php $__env->startSection('title','User management'); ?>
<?php $__env->startSection('breadcrumb','User management'); ?>

<?php $__env->startPush('styles'); ?>
<style>
/* User register follows the compact Assets register structure and measurements. */
.users-list-page{font-size:12px;color:#344054;margin:-15px -15px 0}
.users-list-panel{background:#fff;border:1px solid #e4e7ec;border-radius:5px;box-shadow:0 1px 2px rgba(16,24,40,.035);overflow:hidden}
.users-list-toolbar{min-height:43px;padding:6px 9px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid #e6e9ed;background:#fff}
.users-toolbar-form,.users-toolbar-left,.users-toolbar-right{display:flex;align-items:center;gap:5px;min-width:0}
.users-toolbar-form{width:100%;justify-content:space-between}
.users-toolbar-select{height:29px;min-width:132px;padding:3px 25px 3px 8px;border:1px solid #d0d5dd;border-radius:3px;background-color:#fff;font-size:11px;color:#475467}
.users-mini-btn{height:29px;min-width:29px;padding:0 8px;border:1px solid #d0d5dd;border-radius:3px;background:#fff;color:#526273;display:inline-flex;align-items:center;justify-content:center;font-size:11px;text-decoration:none;white-space:nowrap}
.users-mini-btn:hover{background:#f5f7fa;color:#245b88;border-color:#aab7c4}
.users-mini-btn.primary{background:#347fa7;border-color:#347fa7;color:#fff}
.users-mini-btn.primary:hover{background:#286b91;color:#fff}
.users-quick-search{display:flex;align-items:center;width:min(280px,30vw)}
.users-quick-search input{height:29px;min-width:80px;width:100%;padding:4px 8px;border:1px solid #d0d5dd;border-right:0;border-radius:3px 0 0 3px;font-size:11px;outline:none}
.users-quick-search input:focus{border-color:#6c9fbd;box-shadow:0 0 0 2px rgba(52,127,167,.08)}
.users-quick-search button{height:29px;width:29px;border:1px solid #347fa7;border-radius:0 3px 3px 0;background:#347fa7;color:#fff;display:grid;place-items:center;padding:0}
.users-secondary-bar{min-height:38px;padding:5px 10px;display:flex;align-items:center;justify-content:space-between;gap:10px;background:#fff;border-bottom:1px solid #e6e9ed}
.users-record-meta{display:flex;align-items:center;gap:4px;font-size:11px;color:#606d7a;white-space:nowrap}
.users-record-meta strong{font-weight:600;color:#344054}
.users-page-size{height:25px;margin:0 2px;padding:1px 18px 1px 6px;border:1px solid #d0d5dd;border-radius:3px;background-color:#fff;color:#344054;font-size:11px}
.users-pagination{margin:0;display:flex;align-items:center;justify-content:flex-end}
.users-pagination .pagination{margin:0;gap:2px}
.users-pagination .page-link{min-width:25px;height:25px;padding:4px 7px;border:1px solid #e0e4e9;border-radius:3px!important;background:#fff;color:#475467;font-size:10px;line-height:15px;text-align:center}
.users-pagination .page-item.active .page-link{background:#347fa7;border-color:#347fa7;color:#fff}
.users-pagination .page-item.disabled .page-link{color:#a5adb7;background:#fafbfc}
.users-register-table{width:100%;min-width:860px;margin:0;border-collapse:separate;border-spacing:0;font-size:11px}
.users-register-table thead th{height:31px;padding:6px 7px;border-top:0;border-bottom:1px solid #dfe4e9;background:#f8f9fa;color:#46515d;font-size:10px;font-weight:700;white-space:nowrap;vertical-align:middle}
.users-register-table tbody tr{height:33px}
.users-register-table tbody tr:nth-child(even){background:#fafafa}
.users-register-table tbody tr:hover{background:#f1f7fb}
.users-register-table tbody td{height:33px;padding:3px 7px;border-bottom:1px solid #eef0f2;color:#667085;vertical-align:middle;white-space:nowrap}
.users-register-table tbody tr:last-child td{border-bottom:0}
.users-register-table .user-col{min-width:210px}
.users-register-table .actions-col{width:94px;text-align:right;padding-right:8px}
.users-cell{display:flex;align-items:center;gap:8px;min-width:0}
.users-avatar{width:25px;height:25px;flex:0 0 25px;display:inline-grid;place-items:center;border:1px solid #dce7ef;border-radius:3px;background:#f2f7fa;color:#347fa7;font-size:10px;font-weight:700}
.users-cell-copy{display:flex;flex-direction:column;min-width:0;line-height:1.25}
.users-cell-copy strong{max-width:260px;overflow:hidden;text-overflow:ellipsis;color:#344054;font-size:11px;font-weight:600}
.users-cell-copy span{max-width:260px;overflow:hidden;text-overflow:ellipsis;color:#8793a1;font-size:10px}
.users-row-actions{display:flex;align-items:center;justify-content:flex-end;gap:3px}
.users-row-actions form{margin:0;display:inline-flex}
.users-icon-action{width:22px;height:22px;display:inline-grid;place-items:center;padding:0;border:0;border-radius:2px;background:#24afd0;color:#fff;text-decoration:none;font-size:11px;line-height:1}
.users-icon-action.edit{background:#efa51c}
.users-icon-action.delete{background:#d94b3b}
.users-icon-action:hover{filter:brightness(.92);color:#fff}
.users-icon-action:focus-visible{outline:2px solid rgba(52,127,167,.28);outline-offset:2px}
.users-empty-state{padding:36px 12px!important;text-align:center!important;color:#929ba6!important;font-size:11px}
.users-empty-state i{font-size:19px;display:block;margin-bottom:4px}
.users-pagination-bottom{display:flex;justify-content:flex-end;padding:6px 9px;background:#fff}
@media(max-width:900px){.users-list-toolbar{align-items:flex-start;flex-direction:column}.users-toolbar-form{align-items:flex-start;flex-direction:column;gap:7px}.users-toolbar-left,.users-toolbar-right{width:100%;flex-wrap:wrap}.users-toolbar-right{justify-content:space-between}.users-quick-search{width:min(100%,360px)}.users-secondary-bar{align-items:flex-start;flex-direction:column}.users-pagination{width:100%;justify-content:flex-start}}
@media(max-width:540px){.users-list-page{margin:-10px -10px 0}.users-toolbar-select{flex:1;min-width:120px}.users-record-meta{flex-wrap:wrap;white-space:normal}}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $canManageUsers = auth()->user()->hasPermission('users.manage');
    $isSuperAdmin = auth()->user()->roles->contains('slug', 'super-admin');
    $editRoles = \App\Models\Role::where('slug', '!=', 'super-admin')->orderBy('name')->get();
    $editOffices = \App\Models\Office::where('is_active', true)->orWhereIn('id', $users->pluck('office_id')->filter()->all())->orderBy('name')->get();
    $activeOffices = \App\Models\Office::where('is_active', true)->orderBy('name')->get();
    $editEmployees = \App\Models\Employee::where('status', 'active')->orderBy('last_name')->get();
?>

<div class="users-list-page">
 <div class="users-list-panel">
  <div class="users-list-toolbar">
   <form method="GET" action="<?php echo e(route('admin.users')); ?>" class="users-toolbar-form" role="search">
    <div class="users-toolbar-left">
     <select class="users-toolbar-select" name="status" aria-label="Filter account status">
      <option value="">All account statuses</option>
      <option value="active" <?php if(request('status') === 'active'): echo 'selected'; endif; ?>>Active accounts</option>
      <option value="inactive" <?php if(request('status') === 'inactive'): echo 'selected'; endif; ?>>Inactive accounts</option>
     </select>
     <button type="submit" class="users-mini-btn" title="Apply filter" aria-label="Apply filter"><i class="bi bi-funnel"></i></button>
    </div>
    <div class="users-toolbar-right">
     <div class="users-quick-search">
      <input type="search" name="q" value="<?php echo e(request('q')); ?>" placeholder="Search name or email..." aria-label="Search users">
      <button type="submit" title="Search users" aria-label="Search"><i class="bi bi-search"></i></button>
     </div>
     <?php if(request()->filled('q') || request()->filled('status')): ?>
      <a class="users-mini-btn" href="<?php echo e(route('admin.users')); ?>" title="Clear search and filters" aria-label="Clear search and filters"><i class="bi bi-x-lg"></i></a>
     <?php endif; ?>
     <?php if($canManageUsers): ?>
      <button type="button" class="users-mini-btn primary" data-bs-toggle="modal" data-bs-target="#createUserModal" title="Add user" aria-label="Add user"><i class="bi bi-plus-lg"></i></button>
     <?php endif; ?>
     <a class="users-mini-btn" href="<?php echo e(route('admin.users', request()->only(['q','status','per_page']))); ?>" title="Refresh register" aria-label="Refresh"><i class="bi bi-arrow-clockwise"></i></a>
    </div>
   </form>
  </div>

  <div class="users-secondary-bar">
   <div class="users-record-meta">
    <span>Showing</span><strong><?php echo e($users->firstItem() ?? 0); ?></strong><span>to</span><strong><?php echo e($users->lastItem() ?? 0); ?></strong><span>of</span><strong><?php echo e(number_format($users->total())); ?></strong><span>accounts</span>
    <form method="GET" action="<?php echo e(route('admin.users')); ?>" class="d-inline-flex align-items-center ms-2 gap-1">
     <?php $__currentLoopData = request()->only(['q','status']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
     <label for="usersPerPage">Show</label>
     <select id="usersPerPage" class="users-page-size" name="per_page" aria-label="Rows per page" onchange="this.form.submit()">
      <?php $__currentLoopData = [10,20,25,50,100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $size): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($size); ?>" <?php if($users->perPage() === $size): echo 'selected'; endif; ?>><?php echo e($size); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
     </select><span>rows</span>
    </form>
   </div>
   <div class="users-pagination"><?php echo e($users->onEachSide(2)->links('pagination::bootstrap-5')); ?></div>
  </div>

  <div class="table-responsive">
   <table class="users-register-table">
    <thead><tr>
     <th class="user-col">USER</th><th>OFFICE</th><th>LINKED EMPLOYEE</th><th>ASSIGNED ROLE</th><th>CREATED</th><th>STATUS</th><th class="actions-col">ACTIONS</th>
    </tr></thead>
    <tbody>
     <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <?php
       $targetIsSuperAdmin = $user->roles->contains('slug', 'super-admin');
       $userInScope = $isSuperAdmin || (int) $user->office_id === (int) auth()->user()->office_id;
       $canManageTarget = $canManageUsers && $userInScope && ($isSuperAdmin || ! $targetIsSuperAdmin);
      ?>
      <tr>
       <td>
        <div class="users-cell">
         <span class="users-avatar"><?php echo e(strtoupper(substr($user->name, 0, 1))); ?></span>
         <span class="users-cell-copy"><strong><?php echo e($user->name); ?></strong><span><?php echo e($user->email); ?></span></span>
        </div>
       </td>
       <td><?php echo e($user->office?->short_name ?: $user->office?->name ?: '—'); ?></td>
       <td><?php echo e($user->employee?->full_name ?? '—'); ?></td>
       <td><?php echo e($user->roles->pluck('name')->join(', ') ?: 'No role assigned'); ?></td>
       <td><?php echo e($user->created_at?->format(config('app.date_format', 'd M Y'))); ?></td>
       <td><span class="status-pill <?php echo e($user->is_active ? 'active' : 'inactive'); ?>"><?php echo e($user->is_active ? 'Active' : 'Inactive'); ?></span></td>
       <td class="actions-col">
        <div class="users-row-actions">
         <?php if($userInScope): ?>
          <button type="button" class="users-icon-action" data-bs-toggle="modal" data-bs-target="#viewUserModal<?php echo e($user->id); ?>" title="View account" aria-label="View <?php echo e($user->name); ?>"><i class="bi bi-eye" aria-hidden="true"></i></button>
         <?php endif; ?>
         <?php if($canManageTarget): ?>
          <button type="button" class="users-icon-action edit" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo e($user->id); ?>" title="Edit account" aria-label="Edit <?php echo e($user->name); ?>"><i class="bi bi-pencil" aria-hidden="true"></i></button>
          <?php if((int) $user->id !== (int) auth()->id() && ! $targetIsSuperAdmin): ?>
           <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>" onsubmit="return confirm('Permanently delete this user account? This action cannot be undone.');">
            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <button type="submit" class="users-icon-action delete" title="Delete account" aria-label="Delete <?php echo e($user->name); ?>"><i class="bi bi-trash3" aria-hidden="true"></i></button>
           </form>
          <?php endif; ?>
         <?php endif; ?>
        </div>
       </td>
      </tr>
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <tr><td colspan="7" class="users-empty-state"><i class="bi bi-people"></i>No user accounts match the current filters.</td></tr>
     <?php endif; ?>
    </tbody>
   </table>
  </div>
  <div class="users-pagination-bottom"><div class="users-pagination"><?php echo e($users->onEachSide(2)->links('pagination::bootstrap-5')); ?></div></div>
 </div>
</div>

<?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $targetIsSuperAdmin = $user->roles->contains('slug', 'super-admin');
        $userInScope = $isSuperAdmin || (int) $user->office_id === (int) auth()->user()->office_id;
        $issuedCredentials = session('issued_user_credentials');
        $hasIssuedCredentials = is_array($issuedCredentials) && (int) ($issuedCredentials['user_id'] ?? 0) === (int) $user->id;
        $currentRole = $user->roles->first();
        $employeeOptions = $editEmployees->where('id', '!=', $user->employee_id);
        if ($user->employee) {
            $employeeOptions = $employeeOptions->prepend($user->employee);
        }
    ?>
    <?php if($userInScope): ?>
        <div class="modal fade" id="viewUserModal<?php echo e($user->id); ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div><h5 class="modal-title">User account</h5><p class="text-secondary small mb-0">Account details and printable sign-in credentials.</p></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><div class="user-detail-label">Full name</div><div class="user-detail-value"><?php echo e($user->name); ?></div></div>
                            <div class="col-md-6"><div class="user-detail-label">Email address</div><div class="user-detail-value"><?php echo e($user->email); ?></div></div>
                            <div class="col-md-6"><div class="user-detail-label">Office</div><div class="user-detail-value"><?php echo e($user->office?->name ?? 'Unassigned'); ?></div></div>
                            <div class="col-md-6"><div class="user-detail-label">Linked employee</div><div class="user-detail-value"><?php echo e($user->employee?->full_name ?? '—'); ?></div></div>
                            <div class="col-md-6"><div class="user-detail-label">Role</div><div class="user-detail-value"><?php echo e($user->roles->pluck('name')->join(', ') ?: 'No role assigned'); ?></div></div>
                            <div class="col-md-6"><div class="user-detail-label">Account status</div><div class="user-detail-value"><span class="status-pill <?php echo e($user->is_active ? 'active' : 'inactive'); ?>"><?php echo e($user->is_active ? 'Active' : 'Inactive'); ?></span></div></div>
                        </div>
                        <div class="credential-slip" id="userCredentialSlip<?php echo e($user->id); ?>">
                            <div class="credential-slip-head">
                                <div class="credential-slip-system"><?php echo e($systemBranding['short_name'] ?? config('app.name', 'IT Inventory')); ?></div>
                                <div class="credential-slip-title">USER ACCESS CREDENTIALS</div>
                            </div>
                            <div class="credential-slip-name"><?php echo e($user->name); ?></div>
                            <div class="credential-slip-row"><span>USERNAME</span><strong><?php echo e($user->email); ?></strong></div>
                            <div class="credential-slip-row"><span>TEMPORARY PASSWORD</span>
                                <?php if($hasIssuedCredentials): ?>
                                    <code><?php echo e($issuedCredentials['password']); ?></code>
                                <?php else: ?>
                                    <strong class="credential-unavailable">Not available</strong>
                                <?php endif; ?>
                            </div>
                            <div class="credential-slip-note">Sign in using these credentials. You will be asked to change the temporary password after signing in. Keep this information confidential.</div>
                            <?php if($hasIssuedCredentials): ?>
                                <div class="credential-slip-issued">Issued <?php echo e($issuedCredentials['issued_at'] ?? now()->format(config('app.date_format', 'd M Y'))); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if(! $hasIssuedCredentials): ?>
                            <p class="credential-security-note"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Passwords are securely stored and cannot be viewed. Generate a new temporary password to prepare a printable credential slip.</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <?php if($canManageUsers && $userInScope && ($isSuperAdmin || ! $targetIsSuperAdmin) && (int) $user->id !== (int) auth()->id()): ?>
                            <div class="d-flex gap-2">
                                <?php if($hasIssuedCredentials): ?>
                                    <button type="button" class="btn btn-outline-secondary" onclick="printUserCredentialSlip('userCredentialSlip<?php echo e($user->id); ?>')"><i class="bi bi-printer me-1" aria-hidden="true"></i>Print credentials</button>
                                <?php endif; ?>
                                <form method="POST" action="<?php echo e(route('admin.users.credentials', $user)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="page" value="<?php echo e($users->currentPage()); ?>">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-key me-1" aria-hidden="true"></i><?php echo e($hasIssuedCredentials ? 'Regenerate password' : 'Generate temporary password'); ?></button>
                                </form>
                            </div>
                        <?php elseif($hasIssuedCredentials): ?>
                            <button type="button" class="btn btn-outline-secondary" onclick="printUserCredentialSlip('userCredentialSlip<?php echo e($user->id); ?>')"><i class="bi bi-printer me-1" aria-hidden="true"></i>Print credentials</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if($canManageUsers): ?>
        <?php
            $currentRole = $user->roles->first();
        ?>
        <?php if($userInScope && ($isSuperAdmin || ! $targetIsSuperAdmin)): ?>
        <div class="modal fade" id="editUserModal<?php echo e($user->id); ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form class="modal-content" method="POST" action="<?php echo e(route('admin.users.update', $user)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="modal-header">
                        <div><h5 class="modal-title">Edit user account</h5><p class="text-secondary small mb-0">Update account details, office, employee link, and assigned role.</p></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="editName<?php echo e($user->id); ?>">Full name</label><input id="editName<?php echo e($user->id); ?>" class="form-control" name="name" value="<?php echo e($user->name); ?>" required maxlength="120"></div>
                            <div class="col-md-6"><label class="form-label" for="editEmail<?php echo e($user->id); ?>">Email address</label><input id="editEmail<?php echo e($user->id); ?>" class="form-control" type="email" name="email" value="<?php echo e($user->email); ?>" required maxlength="254"></div>
                            <div class="col-md-6"><label class="form-label" for="editEmployee<?php echo e($user->id); ?>">Linked employee</label><select id="editEmployee<?php echo e($user->id); ?>" class="form-select" name="employee_id"><option value="">No employee link</option><?php $__currentLoopData = $employeeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($employee->id); ?>" <?php if((int) $user->employee_id === (int) $employee->id): echo 'selected'; endif; ?>><?php echo e($employee->employee_no); ?> — <?php echo e($employee->full_name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                            <?php if($isSuperAdmin): ?>
                                <div class="col-md-6"><label class="form-label" for="editOffice<?php echo e($user->id); ?>">Assigned office</label><select id="editOffice<?php echo e($user->id); ?>" class="form-select" name="office_id"><option value="">Unassigned</option><?php $__currentLoopData = $editOffices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $office): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($office->id); ?>" <?php if((int) $user->office_id === (int) $office->id): echo 'selected'; endif; ?>><?php echo e($office->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                            <?php else: ?>
                                <input type="hidden" name="office_id" value="<?php echo e(auth()->user()->office_id); ?>">
                            <?php endif; ?>
                            <div class="col-md-6">
                                <label class="form-label" for="editRole<?php echo e($user->id); ?>">Assigned role</label>
                                <?php if($targetIsSuperAdmin): ?>
                                    <input class="form-control" value="Super Admin (protected role)" readonly aria-readonly="true">
                                <?php else: ?>
                                    <select id="editRole<?php echo e($user->id); ?>" class="form-select" name="role_id" required><option value="">Select a role</option><?php $__currentLoopData = $editRoles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($role->id); ?>" <?php if((int) optional($currentRole)->id === (int) $role->id): echo 'selected'; endif; ?>><?php echo e($role->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6"><label class="form-label" for="editPassword<?php echo e($user->id); ?>">Reset password <span class="text-secondary fw-normal">(optional)</span></label><input id="editPassword<?php echo e($user->id); ?>" class="form-control" type="password" name="password" minlength="12" maxlength="255" autocomplete="new-password" placeholder="Leave blank to keep current password"><div class="form-text">If set, the user will be required to change it at next sign-in.</div></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save changes</button></div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php if($canManageUsers): ?>
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="<?php echo e(route('admin.users.store')); ?>"><?php echo csrf_field(); ?><div class="modal-header"><div><h5 class="modal-title">Create user account</h5><p class="text-secondary small mb-0">Provide the account details and initial role.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Full name</label><input class="form-control" name="name" required maxlength="120"></div><div class="mb-3"><label class="form-label">Email address</label><input class="form-control" type="email" name="email" required></div><div class="mb-3"><label class="form-label">Linked employee (optional)</label><select class="form-select" name="employee_id"><option value="">No employee link</option><?php $__currentLoopData = \App\Models\Employee::where('status','active')->orderBy('last_name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($employee->id); ?>"><?php echo e($employee->employee_no); ?> — <?php echo e($employee->full_name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><div class="form-text">Linking an employee allows accountability and offboarding controls to deactivate their system account.</div></div><?php if($isSuperAdmin): ?><div class="mb-3"><label class="form-label">Assigned office</label><select class="form-select" name="office_id"><option value="">Unassigned</option><?php $__currentLoopData = $activeOffices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $office): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($office->id); ?>"><?php echo e($office->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><?php endif; ?><div class="mb-3"><label class="form-label">Temporary password</label><input class="form-control" type="password" name="password" required minlength="12"><div class="form-text">Use at least 12 characters. Share credentials securely.</div></div><div><label class="form-label">Role</label><select class="form-select" name="role_id" required><option value="">Select a role</option><?php $__currentLoopData = $editRoles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($role->id); ?>"><?php echo e($role->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create account</button></div></form></div></div>
<?php endif; ?>

<style>
    .user-detail-label{font-size:10px;font-weight:600;color:#8793a1;margin-bottom:3px}
    .user-detail-value{font-size:12px;color:#344054;font-weight:500}
    /* Compact credential card preview; the dashed edge is the cut line. */
    .credential-slip{width:100%;max-width:324px;min-height:0;margin:6px auto 0;padding:10px 11px;border:1px dashed #98a2b3;border-radius:2px;background:#fff;color:#344054}
    .credential-slip-head{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-bottom:5px;margin-bottom:6px;border-bottom:1px solid #e4e7ec}
    .credential-slip-system{font-size:10px;font-weight:700;color:#233c57}
    .credential-slip-title{font-size:7px;font-weight:700;letter-spacing:.07em;color:#667085;text-align:right}
    .credential-slip-name{font-size:11px;font-weight:700;margin-bottom:5px;color:#26384a}
    .credential-slip-row{display:block;padding:4px 0;border-bottom:1px solid #f0f2f4}
    .credential-slip-row span{display:block;font-size:7px;font-weight:700;letter-spacing:.045em;color:#7b8794;margin-bottom:2px}
    .credential-slip-row strong,.credential-slip-row code{display:block;font-size:9px;line-height:1.2;font-weight:700;color:#26384a;text-align:left;overflow-wrap:anywhere;word-break:break-word}
    .credential-slip-row code{width:fit-content;max-width:100%;padding:2px 4px;background:#f7f8fa;border:1px solid #e4e7ec;border-radius:2px}
    .credential-unavailable{font-weight:500!important;color:#98a2b3!important}
    .credential-slip-note{font-size:7px;line-height:1.3;color:#667085;margin-top:5px}
    .credential-slip-issued{font-size:6.5px;color:#98a2b3;text-align:right;margin-top:4px}
    .credential-security-note{margin:8px 0 0;color:#7b8794;font-size:10px}
</style>
<script>
    function printUserCredentialSlip(slipId) {
        const slip = document.getElementById(slipId);
        if (!slip) return;
        const printWindow = window.open('', '_blank', 'width=440,height=560');
        if (!printWindow) {
            alert('Please allow pop-ups to print the credential slip.');
            return;
        }
        printWindow.document.open();
        printWindow.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>User access credentials</title><style>
            @page{size:A4 portrait;margin:10mm}
            *{box-sizing:border-box}
            html,body{margin:0;padding:0;background:#fff}
            body{font-family:Arial,Helvetica,sans-serif;color:#344054;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            /* CR80-size handout card with a dashed perimeter as the cut guide. */
            .credential-slip{position:relative;width:85.6mm;height:54mm;max-width:85.6mm;margin:0;padding:3.5mm 4mm;border:0.35mm dashed #8b96a3;border-radius:0;background:#fff;overflow:hidden;break-inside:avoid;page-break-inside:avoid}
            .credential-slip-head{display:flex;align-items:center;justify-content:space-between;gap:3mm;padding-bottom:1.5mm;margin-bottom:1.8mm;border-bottom:0.2mm solid #dfe4e9}
            .credential-slip-system{font-size:7.5pt;font-weight:700;color:#233c57}
            .credential-slip-title{font-size:5pt;font-weight:700;letter-spacing:.06em;color:#667085;text-align:right}
            .credential-slip-name{font-size:8pt;font-weight:700;line-height:1.15;margin-bottom:1.4mm;color:#26384a}
            .credential-slip-row{display:block;padding:1mm 0;border-bottom:0.15mm solid #edf0f2}
            .credential-slip-row span{display:block;font-size:5.2pt;font-weight:700;letter-spacing:.04em;color:#7b8794;margin-bottom:.5mm}
            .credential-slip-row strong,.credential-slip-row code{display:block;max-width:100%;font-family:Arial,Helvetica,sans-serif;font-size:6.8pt;line-height:1.15;font-weight:700;color:#26384a;text-align:left;overflow-wrap:anywhere;word-break:break-word}
            .credential-slip-row code{width:fit-content;padding:.35mm 1mm;border:0.15mm solid #dfe4e9;border-radius:0;background:#f7f8fa}
            .credential-unavailable{font-weight:500!important;color:#98a2b3!important}
            .credential-slip-note{font-size:5pt;line-height:1.2;color:#667085;margin-top:1.5mm}
            .credential-slip-issued{font-size:4.8pt;color:#98a2b3;text-align:right;margin-top:1mm}
            @media print{body{margin:0}.credential-slip{margin:0}}
        </style></head><body>${slip.outerHTML}<script>window.addEventListener('load',function(){window.print();window.addEventListener('afterprint',function(){window.close();},{once:true});});<\/script></body></html>`);
        printWindow.document.close();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const userId = params.get('view_user');
        if (!userId || !window.bootstrap) return;
        const modalElement = document.getElementById('viewUserModal' + userId);
        if (!modalElement) return;
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
        params.delete('view_user');
        const cleanUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
        window.history.replaceState({}, document.title, cleanUrl);
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/admin/users.blade.php ENDPATH**/ ?>
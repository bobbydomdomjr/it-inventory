<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()->hasPermission('users.view'), 403);

        $query = User::with(['roles', 'employee', 'office'])->orderBy('name');

        $search = trim((string) request('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (request('status') === 'active') {
            $query->where('is_active', true);
        } elseif (request('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $perPage = (int) request('per_page', 20);
        if (! in_array($perPage, [10, 20, 25, 50, 100], true)) {
            $perPage = 20;
        }

        $users = $query->paginate($perPage)->withQueryString();

        $response = response()->view('admin.users', compact('users'));
        if (session()->has('issued_user_credentials')) {
            // Credential slips contain a one-time plaintext password. Do not let
            // browsers or shared proxies cache this response.
            $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id', 'unique:users,employee_id'],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
        ]);

        $selectedRole = \App\Models\Role::findOrFail($data['role_id']);
        abort_if($selectedRole->slug === 'super-admin', 422, 'The Super Administrator role cannot be assigned through ordinary user creation.');
        if (! $request->user()->roles->contains('slug', 'super-admin')) {
            abort_unless($request->user()->office_id, 403, 'Your account must be assigned to an office before creating users.');
            $data['office_id'] = $request->user()->office_id;
        }
        if (! empty($data['employee_id'])) {
            abort_unless(\App\Models\Employee::whereKey($data['employee_id'])->exists(), 422, 'The selected employee is outside your office or unavailable.');
        }

        // The account and role assignment are one operation. Previously the
        // user row could be committed before role sync failed, leaving an
        // account that appeared to have failed creation but was partly saved.
        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
                'employee_id' => $data['employee_id'] ?? null,
                'office_id' => $data['office_id'] ?? null,
            ]);

            $user->roles()->sync([(int) $data['role_id']]);

            return $user;
        }, 3);

        Audit::record('user_created', 'users', User::class, $user->id, [], [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => (int) $data['role_id'],
        ]);

        return back()->with('success', 'User account created.');
    }

    public function update(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('users.manage'), 403);

        $actorIsSuperAdmin = $actor->roles()->where('slug', 'super-admin')->exists();
        $targetIsSuperAdmin = $user->roles()->where('slug', 'super-admin')->exists();

        // Non-Super Administrators may only manage accounts in their own office
        // and may not inspect or modify a Super Administrator account.
        if (! $actorIsSuperAdmin) {
            abort_unless($actor->office_id && (int) $user->office_id === (int) $actor->office_id, 404);
            abort_if($targetIsSuperAdmin, 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email,' . $user->id],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id', 'unique:users,employee_id,' . $user->id],
            'office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'role_id' => [$targetIsSuperAdmin ? 'nullable' : 'required', 'integer', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:12', 'max:255'],
        ]);

        // Keep office ownership enforced for non-Super Administrators regardless
        // of what is submitted by the browser.
        if (! $actorIsSuperAdmin) {
            $data['office_id'] = $actor->office_id;
        }

        // The Super Administrator role is protected: it cannot be assigned to
        // ordinary accounts through the user editor, and existing Super Admins
        // retain their protected role while their profile details are updated.
        if ($targetIsSuperAdmin) {
            $roleId = $user->roles()->where('slug', 'super-admin')->value('roles.id');
        } else {
            $selectedRole = Role::findOrFail($data['role_id'] ?? 0);
            abort_if($selectedRole->slug === 'super-admin', 422, 'The Super Administrator role cannot be assigned through User Management.');
            $roleId = $selectedRole->id;
        }

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id,
            'office_id' => $user->office_id,
            'role_ids' => $user->roles()->pluck('roles.id')->all(),
        ];

        DB::transaction(function () use ($user, $data, $roleId, $targetIsSuperAdmin): void {
            $updates = [
                'name' => $data['name'],
                'email' => $data['email'],
                'employee_id' => $data['employee_id'] ?? null,
                'office_id' => $data['office_id'] ?? null,
            ];

            if (! empty($data['password'])) {
                $updates['password'] = Hash::make($data['password']);
                $updates['must_change_password'] = true;
                $updates['password_changed_at'] = null;
            }

            $user->update($updates);

            if (! $targetIsSuperAdmin) {
                $user->roles()->sync([$roleId]);
            }
        }, 3);

        $user->refresh();
        Audit::record('user_updated', 'users', User::class, $user->id, $before, [
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id,
            'office_id' => $user->office_id,
            'role_ids' => $user->roles()->pluck('roles.id')->all(),
        ]);

        return back()->with('success', 'User account updated.');
    }

    public function issueCredentials(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('users.manage'), 403);

        $actorIsSuperAdmin = $actor->roles()->where('slug', 'super-admin')->exists();
        if (! $actorIsSuperAdmin) {
            abort_unless($actor->office_id && (int) $user->office_id === (int) $actor->office_id, 404);
            abort_if($user->roles()->where('slug', 'super-admin')->exists(), 404);
        }

        // Existing passwords are hashes and cannot be retrieved. Issue a new
        // one-time temporary password instead, and require a change at login.
        $temporaryPassword = Str::password(20, true, true, false);
        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'password_changed_at' => null,
        ])->save();

        Audit::record('user_credentials_issued', 'users', User::class, $user->id, [], [
            'username' => $user->email,
            'must_change_password' => true,
        ]);

        session()->flash('issued_user_credentials', [
            'user_id' => $user->id,
            'username' => $user->email,
            'password' => $temporaryPassword,
            'issued_at' => now()->format(config('app.date_format', 'd M Y')),
        ]);

        $page = max(1, (int) $request->input('page', 1));

        return redirect()->route('admin.users', [
            'page' => $page,
            'view_user' => $user->id,
        ])->with('success', 'A new temporary password was generated. Print the credential slip and provide it securely to the user.');
    }

    public function destroy(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($actor->hasPermission('users.manage'), 403);

        $actorIsSuperAdmin = $actor->roles()->where('slug', 'super-admin')->exists();
        if (! $actorIsSuperAdmin) {
            abort_unless($actor->office_id && (int) $user->office_id === (int) $actor->office_id, 404);
        }

        abort_if((int) $user->id === (int) $actor->id, 422, 'You cannot delete your own account.');
        abort_if($user->roles()->where('slug', 'super-admin')->exists(), 422, 'Super Administrator accounts are protected and cannot be deleted.');

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'office_id' => $user->office_id,
            'role_ids' => $user->roles()->pluck('roles.id')->all(),
        ];

        DB::transaction(function () use ($user): void {
            $user->roles()->detach();
            $user->delete();
        }, 3);

        Audit::record('user_deleted', 'users', User::class, $user->id, $before, []);

        return back()->with('success', 'User account deleted.');
    }

    public function toggle(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        if (! $request->user()->roles()->where('slug', 'super-admin')->exists()) abort_unless((int) $user->office_id === (int) $request->user()->office_id, 404);
        abort_if($user->id === $request->user()->id, 422, 'You cannot deactivate your own account.');

        if ($user->is_active && $user->roles()->where('slug', 'super-admin')->exists()
            && User::where('is_active', true)->whereHas('roles', fn ($query) => $query->where('slug', 'super-admin'))->count() <= 1) {
            abort(422, 'The last active Super Administrator account cannot be deactivated.');
        }

        $before = $user->is_active;
        $user->update(['is_active' => ! $before]);
        Audit::record('user_status_changed', 'users', User::class, $user->id, ['is_active' => $before], ['is_active' => $user->is_active]);

        return back()->with('success', 'User status updated.');
    }

    public function roles()
    {
        abort_unless(request()->user()->hasPermission('users.manage'), 403);

        $roles = Role::with('permissions')->get();
        $permissions = Permission::orderBy('module')->get()->groupBy('module');

        return view('admin.roles', compact('roles', 'permissions'));
    }

    public function updateRole(Request $request, Role $role)
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        abort_if($role->slug === 'super-admin', 422, 'The Super Administrator role is protected from permission changes.');

        $data = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
        $officePermissionIds = Permission::whereIn('name', ['offices.view', 'offices.manage'])->pluck('id')->all();
        abort_if($role->slug !== 'system-admin' && array_intersect($officePermissionIds, $data['permissions'] ?? []), 422, 'Office administration permissions are reserved for system administrators.');

        DB::transaction(fn () => $role->permissions()->sync($data['permissions'] ?? []), 3);
        Audit::record('role_permissions_updated', 'users', Role::class, $role->id);

        return back()->with('success', 'Role permissions updated.');
    }
}

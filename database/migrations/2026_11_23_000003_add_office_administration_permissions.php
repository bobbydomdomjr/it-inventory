<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'offices.view' => ['View office administration', 'view'],
            'offices.manage' => ['Manage offices', 'manage'],
        ] as $name => [$label, $action]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['label' => $label, 'module' => 'offices', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('name', ['offices.view', 'offices.manage'])->pluck('id', 'name');
        $roleIds = DB::table('roles')->whereIn('slug', ['super-admin', 'system-admin'])->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', ['offices.view', 'offices.manage'])->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};

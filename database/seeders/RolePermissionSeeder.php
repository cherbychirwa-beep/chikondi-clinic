<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

/** Syncs roles, permissions and the role_permissions matrix from App\Support\Permissions. Safe to re-run. */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::PERMISSIONS as $slug => [$name, $module]) {
            Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'module' => $module]);
        }

        foreach (Permissions::ROLES as $slug => [$name, $description]) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description]);

            $ids = Permission::whereIn('slug', Permissions::matrix()[$slug])->pluck('id')->all();
            $role->permissions()->sync($ids);
        }
    }
}

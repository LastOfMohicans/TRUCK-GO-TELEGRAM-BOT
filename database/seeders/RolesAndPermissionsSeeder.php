<?php

namespace Database\Seeders;

use App\Enums\Permissions;
use App\Enums\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create roles and assign created permissions
        $vendorRole = Role::firstOrCreate(['guard_name' => 'vendor', 'name' => Roles::Vendor->value]);
        $managerRole = Role::firstOrCreate(['guard_name' => 'vendor', 'name' => Roles::VendorManager->value]);
        $adminManagerRole = Role::firstOrCreate(['guard_name' => 'vendor', 'name' => Roles::VendorAdminManager->value]);

        $permission = Permission::firstOrCreate(['guard_name' => $vendorRole->guard_name, 'name' => Permissions::EditProfile->value]);
        $permission = Permission::firstOrCreate(['guard_name' => $adminManagerRole->guard_name, 'name' => Permissions::EditProfile->value]);

        $vendorRole->givePermissionTo($permission);
    }
}

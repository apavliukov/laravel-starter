<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Policies\Permissions;
use Illuminate\Database\Seeder;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::getAllPermissions() as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }
}

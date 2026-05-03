<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Policies\Role as RoleEnum;
use App\Models\Role;
use App\Policies\Permissions;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleEnum::cases() as $roleEnum) {
            /** @var Role $role */
            $role = Role::query()->firstOrCreate(
                ['name' => $roleEnum->value, 'guard_name' => 'web'],
            );

            $rolePermissions = Permissions::getPermissionsByRole($role);

            if ($rolePermissions !== []) {
                $role->syncPermissions($rolePermissions);
            }
        }
    }
}

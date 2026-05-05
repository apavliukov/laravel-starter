<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Policies\Role as RoleEnum;
use App\Helpers\Policies\PermissionRegistry;
use App\Models\Role;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function __construct(private readonly PermissionRegistry $registry) {}

    public function run(): void
    {
        foreach (RoleEnum::cases() as $roleEnum) {
            /** @var Role $role */
            $role = Role::query()->firstOrCreate(
                ['name' => $roleEnum->value, 'guard_name' => 'web'],
            );

            $rolePermissions = $this->registry->forRole($roleEnum);

            if ($rolePermissions !== []) {
                $role->syncPermissions($rolePermissions);
            }
        }
    }
}

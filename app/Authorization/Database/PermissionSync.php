<?php

declare(strict_types=1);

namespace App\Authorization\Database;

use App\Authorization\AuthorizationManager;
use App\Authorization\PermissionRegistry;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

final readonly class PermissionSync
{
    public function __construct(
        private PermissionRegistry $registry,
        private AuthorizationManager $manager,
    ) {}

    public function permissions(): void
    {
        /** @var class-string<Model> $permissionClass */
        $permissionClass = config('permission.models.permission');

        foreach ($this->registry->allPermissions() as $permissionName) {
            $permissionClass::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }

    public function roles(): void
    {
        /** @var class-string<Role> $roleClass */
        $roleClass = config('permission.models.role');
        $roleEnum = $this->manager->roleEnum();

        foreach ($roleEnum::cases() as $role) {
            $spatieRole = $roleClass::query()->firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'web',
            ]);

            $rolePermissions = $role->permissions();

            if ($rolePermissions !== []) {
                $spatieRole->syncPermissions($rolePermissions);
            }
        }
    }
}

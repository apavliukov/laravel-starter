<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Helpers\Policies\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

final class PermissionSeeder extends Seeder
{
    public function __construct(private readonly PermissionRegistry $registry) {}

    public function run(): void
    {
        /** @var Permission $permissionClass */
        $permissionClass = config('permission.models.permission');

        foreach ($this->registry->allPermissions() as $permissionName) {
            $permissionClass::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Helpers\Policies\PermissionRegistry;
use App\Models\Permission;
use Illuminate\Database\Seeder;

final class PermissionSeeder extends Seeder
{
    public function __construct(private readonly PermissionRegistry $registry) {}

    public function run(): void
    {
        foreach ($this->registry->allPermissions() as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }
}

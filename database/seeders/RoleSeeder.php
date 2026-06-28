<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Authorization\Database\PermissionSync;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function __construct(private readonly PermissionSync $sync) {}

    public function run(): void
    {
        $this->sync->roles();
    }
}

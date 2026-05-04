<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs on every deploy to ensure system data consistency.
 * Each child seeder uses updateOrCreate to seed/sync required records idempotently.
 */
final class ConsistencySeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);
    }
}

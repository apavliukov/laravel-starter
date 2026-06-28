<?php

declare(strict_types=1);

namespace Database\Seeders;

use AlexPavliukov\Authorization\Database\AuthorizationSeeder;
use Illuminate\Database\Seeder;

/**
 * Runs on every deploy to ensure system data consistency.
 * Each child seeder syncs required records idempotently.
 */
final class ConsistencySeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthorizationSeeder::class,
        ]);
    }
}

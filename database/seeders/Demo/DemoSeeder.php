<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;

/**
 * Demo data for local development. Not invoked by `db:seed` (which runs
 * DatabaseSeeder). Invoke explicitly: `db:seed --class="Database\\Seeders\\Demo\\DemoSeeder"`.
 *
 * Each child seeder is idempotent — safe to re-run.
 */
final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use AlexPavliukov\Authorization\Database\AuthorizationSeeder;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthorizationSeeder::class,
        ]);
    }
}

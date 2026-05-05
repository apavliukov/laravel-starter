<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Enums\Policies\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@demo.local', 'first_name' => 'Demo', 'last_name' => 'Admin', 'role' => RoleEnum::ADMIN],
            ['email' => 'member@demo.local', 'first_name' => 'Demo', 'last_name' => 'Member', 'role' => RoleEnum::MEMBER],
        ];

        foreach ($users as $row) {
            $user = User::query()->firstOrCreate(
                ['email' => $row['email']],
                [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->hasRole($row['role'])) {
                $user->assignRole($row['role']);
            }
        }
    }
}

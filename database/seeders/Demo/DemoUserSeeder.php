<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Actions\Users\CreateUser;
use App\Dto\Users\CreateUserInput;
use App\Enums\Policies\Role as RoleEnum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(['name' => 'Demo Organization']);

        $users = [
            ['email' => 'admin@demo.local', 'first_name' => 'Demo', 'last_name' => 'Admin', 'role' => RoleEnum::ADMIN, 'organization' => null],
            ['email' => 'member@demo.local', 'first_name' => 'Demo', 'last_name' => 'Member', 'role' => RoleEnum::MEMBER, 'organization' => $organization],
        ];

        $createUser = resolve(CreateUser::class);

        foreach ($users as $row) {
            if (User::query()->where('email', $row['email'])->exists()) {
                continue;
            }

            $user = $createUser(new CreateUserInput(
                firstName: $row['first_name'],
                lastName: $row['last_name'],
                email: $row['email'],
                password: 'password',
                role: $row['role'],
                organizationId: $row['organization']?->id,
            ));

            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }
}

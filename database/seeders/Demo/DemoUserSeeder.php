<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Actions\Organizations\AddOrganizationMember;
use App\Actions\Organizations\CreateOrganization;
use App\Actions\Users\CreateUser;
use App\Dto\Organizations\CreateOrganizationInput;
use App\Dto\Users\CreateUserInput;
use App\Enums\Policies\Role as RoleEnum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DemoUserSeeder extends Seeder
{
    private const string ORGANIZATION = 'Demo Organization';

    public function run(): void
    {
        $users = [
            ['email' => 'admin@demo.local', 'first_name' => 'Demo', 'last_name' => 'Admin', 'platform_admin' => true],
            ['email' => 'owner@demo.local', 'first_name' => 'Demo', 'last_name' => 'Owner', 'platform_admin' => false],
            ['email' => 'member@demo.local', 'first_name' => 'Demo', 'last_name' => 'Member', 'platform_admin' => false],
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
                platformAdmin: $row['platform_admin'],
            ));

            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $organization = Organization::query()->firstWhere('name', self::ORGANIZATION)
            ?? resolve(CreateOrganization::class)(new CreateOrganizationInput(name: self::ORGANIZATION, ownerEmail: 'owner@demo.local'));

        $member = User::query()->where('email', 'member@demo.local')->firstOrFail();

        resolve(AddOrganizationMember::class)($organization, $member, RoleEnum::MEMBER);
    }
}

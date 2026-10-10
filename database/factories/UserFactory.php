<?php

declare(strict_types=1);

namespace Database\Factories;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Enums\Policies\Role as RoleEnum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): self
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    /**
     * Super-admin roles are global; every other role is a membership in an
     * organization, created on the fly when none is given.
     */
    public function withRole(RoleEnum $role, ?Organization $organization = null): self
    {
        return $this->afterCreating(function (User $user) use ($role, $organization): void {
            $membership = resolve(TenantMembership::class);

            if ($role->isSuperAdmin()) {
                $membership->assignGlobal($user, $role);

                return;
            }

            $membership->add($user, $organization ?? Organization::factory()->create(), $role);
        });
    }

    public function admin(): self
    {
        return $this->withRole(RoleEnum::ADMIN);
    }

    public function member(?Organization $organization = null): self
    {
        return $this->withRole(RoleEnum::MEMBER, $organization);
    }
}

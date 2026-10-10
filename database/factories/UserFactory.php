<?php

declare(strict_types=1);

namespace Database\Factories;

use AlexPavliukov\Authorization\Authorization;
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
            'organization_id' => null,
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

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (): array => ['organization_id' => $organization->id]);
    }

    /** ADMIN is a global assignment; every other role lives in the user's organization team. */
    public function withRole(RoleEnum $role): self
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            if ($role->isSuperAdmin()) {
                Authorization::withTeam(null, static fn (): User => $user->assignRole($role));
                Authorization::forgetUserRoles($user);

                return;
            }

            resolve(TenantMembership::class)->add($user, $user->organization_id, $role);
        });
    }

    public function admin(): self
    {
        return $this->withRole(RoleEnum::ADMIN);
    }

    /** Creates an organization when none was given — a member without one is an invalid state. */
    public function member(?Organization $organization = null): self
    {
        return $this
            ->state(fn (array $attributes): array => [
                'organization_id' => $organization?->id ?? $attributes['organization_id'] ?? Organization::factory(),
            ])
            ->withRole(RoleEnum::MEMBER);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Policies\Role as RoleEnum;
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
            'is_active' => true,
        ];
    }

    public function unverified(): self
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function withRole(RoleEnum $role): self
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            $user->assignRole($role->value);
        });
    }

    public function admin(): self
    {
        return $this->withRole(RoleEnum::ADMIN);
    }

    public function member(): self
    {
        return $this->withRole(RoleEnum::MEMBER);
    }
}

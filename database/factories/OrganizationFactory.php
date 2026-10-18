<?php

declare(strict_types=1);

namespace Database\Factories;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
final class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'owner_id' => User::factory(),
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(static function (Organization $organization): void {
            resolve(TenantMembership::class)->transferOwnership($organization, $organization->owner()->firstOrFail());
        });
    }

    public function ownedBy(User $user): self
    {
        return $this->state(fn (): array => ['owner_id' => $user->getKey()]);
    }
}

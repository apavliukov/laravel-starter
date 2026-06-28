<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Authorization\Contracts\AuthorizationRole;
use App\Authorization\Contracts\TeamResolver;
use App\Authorization\Teams\DefaultTeamResolver;
use BackedEnum;
use RuntimeException;

final class AuthorizationManager
{
    /** @var class-string<AuthorizationRole&BackedEnum>|null */
    private ?string $roleEnum = null;

    /** @var array<int, class-string> */
    private array $models = [];

    /** @var class-string<TeamResolver> */
    private string $teamResolver = DefaultTeamResolver::class;

    /** @param class-string<AuthorizationRole&BackedEnum> $roleEnum */
    public function useRoleEnum(string $roleEnum): void
    {
        $this->roleEnum = $roleEnum;
    }

    /** @return class-string<AuthorizationRole&BackedEnum> */
    public function roleEnum(): string
    {
        return $this->roleEnum ?? throw new RuntimeException('Role enum is not configured. Call Authorization::useRoleEnum() in AuthorizationServiceProvider.');
    }

    /** @param array<int, class-string> $models */
    public function authorizableModels(array $models): void
    {
        $this->models = $models;
    }

    /** @return array<int, class-string> */
    public function models(): array
    {
        return $this->models;
    }

    /**
     * Role cases that bypass Gate::before.
     *
     * @return array<int, AuthorizationRole&BackedEnum>
     */
    public function superAdminRoles(): array
    {
        $roleEnum = $this->roleEnum();

        return array_values(array_filter(
            $roleEnum::cases(),
            static fn (AuthorizationRole $role): bool => $role->isSuperAdmin(),
        ));
    }

    /** @param class-string<TeamResolver> $resolverClass */
    public function resolveTeamsUsing(string $resolverClass): void
    {
        $this->teamResolver = $resolverClass;
    }

    public function teamResolver(): TeamResolver
    {
        return resolve($this->teamResolver);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Fixtures\Authorization;

use App\Authorization\Contracts\AuthorizationRole;

enum GrantingRole: string implements AuthorizationRole
{
    case MANAGER = 'manager';

    public function isSuperAdmin(): bool
    {
        return match ($this) {
            self::MANAGER => false,
        };
    }

    /** @return array<int, string> */
    public function permissions(): array
    {
        return match ($this) {
            self::MANAGER => ['view any users'],
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Roles;

trait HasRolePresentation
{
    public function layout(): string
    {
        return match ($this) {
            self::ADMIN => 'platform',
            self::OWNER, self::MEMBER => 'member',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ADMIN => 'red',
            self::OWNER => 'amber',
            self::MEMBER => 'zinc',
        };
    }
}

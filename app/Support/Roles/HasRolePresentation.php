<?php

declare(strict_types=1);

namespace App\Support\Roles;

trait HasRolePresentation
{
    public function layout(): string
    {
        return match ($this) {
            self::ADMIN => 'platform',
            self::MEMBER => 'member',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ADMIN => 'red',
            self::MEMBER => 'zinc',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums\Policies;

use AlexPavliukov\Authorization\Contracts\AuthorizationRole;
use App\Contracts\Enums\HasLabelsInterface;
use App\Contracts\Enums\StringMatchInterface;
use App\Support\Roles\HasRolePresentation;
use App\Traits\Enums\HasLabels;
use App\Traits\Enums\HasValues;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;

enum Role: string implements AuthorizationRole, HasLabelsInterface, StringMatchInterface
{
    use HasLabels;
    use HasRolePresentation;
    use HasValues;

    case ADMIN = 'admin';
    case MEMBER = 'member';

    public static function default(): self
    {
        return self::MEMBER;
    }

    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @return array<string>|string|Translator|Application|null
     */
    public function label(): array|string|Translator|Application|null
    {
        return match ($this) {
            self::ADMIN => __('Admin'),
            self::MEMBER => __('Member'),
        };
    }

    public function isSuperAdmin(): bool
    {
        return match ($this) {
            self::ADMIN => true,
            self::MEMBER => false,
        };
    }

    /** @return array<int, string> */
    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN, self::MEMBER => [],
        };
    }
}

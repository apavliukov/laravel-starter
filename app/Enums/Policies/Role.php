<?php

declare(strict_types=1);

namespace App\Enums\Policies;

use AlexPavliukov\Authorization\Contracts\AuthorizationRole;
use AlexPavliukov\Authorization\Enums\Ability;
use AlexPavliukov\Authorization\Support\Permissions;
use App\Contracts\Enums\HasLabelsInterface;
use App\Contracts\Enums\StringMatchInterface;
use App\Models\Organization;
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
    case OWNER = 'owner';
    case MEMBER = 'member';

    public static function default(): self
    {
        return self::MEMBER;
    }

    /**
     * Highest first: decides `$user->app_role` (landing page, role badge) for a user
     * holding several roles. A product decision, not a permission count.
     *
     * @return list<self>
     */
    public static function byPriority(): array
    {
        return [self::ADMIN, self::OWNER, self::MEMBER];
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
            self::OWNER => __('Owner'),
            self::MEMBER => __('Member'),
        };
    }

    public function isSuperAdmin(): bool
    {
        return match ($this) {
            self::ADMIN => true,
            self::OWNER, self::MEMBER => false,
        };
    }

    /**
     * ADMIN is the super-admin bypass. OWNER is the organization's full set — every
     * lower organization role's grants plus ownership transfer (concentric roles are
     * a convention of this app, not a package rule). MEMBER's empty array is an
     * active deny-all.
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN, self::MEMBER => [],
            self::OWNER => Permissions::make()
                ->for(Organization::class)->only(Ability::VIEW, Ability::UPDATE, OrganizationAbility::TRANSFER_OWNERSHIP)
                ->all(),
        };
    }
}

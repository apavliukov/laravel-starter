<?php

declare(strict_types=1);

namespace Tests\Unit\Enums\Policies;

use AlexPavliukov\Authorization\Contracts\AuthorizationRole;
use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(Role::class)]
final class RoleTest extends TestCase
{
    #[Test]
    public function it_implements_the_authorization_role_contract(): void
    {
        $this->assertInstanceOf(AuthorizationRole::class, Role::ADMIN);
    }

    #[Test]
    public function admin_is_super_admin(): void
    {
        $this->assertTrue(Role::ADMIN->isSuperAdmin());
    }

    #[Test]
    public function owner_and_member_are_not_super_admins(): void
    {
        $this->assertFalse(Role::OWNER->isSuperAdmin());
        $this->assertFalse(Role::MEMBER->isSuperAdmin());
    }

    #[Test]
    public function by_priority_ranks_every_case_exactly_once(): void
    {
        $this->assertSame([Role::ADMIN, Role::OWNER, Role::MEMBER], Role::byPriority());
        $this->assertEqualsCanonicalizing(Role::cases(), Role::byPriority());
    }

    #[Test]
    public function admin_and_member_grant_no_permissions(): void
    {
        $this->assertSame([], Role::ADMIN->permissions());
        $this->assertSame([], Role::MEMBER->permissions());
    }

    #[Test]
    public function owner_holds_the_organization_set_including_ownership_transfer(): void
    {
        $this->assertEqualsCanonicalizing(
            ['view organizations', 'update organizations', 'transfer ownership organizations'],
            Role::OWNER->permissions(),
        );
    }

    #[Test]
    public function admin_uses_the_platform_layout(): void
    {
        $this->assertSame('platform', Role::ADMIN->layout());
    }

    #[Test]
    public function owner_and_member_use_the_member_layout(): void
    {
        $this->assertSame('member', Role::OWNER->layout());
        $this->assertSame('member', Role::MEMBER->layout());
    }

    #[Test]
    public function each_role_exposes_a_badge_color(): void
    {
        $this->assertSame('red', Role::ADMIN->badgeColor());
        $this->assertSame('amber', Role::OWNER->badgeColor());
        $this->assertSame('zinc', Role::MEMBER->badgeColor());
    }
}

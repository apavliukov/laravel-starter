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
    public function member_is_not_super_admin(): void
    {
        $this->assertFalse(Role::MEMBER->isSuperAdmin());
    }

    #[Test]
    public function roles_grant_no_extra_permissions_by_default(): void
    {
        $this->assertSame([], Role::ADMIN->permissions());
        $this->assertSame([], Role::MEMBER->permissions());
    }

    #[Test]
    public function admin_uses_the_platform_layout(): void
    {
        $this->assertSame('platform', Role::ADMIN->layout());
    }

    #[Test]
    public function member_uses_the_member_layout(): void
    {
        $this->assertSame('member', Role::MEMBER->layout());
    }

    #[Test]
    public function each_role_exposes_a_badge_color(): void
    {
        $this->assertSame('red', Role::ADMIN->badgeColor());
        $this->assertSame('zinc', Role::MEMBER->badgeColor());
    }
}

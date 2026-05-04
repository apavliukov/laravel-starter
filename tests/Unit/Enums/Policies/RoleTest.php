<?php

declare(strict_types=1);

namespace Tests\Unit\Enums\Policies;

use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RoleTest extends TestCase
{
    #[Test]
    public function admin_uses_red_badge_color(): void
    {
        $this->assertSame('red', Role::ADMIN->badgeColor());
    }

    #[Test]
    public function member_uses_zinc_badge_color(): void
    {
        $this->assertSame('zinc', Role::MEMBER->badgeColor());
    }
}

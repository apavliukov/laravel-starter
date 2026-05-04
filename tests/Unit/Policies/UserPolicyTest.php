<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserPolicyTest extends TestCase
{
    #[Test]
    public function admin_can_view_any_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(new UserPolicy()->viewAny($admin));
    }

    #[Test]
    public function member_cannot_view_any_users(): void
    {
        $member = User::factory()->member()->create();

        $this->assertFalse(new UserPolicy()->viewAny($member));
    }
}

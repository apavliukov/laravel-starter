<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization;

use App\Authorization\Enums\SystemAbility;
use App\Authorization\Support\BypassStrategy;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(BypassStrategy::class)]
final class BypassStrategyTest extends TestCase
{
    #[Test]
    public function super_admin_bypasses_any_ability(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('any.unregistered.ability'));
    }

    #[Test]
    public function non_super_admin_does_not_bypass(): void
    {
        $member = User::factory()->member()->create();

        $this->assertFalse(Gate::forUser($member)->allows('any.unregistered.ability'));
    }

    #[Test]
    public function platform_admin_access_is_granted_to_super_admin_only(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();

        $this->assertTrue(Gate::forUser($admin)->allows(SystemAbility::ACCESS_PLATFORM_ADMIN));
        $this->assertFalse(Gate::forUser($member)->allows(SystemAbility::ACCESS_PLATFORM_ADMIN));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Authorization\Database\PermissionSync;
use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(PermissionSync::class)]
final class PermissionSyncTest extends TestCase
{
    #[Test]
    public function it_creates_a_permission_for_every_user_ability(): void
    {
        resolve(PermissionSync::class)->permissions();

        $this->assertDatabaseHas('permissions', ['name' => 'view any users', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'force delete users', 'guard_name' => 'web']);
        $this->assertSame(7, Permission::query()->count());
    }

    #[Test]
    public function it_creates_every_role(): void
    {
        resolve(PermissionSync::class)->permissions();
        resolve(PermissionSync::class)->roles();

        foreach (Role::cases() as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role->value, 'guard_name' => 'web']);
        }

        $this->assertSame(count(Role::cases()), SpatieRole::query()->count());
    }
}

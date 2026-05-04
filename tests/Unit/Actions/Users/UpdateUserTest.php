<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Users;

use App\Actions\Users\UpdateUser;
use App\Dto\Users\UpdateUserInput;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('actions')]
#[Group('users')]
#[CoversClass(UpdateUser::class)]
final class UpdateUserTest extends TestCase
{
    private UpdateUser $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(UpdateUser::class);
    }

    #[Test]
    public function updates_fields_and_role_and_rehashes_password_when_provided(): void
    {
        $user = User::factory()->member()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.test',
        ]);
        $originalHash = $user->password;

        $updated = ($this->action)($user, new UpdateUserInput(
            firstName: 'New',
            lastName: 'Name',
            email: 'new@example.test',
            password: 'fresh-pass',
            role: Role::ADMIN,
        ));

        $this->assertSame('New', $updated->first_name);
        $this->assertSame('new@example.test', $updated->email);
        $this->assertNotSame($originalHash, $updated->password);
        $this->assertTrue(Hash::check('fresh-pass', $updated->password));
        $this->assertSame(Role::ADMIN, $updated->appRole);
    }

    #[Test]
    public function leaves_password_unchanged_when_input_password_is_null(): void
    {
        $user = User::factory()->member()->create();
        $originalHash = $user->password;

        $updated = ($this->action)($user, new UpdateUserInput(
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            password: null,
            role: Role::MEMBER,
        ));

        $this->assertSame($originalHash, $updated->password);
    }

    #[Test]
    public function does_not_resync_roles_when_role_unchanged(): void
    {
        $user = User::factory()->member()->create();

        $updated = ($this->action)($user, new UpdateUserInput(
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            password: null,
            role: Role::MEMBER,
        ));

        $this->assertSame(Role::MEMBER, $updated->appRole);
    }
}

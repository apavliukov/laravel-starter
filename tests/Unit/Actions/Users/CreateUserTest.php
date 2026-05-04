<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Users;

use App\Actions\Users\CreateUser;
use App\Dto\Users\CreateUserInput;
use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('actions')]
#[Group('users')]
#[CoversClass(CreateUser::class)]
final class CreateUserTest extends TestCase
{
    #[Test]
    public function creates_user_with_hashed_password_and_role(): void
    {
        $action = $this->app->make(CreateUser::class);

        $user = $action(new CreateUserInput(
            firstName: 'Alice',
            lastName: 'Cooper',
            email: 'alice@example.test',
            password: 'plain-pass',
            role: Role::ADMIN,
        ));

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Alice', $user->first_name);
        $this->assertSame('Cooper', $user->last_name);
        $this->assertSame('alice@example.test', $user->email);
        $this->assertNotSame('plain-pass', $user->password);
        $this->assertTrue(Hash::check('plain-pass', $user->password));
        $this->assertSame(Role::ADMIN, $user->appRole);
    }
}

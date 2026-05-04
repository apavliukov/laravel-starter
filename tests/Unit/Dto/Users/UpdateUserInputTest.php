<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\Users;

use App\Dto\Users\UpdateUserInput;
use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('dto')]
#[Group('users')]
#[CoversClass(UpdateUserInput::class)]
final class UpdateUserInputTest extends TestCase
{
    #[Test]
    public function from_array_returns_provided_password_as_is(): void
    {
        $dto = UpdateUserInput::fromArray([
            'first_name' => 'Alice',
            'last_name' => 'Cooper',
            'email' => 'a@b.test',
            'password' => 'new-pass',
            'role' => 'member',
        ]);

        $this->assertSame('new-pass', $dto->password);
        $this->assertSame(Role::MEMBER, $dto->role);
    }

    #[Test]
    public function from_array_normalizes_empty_string_password_to_null(): void
    {
        $dto = UpdateUserInput::fromArray([
            'first_name' => 'Alice',
            'last_name' => 'Cooper',
            'email' => 'a@b.test',
            'password' => '',
            'role' => 'member',
        ]);

        $this->assertNull($dto->password);
    }

    #[Test]
    public function from_array_treats_missing_password_as_null(): void
    {
        $dto = UpdateUserInput::fromArray([
            'first_name' => 'Alice',
            'last_name' => 'Cooper',
            'email' => 'a@b.test',
            'role' => 'member',
        ]);

        $this->assertNull($dto->password);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\Users;

use App\Dto\Users\CreateUserInput;
use App\Enums\Policies\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CreateUserInputTest extends TestCase
{
    #[Test]
    public function from_array_maps_validated_data_to_typed_dto(): void
    {
        $dto = CreateUserInput::fromArray([
            'first_name' => 'Alice',
            'last_name' => 'Cooper',
            'email' => 'alice@example.test',
            'password' => 'secret-pass',
            'role' => 'admin',
        ]);

        $this->assertSame('Alice', $dto->firstName);
        $this->assertSame('Cooper', $dto->lastName);
        $this->assertSame('alice@example.test', $dto->email);
        $this->assertSame('secret-pass', $dto->password);
        $this->assertSame(Role::ADMIN, $dto->role);
    }

    #[Test]
    public function from_array_throws_on_unknown_role(): void
    {
        $this->expectException(\ValueError::class);

        CreateUserInput::fromArray([
            'first_name' => 'A',
            'last_name' => 'B',
            'email' => 'a@b.test',
            'password' => 'x',
            'role' => 'super-admin',
        ]);
    }
}

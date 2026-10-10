<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\Users;

use App\Dto\Users\CreateUserInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('dto')]
#[Group('users')]
#[CoversClass(CreateUserInput::class)]
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
            'is_platform_admin' => true,
        ]);

        $this->assertSame('Alice', $dto->firstName);
        $this->assertSame('Cooper', $dto->lastName);
        $this->assertSame('alice@example.test', $dto->email);
        $this->assertSame('secret-pass', $dto->password);
        $this->assertTrue($dto->platformAdmin);
    }

    #[Test]
    public function from_array_defaults_to_no_platform_role(): void
    {
        $dto = CreateUserInput::fromArray([
            'first_name' => 'Alice',
            'last_name' => 'Cooper',
            'email' => 'alice@example.test',
            'password' => 'secret-pass',
        ]);

        $this->assertFalse($dto->platformAdmin);
    }
}

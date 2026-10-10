<?php

declare(strict_types=1);

namespace App\Dto\Users;

final readonly class CreateUserInput
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $password,
        public bool $platformAdmin = false,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            password: $data['password'],
            platformAdmin: (bool) ($data['is_platform_admin'] ?? false),
        );
    }
}

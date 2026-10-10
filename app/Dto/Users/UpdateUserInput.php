<?php

declare(strict_types=1);

namespace App\Dto\Users;

final readonly class UpdateUserInput
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $password,
        public bool $platformAdmin = false,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $password = $data['password'] ?? null;

        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            password: $password === '' ? null : $password,
            platformAdmin: (bool) ($data['is_platform_admin'] ?? false),
        );
    }
}

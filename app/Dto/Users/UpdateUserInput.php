<?php

declare(strict_types=1);

namespace App\Dto\Users;

use App\Enums\Policies\Role;

final readonly class UpdateUserInput
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $password,
        public Role $role,
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
            role: Role::from($data['role']),
        );
    }
}

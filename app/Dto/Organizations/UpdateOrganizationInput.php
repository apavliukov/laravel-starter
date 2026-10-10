<?php

declare(strict_types=1);

namespace App\Dto\Organizations;

final readonly class UpdateOrganizationInput
{
    public function __construct(
        public string $name,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
        );
    }
}

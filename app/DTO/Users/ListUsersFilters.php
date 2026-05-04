<?php

declare(strict_types=1);

namespace App\DTO\Users;

final readonly class ListUsersFilters
{
    public function __construct(
        public string $search = '',
        public string $sort = 'created_at',
        public string $direction = 'desc',
        public int $perPage = 15,
    ) {}
}

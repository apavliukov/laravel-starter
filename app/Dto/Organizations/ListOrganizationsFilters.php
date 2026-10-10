<?php

declare(strict_types=1);

namespace App\Dto\Organizations;

final readonly class ListOrganizationsFilters
{
    public function __construct(
        public string $search = '',
        public string $sort = 'created_at',
        public string $direction = 'desc',
        public int $perPage = 15,
    ) {}
}

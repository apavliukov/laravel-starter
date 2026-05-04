<?php

declare(strict_types=1);

namespace Tests\Unit\Queries\Users;

use App\DTO\Users\ListUsersFilters;
use App\Models\User;
use App\Queries\Users\ListUsersQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ListUsersQueryTest extends TestCase
{
    #[Test]
    public function returns_paginator_of_all_users_when_filters_are_default(): void
    {
        User::factory()->count(3)->create();

        $result = (new ListUsersQuery())->handle(new ListUsersFilters());

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(3, $result->total());
    }

    #[Test]
    public function search_filter_is_applied(): void
    {
        User::factory()->create(['first_name' => 'Alice']);
        User::factory()->create(['first_name' => 'Bob']);

        $result = (new ListUsersQuery())->handle(new ListUsersFilters(search: 'alice'));

        $this->assertSame(1, $result->total());
    }

    #[Test]
    public function sorts_by_name_ascending(): void
    {
        User::factory()->create(['first_name' => 'Charlie', 'last_name' => 'Z']);
        User::factory()->create(['first_name' => 'Alice', 'last_name' => 'A']);
        User::factory()->create(['first_name' => 'Bob', 'last_name' => 'B']);

        $result = (new ListUsersQuery())->handle(new ListUsersFilters(sort: 'name', direction: 'asc'));

        $names = $result->getCollection()->map(fn (User $u): string => $u->first_name)->all();
        $this->assertSame(['Alice', 'Bob', 'Charlie'], $names);
    }

    #[Test]
    public function respects_per_page(): void
    {
        User::factory()->count(20)->create();

        $result = (new ListUsersQuery())->handle(new ListUsersFilters(perPage: 5));

        $this->assertSame(5, $result->perPage());
        $this->assertSame(20, $result->total());
    }
}

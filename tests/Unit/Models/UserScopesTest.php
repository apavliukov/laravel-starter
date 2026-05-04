<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\Policies\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('scopes')]
#[Group('users')]
#[CoversClass(User::class)]
final class UserScopesTest extends TestCase
{
    #[Test]
    public function search_matches_first_name_last_name_or_email_case_insensitively(): void
    {
        User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Cooper', 'email' => 'a@example.test']);
        User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Dylan', 'email' => 'b@example.test']);
        User::factory()->create(['first_name' => 'Cody', 'last_name' => 'Smith', 'email' => 'cody@example.test']);

        $this->assertSame(2, User::query()->search('co')->count(), 'Cooper (last name) + Cody (first name)');
        $this->assertSame(1, User::query()->search('dylan')->count());
        $this->assertSame(1, User::query()->search('CODY@')->count());
    }

    #[Test]
    public function search_with_empty_string_returns_all_users(): void
    {
        User::factory()->count(3)->create();

        $this->assertSame(3, User::query()->search('')->count());
    }

    #[Test]
    public function with_role_filters_users_by_role(): void
    {
        User::factory()->admin()->count(2)->create();
        User::factory()->member()->count(3)->create();

        $this->assertSame(2, User::query()->withRole(Role::ADMIN)->count());
        $this->assertSame(3, User::query()->withRole(Role::MEMBER)->count());
    }
}

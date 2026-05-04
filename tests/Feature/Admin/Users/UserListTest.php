<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Livewire\Admin\Users\UserList;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserListTest extends TestCase
{
    #[Test]
    public function renders_users_for_admin(): void
    {
        $admin = User::factory()->admin()->create([
            'first_name' => 'Admin',
            'last_name' => 'Root',
            'email' => 'admin-root@example.test',
        ]);
        User::factory()->member()->create(['first_name' => 'Alice', 'last_name' => 'Cooper']);
        User::factory()->member()->create(['first_name' => 'Bob', 'last_name' => 'Dylan']);

        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->assertOk()
            ->assertSee('Alice')
            ->assertSee('Bob');
    }

    #[Test]
    public function search_filters_users(): void
    {
        $admin = User::factory()->admin()->create([
            'first_name' => 'Admin',
            'last_name' => 'Root',
            'email' => 'admin-root@example.test',
        ]);
        User::factory()->member()->create(['first_name' => 'Alice', 'last_name' => 'Cooper']);
        User::factory()->member()->create(['first_name' => 'Bob', 'last_name' => 'Dylan']);

        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->set('search', 'Alice')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    #[Test]
    public function sort_by_toggles_direction(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->call('sortBy', 'name')
            ->assertSet('sort', 'name')
            ->assertSet('direction', 'desc')
            ->call('sortBy', 'name')
            ->assertSet('direction', 'asc');
    }

    #[Test]
    public function clear_filters_resets_state(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->set('search', 'x')
            ->set('sort', 'name')
            ->set('direction', 'asc')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('sort', 'created_at')
            ->assertSet('direction', 'desc');
    }
}

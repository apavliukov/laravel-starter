<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use AlexPavliukov\Authorization\Tenancy\TenantMembership;
use App\Enums\Policies\Role;
use App\Livewire\Admin\Users\CreateUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('admin')]
#[Group('users')]
#[Group('livewire')]
#[CoversClass(CreateUser::class)]
final class CreateUserTest extends TestCase
{
    #[Test]
    public function admin_can_view_the_create_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.platform.users.create'))
            ->assertOk()
            ->assertSeeLivewire(CreateUser::class);
    }

    #[Test]
    public function member_cannot_view_the_create_page(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get(route('admin.platform.users.create'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_create_a_user_without_any_role(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->set('form.first_name', 'Alice')
            ->set('form.last_name', 'Cooper')
            ->set('form.email', 'alice@example.test')
            ->set('form.password', 'secret-pass')
            ->call('store')
            ->assertHasNoErrors()
            ->assertRedirect();

        $created = User::query()->where('email', 'alice@example.test')->firstOrFail();
        $this->assertSame('Alice', $created->first_name);
        $this->assertTrue(Hash::check('secret-pass', $created->password));
        $this->assertNull($created->app_role);
        $this->assertSame([], resolve(TenantMembership::class)->tenantIdsOf($created));
    }

    #[Test]
    public function admin_can_create_a_platform_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->set('form.first_name', 'Alice')
            ->set('form.last_name', 'Cooper')
            ->set('form.email', 'alice@example.test')
            ->set('form.password', 'secret-pass')
            ->set('form.is_platform_admin', true)
            ->call('store')
            ->assertHasNoErrors();

        $created = User::query()->where('email', 'alice@example.test')->firstOrFail();
        $this->assertSame(Role::ADMIN, $created->app_role);
        $this->assertTrue($created->is_platform_admin);
    }

    #[Test]
    public function validation_requires_required_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->call('store')
            ->assertHasErrors([
                'form.first_name',
                'form.last_name',
                'form.email',
                'form.password',
            ]);
    }

    #[Test]
    public function validation_rejects_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.test']);
        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->set('form.first_name', 'A')
            ->set('form.last_name', 'B')
            ->set('form.email', 'taken@example.test')
            ->set('form.password', 'secret-pass')
            ->call('store')
            ->assertHasErrors(['form.email']);
    }
}

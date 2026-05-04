<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Enums\Policies\Role;
use App\Livewire\Admin\Users\EditUser;
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
#[CoversClass(EditUser::class)]
final class EditUserTest extends TestCase
{
    #[Test]
    public function admin_can_view_the_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create();

        $this->actingAs($admin)
            ->get(route('admin.platform.users.edit', $target))
            ->assertOk()
            ->assertSeeLivewire(EditUser::class);
    }

    #[Test]
    public function member_cannot_view_the_edit_page(): void
    {
        $member = User::factory()->member()->create();
        $target = User::factory()->member()->create();

        $this->actingAs($member)
            ->get(route('admin.platform.users.edit', $target))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_update_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create([
            'first_name' => 'Old',
            'email' => 'old@example.test',
        ]);
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['user' => $target])
            ->set('form.firstName', 'New')
            ->set('form.email', 'new@example.test')
            ->set('form.password', 'fresh-pass')
            ->set('form.role', Role::ADMIN->value)
            ->call('update')
            ->assertHasNoErrors();

        $target->refresh();
        $this->assertSame('New', $target->first_name);
        $this->assertSame('new@example.test', $target->email);
        $this->assertTrue(Hash::check('fresh-pass', $target->password));
        $this->assertSame(Role::ADMIN, $target->appRole);
    }

    #[Test]
    public function blank_password_leaves_existing_hash_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create();
        $originalHash = $target->password;
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['user' => $target])
            ->set('form.password', '')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame($originalHash, $target->fresh()->password);
    }

    #[Test]
    public function unique_email_rule_ignores_current_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create(['email' => 'self@example.test']);
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['user' => $target])
            ->set('form.email', 'self@example.test')
            ->call('update')
            ->assertHasNoErrors('form.email');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Users;

use App\Livewire\Admin\Users\DeleteUser;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('admin')]
#[Group('users')]
#[Group('livewire')]
#[CoversClass(DeleteUser::class)]
final class DeleteUserTest extends TestCase
{
    #[Test]
    public function admin_can_soft_delete_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->member()->create();
        $this->actingAs($admin);

        Livewire::test(DeleteUser::class, [
            'user' => $target,
            'modalName' => 'delete-user-'.$target->id,
        ])
            ->call('delete')
            ->assertRedirect(route('admin.platform.users.index'));

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    #[Test]
    public function member_cannot_delete_a_user(): void
    {
        $member = User::factory()->member()->create();
        $target = User::factory()->member()->create();
        $this->actingAs($member);

        Livewire::test(DeleteUser::class, [
            'user' => $target,
            'modalName' => 'delete-user-'.$target->id,
        ])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }
}

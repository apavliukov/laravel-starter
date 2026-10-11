<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Users;

use AlexPavliukov\Authorization\Tenancy\OwnershipException;
use App\Actions\Users\DeleteUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('actions')]
#[Group('users')]
#[CoversClass(DeleteUser::class)]
final class DeleteUserTest extends TestCase
{
    #[Test]
    public function soft_deletes_the_user(): void
    {
        $user = User::factory()->member()->create();
        $action = new DeleteUser();

        $action($user);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    #[Test]
    public function refuses_to_delete_an_organization_owner(): void
    {
        $owner = User::factory()->owner()->create();

        $this->assertThrows(static fn () => new DeleteUser()($owner), OwnershipException::class);

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'deleted_at' => null]);
    }
}

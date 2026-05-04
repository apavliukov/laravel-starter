<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Users;

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
}

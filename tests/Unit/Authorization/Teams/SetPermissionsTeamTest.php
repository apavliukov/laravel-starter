<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization\Teams;

use App\Authorization\Teams\SetPermissionsTeam;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(SetPermissionsTeam::class)]
final class SetPermissionsTeamTest extends TestCase
{
    #[Test]
    public function it_sets_the_spatie_team_id_from_the_resolver(): void
    {
        config(['permission.team_foreign_key' => 'team_id']);

        $user = User::factory()->make();
        $user->setAttribute('team_id', 7);

        $request = Request::create('/');
        $request->setUserResolver(static fn (): User => $user);

        resolve(SetPermissionsTeam::class)->handle(
            $request,
            static fn (Request $request): Response => new Response(),
        );

        $this->assertSame(7, getPermissionsTeamId());
    }
}

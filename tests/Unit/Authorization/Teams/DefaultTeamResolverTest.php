<?php

declare(strict_types=1);

namespace Tests\Unit\Authorization\Teams;

use App\Authorization\Teams\DefaultTeamResolver;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('policies')]
#[CoversClass(DefaultTeamResolver::class)]
final class DefaultTeamResolverTest extends TestCase
{
    #[Test]
    public function it_returns_null_when_there_is_no_user(): void
    {
        $request = Request::create('/');

        $this->assertNull(resolve(DefaultTeamResolver::class)->resolve($request));
    }

    #[Test]
    public function it_reads_the_configured_team_foreign_key_from_the_user(): void
    {
        config(['permission.team_foreign_key' => 'team_id']);

        $user = User::factory()->make();
        $user->setAttribute('team_id', 42);

        $request = Request::create('/');
        $request->setUserResolver(static fn (): User => $user);

        $this->assertSame(42, resolve(DefaultTeamResolver::class)->resolve($request));
    }
}

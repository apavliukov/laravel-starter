<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use AlexPavliukov\Authorization\Tenancy\BelongsToTenant;
use AlexPavliukov\Authorization\Tenancy\MissingTenantContextException;
use AlexPavliukov\Authorization\Testing\InteractsWithTenancy;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The reference for every tenant-owned model this template will grow: a
 * BelongsToTenant model yields nothing (or throws, in strict mode) without a
 * tenant, is autofilled and fenced inside one, and is only fully visible under
 * the named bypass.
 */
#[Group('tenancy')]
final class FailClosedScopeTest extends TestCase
{
    use InteractsWithTenancy;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenant_notes', static function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('organization_id')->constrained();
            $blueprint->string('body');
            $blueprint->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tenant_notes');

        parent::tearDown();
    }

    #[Test]
    public function queries_outside_a_tenant_fail_closed(): void
    {
        $this->assertFailsClosed(TenantNote::class);

        config()->set('authorization.tenancy.strict', true);
        $this->expectException(MissingTenantContextException::class);

        TenantNote::query()->get();
    }

    #[Test]
    public function queries_outside_a_tenant_yield_nothing_when_not_strict(): void
    {
        $organization = Organization::factory()->create();
        $this->forTenant($organization, static fn (): TenantNote => TenantNote::query()->create(['body' => 'hidden']));

        config()->set('authorization.tenancy.strict', false);

        $this->assertTrue(TenantNote::query()->get()->isEmpty());
    }

    #[Test]
    public function rows_are_autofilled_and_fenced_per_tenant(): void
    {
        [$first, $second] = Organization::factory()->count(2)->create();

        $note = $this->forTenant($first, static fn (): TenantNote => TenantNote::query()->create(['body' => 'first']));
        $this->forTenant($second, static fn (): TenantNote => TenantNote::query()->create(['body' => 'second']));

        $this->assertSame($first->id, $note->organization_id);
        $this->assertSame(['first'], $this->forTenant($first, static fn (): array => TenantNote::query()->pluck('body')->all()));
        $this->assertSame(['second'], $this->forTenant($second, static fn (): array => TenantNote::query()->pluck('body')->all()));
        $this->assertNull($this->forTenant($second, static fn (): ?TenantNote => TenantNote::query()->find($note->id)));
        $this->assertSame(2, $this->withoutTenant(static fn (): int => TenantNote::query()->count()));
    }
}

#[Table(name: 'tenant_notes')]
final class TenantNote extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
}

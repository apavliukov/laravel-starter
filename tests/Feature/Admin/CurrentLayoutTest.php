<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\User;
use App\Support\Areas\CurrentLayout;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('admin')]
#[CoversClass(CurrentLayout::class)]
final class CurrentLayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/layout-probe/member', static fn (): string => resolve(CurrentLayout::class)->name())->name('admin.member.probe');
        Route::middleware('web')->get('/layout-probe/none', static fn (): string => resolve(CurrentLayout::class)->name())->name('probe');
    }

    #[Test]
    public function inside_an_area_the_area_decides_even_for_a_platform_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/layout-probe/member')->assertSee('member');
    }

    #[Test]
    public function outside_any_area_the_app_role_decides(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();
        $owner = User::factory()->owner()->create();

        $this->actingAs($admin)->get('/layout-probe/none')->assertSee('platform');
        $this->actingAs($member)->get('/layout-probe/none')->assertSee('member');
        $this->actingAs($owner)->get('/layout-probe/none')->assertSee('member');
    }

    #[Test]
    public function the_dashboard_and_platform_pages_render_the_matching_menu(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member(Organization::factory()->create())->create();

        $this->actingAs($admin)->get(route('admin.platform.organizations.index'))->assertSee(__('Platform Dashboard'));
        $this->actingAs($member)->get(route('admin.dashboard'))->assertSee(__('Member Dashboard'))->assertDontSee(__('Platform Dashboard'));
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Areas;

use App\Enums\Policies\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Which chrome (menu, dashboard body) to render. Inside an area the area decides —
 * a platform admin browsing an organization sees the member chrome. On area-less
 * pages (dashboard, settings) there is no area, so the user's app_role decides.
 * Layout names equal area names (`platform`, `member`) by design.
 */
final readonly class CurrentLayout
{
    /** @var array<string, string> layout name => route-name pattern */
    private const array AREAS = [
        'platform' => 'admin.platform.*',
        'member' => 'admin.member.*',
    ];

    public function __construct(private Request $request) {}

    public function name(): string
    {
        $routeName = $this->request->route()?->getName() ?? '';

        foreach (self::AREAS as $layout => $pattern) {
            if (Str::is($pattern, $routeName)) {
                return $layout;
            }
        }

        $user = $this->request->user();
        $role = $user instanceof User ? $user->app_role : null;

        return ($role instanceof Role ? $role : Role::default())->layout();
    }
}

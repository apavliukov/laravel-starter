<?php

declare(strict_types=1);

namespace App\Support\Areas;

use AlexPavliukov\Authorization\Tenancy\CurrentArea;
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
    public function __construct(private CurrentArea $area, private Request $request) {}

    public function name(): string
    {
        if ($this->inArea()) {
            return (string) $this->area->current();
        }

        $user = $this->request->user();
        $role = $user instanceof User ? $user->app_role : null;

        return ($role instanceof Role ? $role : Role::default())->layout();
    }

    private function inArea(): bool
    {
        /** @var array<string, string> $patterns */
        $patterns = config('authorization.areas', []);

        return Str::is($patterns, $this->request->route()?->getName() ?? '');
    }
}

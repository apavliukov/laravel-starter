<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use AlexPavliukov\Authorization\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

final class OrganizationController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize(Ability::VIEW_ANY, Organization::class);

        return view('pages.admin.organizations.index');
    }

    public function create(): View
    {
        $this->authorize(Ability::CREATE, Organization::class);

        return view('pages.admin.organizations.create');
    }

    public function edit(Organization $organization): View
    {
        $this->authorize(Ability::UPDATE, $organization);

        return view('pages.admin.organizations.edit', ['organization' => $organization]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Authorization\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

final class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize(Ability::VIEW_ANY, User::class);

        return view('pages.admin.users.index');
    }

    public function create(): View
    {
        $this->authorize(Ability::CREATE, User::class);

        return view('pages.admin.users.create');
    }

    public function edit(User $user): View
    {
        $this->authorize(Ability::UPDATE, $user);

        return view('pages.admin.users.edit', ['user' => $user]);
    }
}

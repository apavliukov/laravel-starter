<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Policies\Ability;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\Controllers\HasAuthorizationPolicy;
use Illuminate\Contracts\View\View;

final class UserController extends Controller
{
    use HasAuthorizationPolicy;

    protected string $modelClass = User::class;

    public function index(): View
    {
        $this->authorize(Ability::VIEW_ANY->value, $this->modelClass);

        return view('pages.admin.users.index');
    }

    public function create(): View
    {
        $this->authorize(Ability::CREATE->value, $this->modelClass);

        return view('pages.admin.users.create');
    }

    public function edit(User $user): View
    {
        $this->authorize(Ability::UPDATE->value, $user);

        return view('pages.admin.users.edit', ['user' => $user]);
    }
}

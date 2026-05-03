<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Policies\Ability;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize(Ability::VIEW_ANY, User::class);

        return view('pages.admin.users.index');
    }
}

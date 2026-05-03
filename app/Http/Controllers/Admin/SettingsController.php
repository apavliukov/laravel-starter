<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function profile(): View
    {
        return view('pages.admin.settings.profile');
    }

    public function password(): View
    {
        return view('pages.admin.settings.password');
    }

    public function appearance(): View
    {
        return view('pages.admin.settings.appearance');
    }
}

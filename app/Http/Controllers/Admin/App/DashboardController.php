<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\App;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.admin.app.dashboard.index');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.web.home.index');
    }
}

<?php

declare(strict_types=1);

namespace App\View\Components\Common\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Toast extends Component
{
    public function __construct(public string $position = 'bottom-right') {}

    public function render(): View
    {
        return view('components.common.ui.toast');
    }
}

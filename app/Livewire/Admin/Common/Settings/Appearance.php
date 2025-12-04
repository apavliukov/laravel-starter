<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Common\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Appearance extends Component
{
    public function render(): View
    {
        return view('livewire.admin.common.settings.appearance');
    }
}

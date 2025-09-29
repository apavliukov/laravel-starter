<?php

declare(strict_types=1);

namespace App\Livewire\Common\Utility;

use Illuminate\View\View;
use Livewire\Component;

final class ToastVariants extends Component
{
    public function showSuccess(): void
    {
        $this->dispatch('toast', message: 'This is a success message!', variant: 'success');
    }

    public function showError(): void
    {
        $this->dispatch('toast', message: 'This is an error message!', variant: 'error');
    }

    public function showWarning(): void
    {
        $this->dispatch('toast', message: 'This is a warning message!', variant: 'warning');
    }

    public function showInfo(): void
    {
        $this->dispatch('toast', message: 'This is an info message!', variant: 'info');
    }

    public function showPersistent(): void
    {
        $this->dispatch('toast', message: 'This message stays until dismissed!', variant: 'info', duration: 0);
    }

    public function render(): View
    {
        return view('livewire.common.utility.toast-variants');
    }
}

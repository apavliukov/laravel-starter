<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Common\Settings;

use App\Livewire\Auth\Actions\Logout;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class DeleteUserForm extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.common.settings.delete-user-form');
    }
}

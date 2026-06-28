<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use AlexPavliukov\Authorization\Enums\Ability;
use App\Actions\Users\DeleteUser as DeleteUserAction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class DeleteUser extends Component
{
    public User $user;

    public string $modalName;

    public function mount(User $user, string $modalName): void
    {
        $this->user = $user;
        $this->modalName = $modalName;
    }

    public function delete(DeleteUserAction $action): void
    {
        Gate::authorize(Ability::DELETE, $this->user);

        $action($this->user);

        self::modal($this->modalName)->close();

        session()?->flash('toast', [
            'message' => __('User deleted successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.platform.users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.delete-user');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Actions\Users\UpdateUser as UpdateUserAction;
use App\Enums\Policies\Ability;
use App\Enums\Policies\Role;
use App\Livewire\Admin\Users\Forms\UserForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class EditUser extends Component
{
    public User $user;

    public UserForm $form;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->form->ignoreUserId = $user->id;
        $this->form->passwordRequired = false;
        $this->form->setUser($user);
    }

    /**
     * @return array<int, Role>
     */
    #[Computed]
    public function roleOptions(): array
    {
        return Role::cases();
    }

    public function update(UpdateUserAction $action): void
    {
        Gate::authorize(Ability::UPDATE, $this->user);

        $this->form->validate();

        $action($this->user, $this->form->toUpdateInput());

        session()?->flash('toast', [
            'message' => __('User updated successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.platform.users.edit', $this->user), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.edit-user');
    }
}

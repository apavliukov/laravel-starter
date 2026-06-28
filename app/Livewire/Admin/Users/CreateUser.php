<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Actions\Users\CreateUser as CreateUserAction;
use App\Authorization\Enums\Ability;
use App\Enums\Policies\Role;
use App\Livewire\Admin\Users\Forms\UserForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CreateUser extends Component
{
    public UserForm $form;

    public function mount(): void
    {
        $this->form->role = Role::default()->value;
    }

    /**
     * @return array<int, Role>
     */
    #[Computed]
    public function roleOptions(): array
    {
        return Role::cases();
    }

    public function store(CreateUserAction $action): void
    {
        Gate::authorize(Ability::CREATE, User::class);

        $this->form->validate();

        $user = $action($this->form->toCreateInput());

        session()?->flash('toast', [
            'message' => __('User created successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.platform.users.edit', $user), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.create-user');
    }
}

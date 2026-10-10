<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Organizations;

use AlexPavliukov\Authorization\Enums\Ability;
use App\Actions\Organizations\CreateOrganization as CreateOrganizationAction;
use App\Livewire\Admin\Organizations\Forms\OrganizationForm;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class CreateOrganization extends Component
{
    public OrganizationForm $form;

    public function store(CreateOrganizationAction $action): void
    {
        Gate::authorize(Ability::CREATE, Organization::class);

        $this->form->validate();

        $organization = $action($this->form->toCreateInput());

        session()?->flash('toast', [
            'message' => __('Organization created successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.platform.organizations.edit', $organization), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.organizations.create-organization');
    }
}

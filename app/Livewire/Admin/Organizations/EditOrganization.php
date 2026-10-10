<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Organizations;

use AlexPavliukov\Authorization\Enums\Ability;
use App\Actions\Organizations\UpdateOrganization as UpdateOrganizationAction;
use App\Livewire\Admin\Organizations\Forms\OrganizationForm;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class EditOrganization extends Component
{
    public Organization $organization;

    public OrganizationForm $form;

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
        $this->form->setOrganization($organization);
    }

    public function update(UpdateOrganizationAction $action): void
    {
        Gate::authorize(Ability::UPDATE, $this->organization);

        $this->form->validate();

        $action($this->organization, $this->form->toUpdateInput());

        session()?->flash('toast', [
            'message' => __('Organization updated successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.platform.organizations.edit', $this->organization), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.organizations.edit-organization');
    }
}

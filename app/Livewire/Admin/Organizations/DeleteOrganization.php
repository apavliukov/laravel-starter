<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Organizations;

use AlexPavliukov\Authorization\Enums\Ability;
use App\Actions\Organizations\DeleteOrganization as DeleteOrganizationAction;
use App\Models\Organization;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class DeleteOrganization extends Component
{
    public Organization $organization;

    public string $modalName;

    public function mount(Organization $organization, string $modalName): void
    {
        $this->organization = $organization;
        $this->modalName = $modalName;
    }

    public function delete(DeleteOrganizationAction $action): void
    {
        Gate::authorize(Ability::DELETE, $this->organization);

        try {
            $action($this->organization);
        } catch (DomainException $exception) {
            self::modal($this->modalName)->close();
            $this->dispatch('toast', message: $exception->getMessage(), variant: 'error');

            return;
        }

        self::modal($this->modalName)->close();

        session()?->flash('toast', [
            'message' => __('Organization deleted successfully'),
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.platform.organizations.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.organizations.delete-organization');
    }
}

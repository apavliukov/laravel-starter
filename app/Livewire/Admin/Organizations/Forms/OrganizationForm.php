<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Organizations\Forms;

use App\Dto\Organizations\CreateOrganizationInput;
use App\Dto\Organizations\UpdateOrganizationInput;
use App\Models\Organization;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class OrganizationForm extends Form
{
    public string $name = '';

    /** Create only: ownership is transferred, never edited, once the organization exists. */
    public string $ownerEmail = '';

    public bool $exists = false;

    public function setOrganization(Organization $organization): void
    {
        $this->name = $organization->name;
        $this->exists = true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ownerEmail' => $this->exists
                ? ['nullable']
                : ['required', 'email', Rule::exists('users', 'email')->whereNull('deleted_at')],
        ];
    }

    public function toCreateInput(): CreateOrganizationInput
    {
        return new CreateOrganizationInput(
            name: $this->name,
            ownerEmail: $this->ownerEmail,
        );
    }

    public function toUpdateInput(): UpdateOrganizationInput
    {
        return new UpdateOrganizationInput(
            name: $this->name,
        );
    }
}

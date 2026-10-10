<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Organizations\Forms;

use App\Dto\Organizations\CreateOrganizationInput;
use App\Dto\Organizations\UpdateOrganizationInput;
use App\Models\Organization;
use Livewire\Form;

final class OrganizationForm extends Form
{
    public string $name = '';

    public function setOrganization(Organization $organization): void
    {
        $this->name = $organization->name;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function toCreateInput(): CreateOrganizationInput
    {
        return new CreateOrganizationInput(
            name: $this->name,
        );
    }

    public function toUpdateInput(): UpdateOrganizationInput
    {
        return new UpdateOrganizationInput(
            name: $this->name,
        );
    }
}

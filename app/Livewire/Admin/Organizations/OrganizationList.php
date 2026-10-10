<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Organizations;

use App\Dto\Organizations\ListOrganizationsFilters;
use App\Models\Organization;
use App\Queries\Organizations\ListOrganizationsQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class OrganizationList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'desc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->sort = 'created_at';
        $this->direction = 'desc';

        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Organization>
     */
    #[Computed]
    public function organizations(): LengthAwarePaginator
    {
        return new ListOrganizationsQuery()->handle(new ListOrganizationsFilters(
            search: $this->search,
            sort: $this->sort,
            direction: $this->direction,
        ));
    }

    public function render(): View
    {
        return view('livewire.admin.organizations.organization-list');
    }
}

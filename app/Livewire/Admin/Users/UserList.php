<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class UserList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $sort = 'created_at';

    public string $direction = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
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
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $query = User::query()
            ->when(
                $this->search !== '',
                function (Builder $q): void {
                    $term = '%'.$this->search.'%';

                    $q->where(function (Builder $q) use ($term): void {
                        $q->where('first_name', 'ilike', $term)
                            ->orWhere('last_name', 'ilike', $term)
                            ->orWhere('email', 'ilike', $term);
                    });
                }
            );

        if ($this->sort === 'name') {
            $query->orderBy('first_name', $this->direction)
                ->orderBy('last_name', $this->direction);
        } else {
            $query->orderBy($this->sort, $this->direction);
        }

        return $query->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.admin.users.user-list');
    }
}

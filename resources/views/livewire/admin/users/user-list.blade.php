@php use App\Enums\Policies\Abilities\Ability; @endphp
<div class="space-y-6">
    {{-- Filters --}}
    <div class="flex flex-wrap items-end gap-4">
        <flux:field class="w-full sm:w-64">
            <flux:label>{{ __('Search') }}</flux:label>
            <flux:input
                wire:model="search"
                wire:keydown.enter="$refresh"
                x-on:search="$wire.$refresh()"
                size="sm"
                type="search"
                :placeholder="__('Search users...')"
                icon="magnifying-glass"
            />
        </flux:field>

        @if ($search)
            <flux:button wire:click="clearFilters" variant="filled" size="sm" icon="x-mark">
                {{ __('Clear Filters') }}
            </flux:button>
        @endif
    </div>

    {{-- Empty state / Table --}}
    @if ($this->users->isEmpty())
        <div
            class="rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-600 dark:bg-zinc-800">
            <flux:icon.users class="mx-auto h-12 w-12 text-zinc-400"/>
            <flux:heading size="sm" class="mt-2">{{ __('No users found') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">{{ __('No users match your current filters.') }}</flux:text>
        </div>
    @else
        <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-500 dark:text-neutral-400">
                                <button wire:click="sortBy('name')"
                                        class="flex items-center gap-1 uppercase hover:text-neutral-700 dark:hover:text-neutral-200">
                                    {{ __('Name') }}
                                    @if ($sort === 'name')
                                        <flux:icon :name="$direction === 'asc' ? 'chevron-up' : 'chevron-down'"
                                                   class="size-3"/>
                                    @endif
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('Role') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-500 dark:text-neutral-400">
                                <button wire:click="sortBy('created_at')"
                                        class="flex items-center gap-1 uppercase hover:text-neutral-700 dark:hover:text-neutral-200">
                                    {{ __('Registered') }}
                                    @if ($sort === 'created_at')
                                        <flux:icon :name="$direction === 'asc' ? 'chevron-up' : 'chevron-down'"
                                                   class="size-3"/>
                                    @endif
                                </button>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                        @foreach ($this->users as $user)
                            <tr wire:key="user-{{ $user->id }}" class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                <td class="px-6 py-4">
                                    <div>
                                        <a href="{{ route('admin.platform.users.edit', $user) }}" wire:navigate
                                           class="text-sm font-medium text-neutral-900 hover:underline dark:text-neutral-100">
                                            {{ $user->name }}
                                        </a>
                                        <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $user->email }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <flux:badge :color="$user->appRole->badgeColor()" size="sm">
                                        {{ $user->appRole->label() }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-4">
                                    <flux:text class="text-sm" title="{{ $user->created_at->smartDateTime() }}">
                                        {{ $user->created_at->smartDate() }}
                                    </flux:text>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @canany([Ability::UPDATE, Ability::DELETE], $user)
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" square />

                                            <flux:menu>
                                                @can(Ability::UPDATE, $user)
                                                    <flux:menu.item
                                                        icon="pencil"
                                                        :href="route('admin.platform.users.edit', $user)"
                                                        wire:navigate
                                                    >
                                                        {{ __('Edit') }}
                                                    </flux:menu.item>
                                                @endcan
                                                @can(Ability::DELETE, $user)
                                                    <flux:menu.separator />
                                                    <flux:modal.trigger :name="'delete-user-'.$user->id">
                                                        <flux:menu.item variant="danger" icon="trash">
                                                            {{ __('Delete') }}
                                                        </flux:menu.item>
                                                    </flux:modal.trigger>
                                                @endcan
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endcanany

                                    @can(Ability::DELETE, $user)
                                        <livewire:admin.users.delete-user
                                            :user="$user"
                                            :modal-name="'delete-user-'.$user->id"
                                            wire:key="delete-user-{{ $user->id }}" />
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($this->users->hasPages())
            <div class="mt-4">{{ $this->users->links() }}</div>
        @endif
    @endif
</div>

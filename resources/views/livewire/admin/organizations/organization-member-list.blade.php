@php
    use AlexPavliukov\Authorization\Enums\Ability;
    use App\Enums\Policies\OrganizationAbility;
    use Illuminate\Support\Facades\Gate;

    $canUpdate = Gate::allows(Ability::UPDATE, $organization);
    $canTransfer = Gate::allows(OrganizationAbility::TRANSFER_OWNERSHIP, $organization);
@endphp
<div class="space-y-6">
    <flux:heading size="lg">{{ __('Members') }}</flux:heading>

    @if ($canUpdate)
        <form wire:submit="add" class="flex flex-wrap items-end gap-4">
            <flux:field class="w-full sm:w-72">
                <flux:label>{{ __('Email') }}</flux:label>
                <flux:input type="email" wire:model="email" :placeholder="__('Existing user email')" />
                <flux:error class="mt-0!" name="email" />
            </flux:field>

            <flux:field class="w-full sm:w-44">
                <flux:label>{{ __('Role') }}</flux:label>
                <flux:select wire:model="role">
                    @foreach ($this->roleOptions as $case)
                        <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error class="mt-0!" name="role" />
            </flux:field>

            <flux:button type="submit" variant="primary" size="sm" icon="plus">{{ __('Add Member') }}</flux:button>
        </form>
    @endif

    @if ($this->members->isEmpty())
        <div class="rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-600 dark:bg-zinc-800">
            <flux:icon.users class="mx-auto h-10 w-10 text-zinc-400" />
            <flux:text class="mt-2 text-zinc-500">{{ __('No members yet.') }}</flux:text>
        </div>
    @else
        <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                            {{ __('Name') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                            {{ __('Role') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium tracking-wider text-neutral-500 uppercase dark:text-neutral-400"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                    @foreach ($this->members as $member)
                        @php
                            $memberRole = $this->roleOf($member);
                            $isOwner = $this->isOwner($member);
                        @endphp
                        <tr wire:key="member-{{ $member->id }}">
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <flux:link
                                        href="{{ route('admin.platform.users.edit', $member) }}"
                                        wire:navigate
                                        class="text-sm font-medium"
                                    >
                                        {{ $member->name }}
                                    </flux:link>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $member->email }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if (! $isOwner && $canUpdate)
                                    <flux:select
                                        size="sm"
                                        class="w-40"
                                        wire:change="changeRole({{ $member->id }}, $event.target.value)"
                                    >
                                        @foreach ($this->roleOptions as $case)
                                            <flux:select.option :value="$case->value" :selected="$memberRole === $case">
                                                {{ $case->label() }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @elseif ($memberRole)
                                    <flux:badge
                                        :color="$memberRole->badgeColor()"
                                        size="sm"
                                    >{{ $memberRole->label() }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if (! $isOwner)
                                    @if ($canTransfer)
                                        <flux:button
                                            wire:click="transferOwnership({{ $member->id }})"
                                            wire:confirm="{{ __('Make :name the owner of :organization? The current owner becomes a member.', ['name' => $member->name, 'organization' => $organization->name]) }}"
                                            variant="ghost"
                                            size="sm"
                                            icon="key"
                                            square
                                            :title="__('Make owner')"
                                        />
                                    @endif
                                    @if ($canUpdate)
                                        <flux:button
                                            wire:click="remove({{ $member->id }})"
                                            wire:confirm="{{ __('Remove :name from :organization?', ['name' => $member->name, 'organization' => $organization->name]) }}"
                                            variant="ghost"
                                            size="sm"
                                            icon="user-minus"
                                            square
                                        />
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@props(['position' => 'bottom-right'])

<div
    x-data="toast('{{ $position }}')"
    x-init="
        @if(session()->has('toast'))
            @php $toast = session('toast'); @endphp
            addMessage(
                '{{ addslashes($toast['message'] ?? '') }}',
                '{{ $toast['variant'] ?? 'info' }}',
                {{ $toast['duration'] ?? 3000 }}
            );
        @endif
    "
    class="fixed z-50 flex flex-col gap-2"
    :class="positionClasses"
>
    <template x-for="message in messages" :key="message.id">
        <div
            x-show="message.show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-2"
            @mouseenter="pauseTimer(message.id)"
            @mouseleave="resumeTimer(message.id)"
            class="bg-white dark:bg-gray-700 border-gray-300 text-neutral-900 dark:text-neutral-100 p-3 rounded-lg shadow-md border flex items-center gap-3 min-w-40 sm:min-w-75 max-w-40 sm:max-w-md"
        >
            <div class="shrink-0" :class="getIconColorClasses(message.variant)">
                <template x-if="message.variant === 'success'">
                    <flux:icon.check-circle class="size-5" />
                </template>
                <template x-if="message.variant === 'error'">
                    <flux:icon.x-circle class="size-5" />
                </template>
                <template x-if="message.variant === 'warning'">
                    <flux:icon.exclamation-triangle class="size-5" />
                </template>
                <template x-if="message.variant === 'info'">
                    <flux:icon.information-circle class="size-5" />
                </template>
            </div>
            <span class="flex-1 text-sm sm:text-base" x-text="message.message"></span>
            <button
                @click="removeMessage(message.id)"
                class="shrink-0 rounded hover:bg-gray-100 dark:hover:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-gray-300"
            >
                <flux:icon.x-mark class="size-4 text-gray-500" />
            </button>
        </div>
    </template>
</div>

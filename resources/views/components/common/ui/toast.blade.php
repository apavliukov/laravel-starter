@props(['position' => 'bottom-right'])

<div x-data="{
    messages: [],
    nextId: 1,
    timers: {},
    addMessage(message, variant = 'info', duration = 3000) {
        const id = this.nextId++;
        const newMessage = {
            id,
            message,
            variant,
            show: false,
            duration,
            remainingTime: duration,
            isPaused: false
        };
        this.messages.push(newMessage);

        this.$nextTick(() => {
            const msg = this.messages.find(m => m.id === id);
            if (msg) {
                msg.show = true;
            }
        });

        if (duration > 0) {
            this.startTimer(id, duration);
        }
    },
    startTimer(id, duration) {
        const msg = this.messages.find(m => m.id === id);
        if (msg) {
            msg.startTime = Date.now();
            this.timers[id] = setTimeout(() => {
                this.removeMessage(id);
            }, duration);
        }
    },
    pauseTimer(id) {
        const msg = this.messages.find(m => m.id === id);
        if (msg && msg.duration > 0 && !msg.isPaused) {
            clearTimeout(this.timers[id]);
            msg.isPaused = true;
            const elapsed = Date.now() - (msg.startTime || Date.now());
            msg.remainingTime = Math.max(0, msg.duration - elapsed);
        }
    },
    resumeTimer(id) {
        const msg = this.messages.find(m => m.id === id);
        if (msg && msg.duration > 0 && msg.isPaused) {
            msg.isPaused = false;
            msg.startTime = Date.now();
            this.timers[id] = setTimeout(() => {
                this.removeMessage(id);
            }, msg.remainingTime);
        }
    },
    removeMessage(id) {
        const index = this.messages.findIndex(m => m.id === id);
        if (index !== -1) {
            clearTimeout(this.timers[id]);
            delete this.timers[id];
            this.messages[index].show = false;
            setTimeout(() => {
                this.messages = this.messages.filter(m => m.id !== id);
            }, 300);
        }
    },
    getIconColorClasses(variant) {
        const classes = {
            'success': 'text-green-500',
            'error': 'text-red-500',
            'warning': 'text-yellow-500',
            'info': 'text-blue-500'
        };
        return classes[variant] || classes['info'];
    },
    getPositionClasses() {
        const positions = {
            'top-left': 'top-4 left-4',
            'top-right': 'top-4 right-4',
            'top-center': 'top-4 left-1/2 -translate-x-1/2',
            'bottom-left': 'bottom-4 left-4',
            'bottom-right': 'bottom-4 right-4',
            'bottom-center': 'bottom-4 left-1/2 -translate-x-1/2'
        };
        return positions['{{ $position }}'] || positions['bottom-right'];
    }
}"
x-init="
    if (typeof Livewire !== 'undefined') {
        Livewire.on('toast', (data) => {
            if (Array.isArray(data)) {
                data = data[0];
            }
            const duration = data.duration !== undefined ? data.duration : 3000;
            addMessage(data.message, data.variant || 'info', duration);
        });
    }

    // Check for flashed session toast
    @if(session()->has('toast'))
        @php
            $toast = session('toast');
        @endphp
        addMessage(
            '{{ addslashes($toast['message'] ?? '') }}',
            '{{ $toast['variant'] ?? 'info' }}',
            {{ $toast['duration'] ?? 3000 }}
        );
    @endif
"
class="fixed z-50"
:class="getPositionClasses()"
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
            class="bg-white dark:bg-gray-700 border-gray-300 text-neutral-900 dark:text-neutral-100 mb-3 px-4 py-3 rounded-lg shadow-md border flex items-center gap-3 min-w-[300px] max-w-md"
        >
            <div class="flex-shrink-0" :class="getIconColorClasses(message.variant)">
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
            <span class="flex-1" x-text="message.message"></span>
            <button
                @click="removeMessage(message.id)"
                class="flex-shrink-0 ml-2 p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-gray-300"
            >
                <flux:icon.x-mark class="size-4 text-gray-500" />
            </button>
        </div>
    </template>
</div>

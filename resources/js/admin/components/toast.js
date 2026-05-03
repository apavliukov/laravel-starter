/**
 * Alpine component for toast notifications.
 *
 * Manages a stack of dismissible messages with auto-hide timers,
 * pause-on-hover, and Livewire event integration.
 *
 * @param {string} position - Position on screen (e.g. 'bottom-right', 'top-center')
 * @returns {object} Alpine component
 */
export default function toast(position = 'bottom-right') {
    const positions = {
        'top-left': 'top-4 left-4',
        'top-right': 'top-4 right-4',
        'top-center': 'top-4 left-1/2 -translate-x-1/2',
        'bottom-left': 'bottom-4 left-4',
        'bottom-right': 'bottom-4 right-4',
        'bottom-center': 'bottom-4 left-1/2 -translate-x-1/2',
    };

    const iconColorClasses = {
        success: 'text-green-500',
        error: 'text-red-500',
        warning: 'text-yellow-500',
        info: 'text-blue-500',
    };

    return {
        messages: [],
        nextId: 1,
        timers: {},
        positionClasses: positions[position] || positions['bottom-right'],

        init() {
            if (typeof window.Livewire !== 'undefined') {
                window.Livewire.on('toast', (data) => {
                    if (Array.isArray(data)) {
                        data = data[0];
                    }

                    const duration = data.duration !== undefined ? data.duration : 3000;
                    this.addMessage(data.message, data.variant || 'info', duration);
                });
            }
        },

        addMessage(message, variant = 'info', duration = 3000) {
            const id = this.nextId++;

            this.messages.push({
                id,
                message,
                variant,
                show: false,
                duration,
                remainingTime: duration,
                isPaused: false,
            });

            this.$nextTick(() => {
                const msg = this.messages.find((m) => m.id === id);
                if (msg) {
                    msg.show = true;
                }
            });

            if (duration > 0) {
                this.startTimer(id, duration);
            }
        },

        startTimer(id, duration) {
            const msg = this.messages.find((m) => m.id === id);
            if (msg) {
                msg.startTime = Date.now();
                this.timers[id] = setTimeout(() => {
                    this.removeMessage(id);
                }, duration);
            }
        },

        pauseTimer(id) {
            const msg = this.messages.find((m) => m.id === id);
            if (msg && msg.duration > 0 && !msg.isPaused) {
                clearTimeout(this.timers[id]);
                msg.isPaused = true;
                const elapsed = Date.now() - (msg.startTime || Date.now());
                msg.remainingTime = Math.max(0, msg.duration - elapsed);
            }
        },

        resumeTimer(id) {
            const msg = this.messages.find((m) => m.id === id);
            if (msg && msg.duration > 0 && msg.isPaused) {
                msg.isPaused = false;
                msg.startTime = Date.now();
                this.timers[id] = setTimeout(() => {
                    this.removeMessage(id);
                }, msg.remainingTime);
            }
        },

        removeMessage(id) {
            const index = this.messages.findIndex((m) => m.id === id);
            if (index !== -1) {
                clearTimeout(this.timers[id]);
                delete this.timers[id];
                this.messages[index].show = false;
                setTimeout(() => {
                    this.messages = this.messages.filter((m) => m.id !== id);
                }, 300);
            }
        },

        getIconColorClasses(variant) {
            return iconColorClasses[variant] || iconColorClasses['info'];
        },
    };
}

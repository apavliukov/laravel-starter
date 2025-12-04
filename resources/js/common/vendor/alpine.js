import { Livewire } from '#/../../vendor/livewire/livewire/dist/livewire.esm';

const componentInitQueue = [];

const runComponentInits = () => {
    if (componentInitQueue.length === 0) {
        return;
    }

    const callback = () => {
        componentInitQueue.forEach(({ name, component }) => {
            window.Alpine.data(name, component);
        });
    };

    document.addEventListener('alpine:init', callback, { once: true });
    document.addEventListener('livewire:navigated', callback);
};

const queueComponentInit = (name, component) => {
    if (typeof component !== 'function') {
        return;
    }

    componentInitQueue.push({ name, component });
};

export const startAlpine = () => {
    if (window.alpineStarted) {
        return;
    }

    if (typeof window.Livewire !== 'object') {
        window.Livewire = Livewire;
    }

    runComponentInits();

    window.Livewire.start();
    window.alpineStarted = true;
};

export const initAlpineComponents = (components) => {
    if (!components || typeof components !== 'object') {
        return;
    }

    Object.entries(components).forEach(([name, component]) => {
        queueComponentInit(name, component);
    });
};

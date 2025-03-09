import Alpine from 'alpinejs';

const componentInitQueue = [];

const runComponentInits = () => {
    if (componentInitQueue.length === 0) {
        return;
    }

    document.addEventListener('livewire:init', () => {
        componentInitQueue.forEach((initFunction) => {
            initFunction();
        });
    });
};

const queueComponentInit = (initFunction) => {
    if (typeof initFunction !== 'function') {
        return;
    }

    componentInitQueue.push(initFunction);
};

export const startAlpine = () => {
    if (window.alpineStarted) {
        return;
    }

    if (typeof window.Alpine !== 'object') {
        window.Alpine = Alpine;
    }

    runComponentInits();

    Alpine.start();
    window.alpineStarted = true;
};

export const initAlpineComponents = (components) => {
    if (!components.length) {
        return;
    }

    components.forEach((component) => {
        queueComponentInit(component);
    });
};

/**
 * Alpine component registration for Livewire 4.
 *
 * This module does NOT import Livewire - it relies on auto-injection via @fluxScripts.
 * Components are registered via the 'alpine:init' event which fires before Alpine starts.
 */

const componentInitQueue = [];
let initialized = false;

const registerQueuedComponents = () => {
    if (!window.Alpine) {
        return;
    }

    componentInitQueue.forEach(({ name, component }) => {
        window.Alpine.data(name, component);
    });
};

const setupEventListeners = () => {
    if (initialized) {
        return;
    }

    // Register components when Alpine initializes (before start)
    document.addEventListener(
        'alpine:init',
        () => {
            registerQueuedComponents();
        },
        { once: true },
    );

    // Re-register on SPA navigation (components persist but may need re-binding)
    document.addEventListener('livewire:navigated', () => {
        registerQueuedComponents();
    });

    initialized = true;
};

export const initAlpineComponents = (components) => {
    if (!components || typeof components !== 'object') {
        return;
    }

    Object.entries(components).forEach(([name, component]) => {
        if (typeof component === 'function') {
            componentInitQueue.push({ name, component });
        }
    });

    // If Alpine already exists (e.g., on SPA navigation), register immediately
    if (window.Alpine) {
        registerQueuedComponents();
    }
};

export const startAlpine = () => {
    setupEventListeners();

    // If Alpine is already running, just ensure components are registered
    if (window.Alpine) {
        registerQueuedComponents();
    }
    // Otherwise, auto-injection will start Livewire/Alpine and trigger 'alpine:init'
};

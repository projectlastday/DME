import { createAppController } from './createAppController.js';
import { registerServiceWorker } from './registerServiceWorker.js';
import { mountAppShell } from '../../shell/mountAppShell.js';
import { createAppStore } from '../state/createAppStore.js';

export function bootstrapApp() {
    const shell = mountAppShell();
    const store = createAppStore();

    const controller = createAppController({
        shell,
        store,
        windowObject: window,
    });

    controller.start();
    scheduleServiceWorkerRegistration(window);
}

function scheduleServiceWorkerRegistration(windowObject) {
    const register = () => {
        registerServiceWorker(windowObject).catch(() => null);
    };

    const scheduleIdleRegistration = () => {
        if (typeof windowObject.requestIdleCallback === 'function') {
            windowObject.requestIdleCallback(register, {
                timeout: 1500,
            });
            return;
        }

        windowObject.setTimeout(register, 300);
    };

    if (document.readyState === 'complete') {
        scheduleIdleRegistration();
        return;
    }

    windowObject.addEventListener('load', scheduleIdleRegistration, {
        once: true,
    });
}

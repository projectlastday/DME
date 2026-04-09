export function mountAppShell() {
    const target = document.querySelector('#app');

    if (!target) {
        throw new Error('App mount container #app was not found.');
    }

    target.innerHTML = `
        <main class="min-h-[100dvh] w-full max-w-2xl mx-auto p-4 sm:p-6 pt-[max(1rem,env(safe-area-inset-top))] pb-[max(1rem,env(safe-area-inset-bottom))]" aria-label="Application shell">
            <div data-app-view class="w-full"></div>
            <!-- Hidden status tracker to not break existing references -->
            <p style="display:none;" data-app-status></p>
        </main>
    `;

    const status = target.querySelector('[data-app-status]');
    const view = target.querySelector('[data-app-view]');

    if (!status || !view) {
        throw new Error('App shell UI containers were not found.');
    }

    return {
        root: target,
        status,
        view,
    };
}

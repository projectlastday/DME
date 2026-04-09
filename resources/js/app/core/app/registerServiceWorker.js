export function registerServiceWorker(windowObject = window) {
    if (!('serviceWorker' in windowObject.navigator)) {
        return Promise.resolve(null);
    }

    return windowObject.navigator.serviceWorker.register('/sw.js', {
        scope: '/',
    });
}

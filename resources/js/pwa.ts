/**
 * Register the Teacha service worker so the app keeps working offline.
 *
 * The SW is intentionally registered at the root scope ("/") so it can
 * intercept every navigation. We only register when the protocol is
 * secure (https) or we're on localhost (browsers refuse to register
 * service workers over plain http in production).
 */
export function registerServiceWorker(): void {
    if (typeof navigator === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }
    if (
        window.location.protocol !== 'https:' &&
        window.location.hostname !== 'localhost' &&
        window.location.hostname !== '127.0.0.1'
    ) {
        return;
    }
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .catch((error: unknown) => {
                // eslint-disable-next-line no-console
                console.warn(
                    'Teacha service worker failed to register:',
                    error,
                );
            });
    });
}

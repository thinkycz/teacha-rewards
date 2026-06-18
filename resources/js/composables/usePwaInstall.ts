import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

/**
 * `BeforeInstallPromptEvent` is a non-standard DOM event that Chromium-based
 * browsers fire when the PWA is installable but the user hasn't installed
 * it yet. We capture the event so we can surface a "Přidat na plochu" CTA
 * rather than relying on the browser's mini-infobar.
 */
interface BeforeInstallPromptEvent extends Event {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

export interface PwaInstallState {
    /** True when the user has the browser's native install dialog ready. */
    canShowChromePrompt: Readonly<Ref<boolean>>;
    /** True when running on iOS Safari (which does not fire beforeinstallprompt). */
    isIosSafari: Readonly<Ref<boolean>>;
    /** True when the app is already running as an installed PWA. */
    isInstalled: Readonly<Ref<boolean>>;
    /** Triggers the native install dialog and resolves with the outcome. */
    prompt: () => Promise<'accepted' | 'dismissed' | 'unavailable'>;
}

function detectIosSafari(): boolean {
    if (typeof navigator === 'undefined') {
        return false;
    }
    const ua = navigator.userAgent;
    const isIos = /iPhone|iPad|iPod/.test(ua);
    const isWebkit = /WebKit/.test(ua) && !/CriOS|FxiOS|EdgiOS/.test(ua);
    const nav = navigator as Navigator & { standalone?: boolean };
    const inStandalone = nav.standalone === true;
    return isIos && isWebkit && !inStandalone;
}

function detectInstalled(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }
    if (window.matchMedia?.('(display-mode: standalone)').matches) {
        return true;
    }
    const nav = window.navigator as Navigator & { standalone?: boolean };
    return nav.standalone === true;
}

/**
 * Reactive PWA install state. Listen for the browser's
 * `beforeinstallprompt` event so we can show a custom install banner
 * instead of (or before) the browser's mini-infobar.
 */
export function usePwaInstall(): PwaInstallState {
    const installPromptEvent = ref<BeforeInstallPromptEvent | null>(null);
    const canShowChromePrompt = ref<boolean>(false);
    const isInstalled = ref<boolean>(false);
    const isIosSafari = ref<boolean>(false);

    function handleBeforeInstallPrompt(event: Event): void {
        event.preventDefault();
        installPromptEvent.value = event as BeforeInstallPromptEvent;
        canShowChromePrompt.value = true;
    }

    function handleAppInstalled(): void {
        isInstalled.value = true;
        installPromptEvent.value = null;
        canShowChromePrompt.value = false;
    }

    onMounted(() => {
        isInstalled.value = detectInstalled();
        isIosSafari.value = detectIosSafari();
        window.addEventListener(
            'beforeinstallprompt',
            handleBeforeInstallPrompt,
        );
        window.addEventListener('appinstalled', handleAppInstalled);
    });

    onBeforeUnmount(() => {
        window.removeEventListener(
            'beforeinstallprompt',
            handleBeforeInstallPrompt,
        );
        window.removeEventListener('appinstalled', handleAppInstalled);
    });

    async function prompt(): Promise<'accepted' | 'dismissed' | 'unavailable'> {
        const event = installPromptEvent.value;
        if (event === null) {
            return 'unavailable';
        }
        await event.prompt();
        const choice = await event.userChoice;
        if (choice.outcome === 'accepted') {
            isInstalled.value = true;
        }
        installPromptEvent.value = null;
        canShowChromePrompt.value = false;
        return choice.outcome;
    }

    return {
        canShowChromePrompt,
        isIosSafari,
        isInstalled,
        prompt,
    };
}

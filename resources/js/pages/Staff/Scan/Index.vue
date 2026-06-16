<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { QrCode, Camera, AlertTriangle } from '@lucide/vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

useI18n();
const { t } = useI18n();

const page = usePage();

// Reactive state
const scannerEl = ref<HTMLElement | null>(null);
const cameraStatus = ref<'idle' | 'starting' | 'scanning' | 'denied' | 'error' | 'no-camera'>('idle');
const errorMessage = ref<string | null>(null);
const manualToken = ref('');
const manualSubmitting = ref(false);

let scanner: unknown = null;
// We use a loose type to avoid importing the runtime types of `html5-qrcode`
// at compile time, keeping the page light. The library is mounted as a
// side effect — we only need `start`, `stop`, and `clear` from it.
type ScannerInstance = {
    start: (
        cameraConfig: { facingMode: string },
        config: Record<string, unknown>,
        onSuccess: (decoded: string) => void,
        onError: (err: string) => void,
    ) => Promise<void>;
    stop: () => Promise<void>;
    clear: () => void;
};

function navigateToToken(token: string): void {
    const trimmed = token.trim();
    if (trimmed === '') {
        return;
    }
    // The public_token may arrive as a full URL (because the customer's
    // QR code points at /w/{token}) or as a bare token. Normalize.
    let raw = trimmed;
    try {
        const parsed = new URL(trimmed);
        const match = parsed.pathname.match(/\/w\/([A-Za-z0-9_-]+)/);
        if (match) {
            raw = match[1] ?? '';
        }
    } catch {
        // not a URL — treat as a bare token
    }
    if (raw === '') {
        return;
    }
    router.visit(`/staff/scan/${encodeURIComponent(raw)}`);
}

function handleManualSubmit(): void {
    if (manualToken.value.trim() === '') {
        return;
    }
    manualSubmitting.value = true;
    navigateToToken(manualToken.value);
}

async function startScanner(): Promise<void> {
    if (scannerEl.value === null) {
        return;
    }
    cameraStatus.value = 'starting';
    errorMessage.value = null;

    try {
        const mod = await import('html5-qrcode');
        const Scanner = mod.Html5Qrcode;
        const instance = new Scanner(scannerEl.value.id);
        scanner = instance;

        await instance.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 240, height: 240 },
            },
            (decoded: string) => {
                void instance.stop().catch(() => undefined);
                void instance.clear();
                scanner = null;
                cameraStatus.value = 'scanning';
                navigateToToken(decoded);
            },
            () => {
                // Per-frame decode failures are normal — html5-qrcode fires this
                // on every frame that doesn't contain a QR. We ignore.
            },
        );
        cameraStatus.value = 'scanning';
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : String(err);
        if (/Permission|NotAllowedError/i.test(message)) {
            cameraStatus.value = 'denied';
            errorMessage.value = t('staff.scan.index.camera_denied');
        } else if (/NotFoundError|no.camera/i.test(message)) {
            cameraStatus.value = 'no-camera';
            errorMessage.value = t('staff.scan.index.no_camera');
        } else {
            cameraStatus.value = 'error';
            errorMessage.value = t('staff.scan.index.scanner_error');
        }
    }
}

async function stopScanner(): Promise<void> {
    if (scanner === null) {
        return;
    }
    const instance = scanner as ScannerInstance;
    scanner = null;
    try {
        await instance.stop();
    } catch {
        // already stopped
    }
    try {
        instance.clear();
    } catch {
        // nothing to clear
    }
}

onMounted(() => {
    void startScanner();
});

onBeforeUnmount(() => {
    void stopScanner();
});

// Surface flashed error message from the server (e.g. invalid token).
const flashError = ref<string | null>(
    (page.props.flash as { error?: string | null } | undefined)?.error ?? null,
);
</script>

<template>
    <Head :title="t('staff.scan.index.title')" />

    <StaffLayout :title="t('staff.scan.index.title')">
        <div class="space-y-6">
            <header class="text-center">
                <h1 class="text-2xl font-semibold text-charcoal-900">
                    {{ t('staff.scan.index.heading') }}
                </h1>
                <p class="mt-2 text-sm text-charcoal-600">
                    {{ t('staff.scan.index.subheading') }}
                </p>
            </header>

            <!-- Camera viewport -->
            <section
                class="relative mx-auto aspect-square w-full max-w-sm overflow-hidden rounded-3xl border border-outline-glass bg-charcoal-900 shadow-sm"
            >
                <div
                    id="staff-qr-scanner"
                    ref="scannerEl"
                    class="absolute inset-0"
                />
                <div
                    v-if="cameraStatus !== 'scanning'"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-charcoal-900/80 p-6 text-center text-white"
                >
                    <Camera
                        v-if="cameraStatus === 'starting' || cameraStatus === 'idle'"
                        :size="48"
                        class="animate-pulse"
                    />
                    <AlertTriangle
                        v-else
                        :size="48"
                        class="text-amber-300"
                    />
                    <p
                        v-if="cameraStatus === 'starting' || cameraStatus === 'idle'"
                        class="text-sm"
                    >
                        {{ t('staff.scan.index.subheading') }}
                    </p>
                    <p
                        v-else-if="errorMessage"
                        class="text-sm"
                    >
                        {{ errorMessage }}
                    </p>
                </div>
            </section>

            <!-- Manual fallback -->
            <section
                class="rounded-2xl border border-outline-glass bg-white p-5 shadow-sm"
            >
                <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-500">
                    {{ t('staff.scan.index.manual_fallback') }}
                </p>
                <form
                    class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end"
                    @submit.prevent="handleManualSubmit"
                >
                    <div class="flex-1 space-y-2">
                        <Label for="manual-token">
                            {{ t('staff.scan.index.manual_link') }}
                        </Label>
                        <Input
                            id="manual-token"
                            v-model="manualToken"
                            name="manual_token"
                            type="text"
                            autocomplete="off"
                            :placeholder="t('staff.scan.index.manual_link')"
                        />
                    </div>
                    <Button
                        type="submit"
                        class="self-start sm:self-auto"
                        :disabled="manualSubmitting"
                    >
                        <QrCode :size="14" />
                        {{ t('staff.scan.index.manual_link') }}
                    </Button>
                </form>
            </section>

            <div
                v-if="flashError"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
            >
                {{ flashError }}
            </div>
        </div>
    </StaffLayout>
</template>

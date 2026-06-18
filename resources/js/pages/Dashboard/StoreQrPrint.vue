<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Printer, ArrowLeft, ExternalLink } from '@lucide/vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import { useSharedProps } from '@/composables/useSharedProps';

useBoundLocale();
const { t } = useI18n();
const { auth } = useSharedProps();

const props = defineProps<{
    store_name: string;
    program_name: string;
    wallet_url: string;
}>();

const qrDataUrl = ref<string | null>(null);

onMounted(async () => {
    const QRCode = (await import('qrcode')).default;
    qrDataUrl.value = await QRCode.toDataURL(props.wallet_url, {
        errorCorrectionLevel: 'H',
        margin: 1,
        width: 720,
    });
});

const shortUrl = computed(() => {
    try {
        const parsed = new URL(props.wallet_url);
        return parsed.host + parsed.pathname;
    } catch {
        return props.wallet_url;
    }
});

function printPage(): void {
    if (typeof window !== 'undefined') {
        window.print();
    }
}

const isAdmin = computed(() => auth.value.user?.role === 'admin');
</script>

<template>
    <Head :title="t('dashboard.store_qr.title')" />

    <AdminLayout :title="t('dashboard.store_qr.title')">
        <!-- Screen toolbar: hidden when printing. -->
        <div class="no-print flex items-center justify-between">
            <Link
                href="/dashboard"
                class="inline-flex items-center gap-1 text-xs font-semibold text-on-surface-variant transition hover:text-on-surface"
            >
                <ArrowLeft :size="12" />
                {{ t('dashboard.store_qr.back') }}
            </Link>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-outline-glass bg-white px-3 py-2 text-xs font-semibold text-on-surface transition hover:border-primary hover:bg-primary-soft"
                @click="printPage"
            >
                <Printer :size="14" />
                {{ t('dashboard.store_qr.print') }}
            </button>
        </div>

        <!-- A4 print sheet. On screen it sits inside the admin layout
             as a normal card; when printing, the scoped @media print
             rules below take over: the admin chrome is hidden, the
             sheet fills the A4 page (210 x 297mm) with proper margins,
             and the QR sits centered with generous whitespace so it
             scans reliably from a printed page. -->
        <article
            class="qr-sheet surface-card mt-6 p-8 print:mt-0 print:border-0 print:shadow-none print:p-0"
        >
            <div class="qr-sheet-inner">
                <header class="qr-sheet-header">
                    <p class="qr-sheet-store">
                        {{ store_name }}
                    </p>
                    <h1 class="qr-sheet-title">
                        {{ program_name }}
                    </h1>
                    <p class="qr-sheet-headline">
                        {{ t('dashboard.store_qr.headline') }}
                    </p>
                </header>

                <div class="qr-sheet-body">
                    <div v-if="qrDataUrl" class="qr-sheet-frame">
                        <img
                            :src="qrDataUrl"
                            :alt="
                                t('dashboard.store_qr.qr_alt', {
                                    url: wallet_url,
                                })
                            "
                            class="qr-sheet-img"
                        />
                    </div>
                    <div v-else class="qr-sheet-placeholder" />

                    <p class="qr-sheet-scan-prompt">
                        {{ t('dashboard.store_qr.scan_prompt') }}
                    </p>

                    <p class="qr-sheet-url">
                        <ExternalLink :size="12" />
                        {{ shortUrl }}
                    </p>
                </div>
            </div>
        </article>

        <p
            v-if="isAdmin"
            class="no-print mt-4 text-center text-xs text-on-surface-variant"
        >
            {{ t('dashboard.store_qr.admin_hint') }}
        </p>
    </AdminLayout>
</template>

<style scoped>
/* On-screen: look like a normal card with reasonable spacing. */
.qr-sheet {
    max-width: 32rem;
    margin-left: auto;
    margin-right: auto;
}

.qr-sheet-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.qr-sheet-header {
    margin-bottom: 2rem;
}

.qr-sheet-store {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #64748b;
}

.qr-sheet-title {
    margin-top: 0.5rem;
    font-size: 1.875rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #0f172a;
}

.qr-sheet-headline {
    margin-top: 0.75rem;
    font-size: 0.875rem;
    color: #475569;
}

.qr-sheet-body {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.qr-sheet-frame {
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 1rem;
}

.qr-sheet-img {
    display: block;
    height: 18rem;
    width: 18rem;
}

.qr-sheet-placeholder {
    height: 18rem;
    width: 18rem;
    border-radius: 1rem;
    background: #e0e7ff;
    animation: pulse 1.5s ease-in-out infinite;
}

.qr-sheet-scan-prompt {
    margin-top: 1.5rem;
    max-width: 28rem;
    font-size: 0.875rem;
    color: #1e293b;
}

.qr-sheet-url {
    margin-top: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 9999px;
    background: #e0e7ff;
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
    font-family: monospace;
    color: #4338ca;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

/*
 * Print: the sheet becomes a proper A4 page.
 *
 * The admin layout's nav, sidebar, and toolbar are hidden via the
 * global `.no-print` rule. Here we force the sheet to fill the A4
 * page area with the browser's default print margins (usually ~10mm
 * on all sides, which is what we want for a counter display print).
 *
 * The QR is sized to ~120mm — large enough to scan from arm's length
 * on a printed A4, small enough to leave room for the header and
 * scan-prompt text. Everything is centered on the page.
 */
@media print {
    .qr-sheet {
        max-width: none;
        margin: 0;
        width: 100%;
    }

    .qr-sheet-inner {
        /* A4 is 210 x 297mm. With ~10mm margins the usable area is
           ~190 x 277mm. We vertically center the content block. */
        min-height: 277mm;
        justify-content: center;
        page-break-after: avoid;
    }

    .qr-sheet-header {
        margin-bottom: 12mm;
    }

    .qr-sheet-store {
        font-size: 12pt;
    }

    .qr-sheet-title {
        font-size: 28pt;
    }

    .qr-sheet-headline {
        font-size: 13pt;
    }

    .qr-sheet-body {
        margin-bottom: 12mm;
    }

    .qr-sheet-frame {
        border: 2px solid #cbd5e1;
        padding: 6mm;
    }

    .qr-sheet-img {
        /* ~120mm — scannable from arm's length on A4 print. */
        height: 120mm;
        width: 120mm;
    }

    .qr-sheet-scan-prompt {
        font-size: 13pt;
        margin-top: 10mm;
        max-width: 160mm;
    }

    .qr-sheet-url {
        font-size: 11pt;
        margin-top: 6mm;
        padding: 3mm 6mm;
    }
}
</style>

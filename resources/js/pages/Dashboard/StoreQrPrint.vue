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
        <div class="space-y-6 print:space-y-0">
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

            <article class="surface-card print:border-0 p-8 print:shadow-none print:p-12">
                <header class="text-center">
                    <p class="label-eyebrow">
                        {{ store_name }}
                    </p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-on-surface">
                        {{ program_name }}
                    </h1>
                    <p class="mt-3 text-sm text-on-surface-variant">
                        {{ t('dashboard.store_qr.headline') }}
                    </p>
                </header>

                <div class="mt-8 flex flex-col items-center">
                    <div
                        v-if="qrDataUrl"
                        class="rounded-2xl border border-outline-glass bg-white p-4"
                    >
                        <img
                            :src="qrDataUrl"
                            :alt="t('dashboard.store_qr.qr_alt', { url: wallet_url })"
                            class="block h-72 w-72"
                        >
                    </div>
                    <div
                        v-else
                        class="h-72 w-72 animate-pulse rounded-2xl bg-primary-soft"
                    />

                    <p class="mt-6 max-w-md text-center text-sm text-on-surface">
                        {{ t('dashboard.store_qr.scan_prompt') }}
                    </p>

                    <p class="mt-3 inline-flex items-center gap-1 rounded-full bg-primary-soft px-4 py-2 text-xs font-mono text-primary">
                        <ExternalLink :size="12" />
                        {{ shortUrl }}
                    </p>
                </div>

                <footer class="mt-10 border-t border-outline-glass pt-6 text-center text-xs text-on-surface-variant">
                    {{ t('dashboard.store_qr.footer') }}
                </footer>
            </article>

            <p
                v-if="isAdmin"
                class="no-print text-center text-xs text-on-surface-variant"
            >
                {{ t('dashboard.store_qr.admin_hint') }}
            </p>
        </div>
    </AdminLayout>
</template>

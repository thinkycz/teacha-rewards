<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ArrowRight, AlertTriangle } from '@lucide/vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import Brand from '@/components/ui/Brand.vue';
import BarcodeBlock from '@/components/reward/BarcodeBlock.vue';
import { formatDateTime } from '@/lib/date';

useBoundLocale();
const { t } = useI18n();

interface WalletSummary {
    public_token: string;
    wallet_number: string;
    first_name: string;
    rewards_balance: string;
    status: string;
}

interface Transaction {
    id: number;
    type: string;
    amount: string;
    purchase_amount: string | null;
    created_at: string | null;
}

const props = defineProps<{
    wallet: WalletSummary;
    recent_transactions: Transaction[];
}>();

const isActive = computed(() => props.wallet.status === 'active');

const balanceFormatted = computed(() =>
    new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(props.wallet.rewards_balance)),
);

function typeLabel(type: string): string {
    switch (type) {
        case 'purchase_cashback':
            return t('wallet.transactions.types.purchase_cashback');
        case 'redeem':
            return t('wallet.transactions.types.redeem');
        case 'manual_add':
            return t('wallet.transactions.types.manual_add');
        case 'manual_subtract':
            return t('wallet.transactions.types.manual_subtract');
        case 'manual_set':
            return t('wallet.transactions.types.manual_set');
        default:
            return type;
    }
}

function rowSubtitle(tx: Transaction): string {
    const parts: string[] = [];
    if (tx.purchase_amount) {
        parts.push(t('wallet.transactions.purchase', { amount: tx.purchase_amount }));
    }
    const date = formatDateTime(tx.created_at);
    if (date !== '') {
        parts.push(date);
    }
    return parts.join(' · ');
}

function formatSigned(value: string): string {
    const num = Number(value);
    const abs = new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Math.abs(num));
    return (num >= 0 ? '+' : '−') + abs;
}
</script>

<template>
    <Head :title="t('wallet.show.title', { name: wallet.first_name })" />

    <div class="min-h-screen bg-surface-bg text-on-surface">
        <header class="mx-auto flex max-w-md items-center justify-between px-6 py-6">
            <Link :href="'/'">
                <Brand class="text-2xl" />
            </Link>
        </header>

        <main class="mx-auto max-w-md space-y-5 px-6 pb-20">
            <section
                v-if="!isActive"
                class="flex items-start gap-3 rounded-2xl border border-warning bg-warning-soft p-4 text-sm text-warning"
            >
                <AlertTriangle :size="16" class="mt-0.5 shrink-0" />
                <span>{{ t('wallet.show.disabled_notice') }}</span>
            </section>

            <!-- Combined card: navy header band with the wallet
                 identity + balance, white body holding the barcode
                 the staff scans at checkout. -->
            <section class="surface-card overflow-hidden">
                <header class="bg-primary p-5 text-on-primary">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-on-primary/70">
                                Teacha Rewards
                            </p>
                            <h1 class="mt-0.5 truncate text-xl font-semibold">
                                {{ wallet.first_name }}
                            </h1>
                            <p class="mt-0.5 font-mono text-xs tracking-widest text-on-primary/80">
                                {{ wallet.wallet_number }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-on-primary/70">
                                {{ t('wallet.show.balance') }}
                            </p>
                            <p class="mt-0.5 text-2xl font-bold tracking-tight tabular-nums">
                                {{ balanceFormatted }}&nbsp;Kč
                            </p>
                        </div>
                    </div>
                </header>
                <div class="p-5">
                    <BarcodeBlock
                        :value="wallet.public_token"
                        :height="80"
                        :font-size="14"
                    />
                    <p class="mt-3 text-center font-mono text-[11px] text-on-surface-variant">
                        {{ wallet.wallet_number }}
                    </p>
                </div>
            </section>

            <!-- Recent activity - tight list inside a single card,
                 one row per transaction separated by a hairline. -->
            <section
                v-if="recent_transactions.length > 0"
                class="surface-card overflow-hidden"
            >
                <header class="flex items-center justify-between border-b border-outline-glass px-5 py-3">
                    <h2 class="label-eyebrow">
                        {{ t('wallet.show.recent_heading') }}
                    </h2>
                    <Link
                        :href="`/w/${wallet.public_token}/activity`"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary transition hover:text-primary-container"
                    >
                        {{ t('wallet.show.view_all_activity') }}
                        <ArrowRight :size="12" />
                    </Link>
                </header>
                <ul>
                    <li
                        v-for="(tx, idx) in recent_transactions"
                        :key="tx.id"
                        class="flex items-center justify-between gap-4 px-5 py-3"
                        :class="{ 'border-t border-outline-glass': idx > 0 }"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-on-surface">
                                {{ typeLabel(tx.type) }}
                            </p>
                            <p
                                v-if="rowSubtitle(tx)"
                                class="mt-0.5 truncate text-xs text-on-surface-variant"
                            >
                                {{ rowSubtitle(tx) }}
                            </p>
                        </div>
                        <p
                            class="shrink-0 text-sm font-bold tabular-nums"
                            :class="Number(tx.amount) >= 0 ? 'text-success' : 'text-error-red'"
                        >
                            {{ formatSigned(tx.amount) }}&nbsp;Kč
                        </p>
                    </li>
                </ul>
            </section>
        </main>
    </div>
</template>

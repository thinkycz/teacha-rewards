<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ArrowRight, AlertTriangle } from '@lucide/vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import { useTransactionFormat } from '@/composables/useTransactionFormat';
import Brand from '@/components/ui/Brand.vue';
import BarcodeBlock from '@/components/reward/BarcodeBlock.vue';
import StampCard from '@/components/reward/StampCard.vue';
import { formatDateTime } from '@/lib/date';

useBoundLocale();
const { t } = useI18n();
const { formatAmount, stampsEqRewards, typeLabel } = useTransactionFormat();

interface WalletSummary {
    public_token: string;
    wallet_number: string;
    type: 'cashback' | 'stamps';
    first_name: string;
    rewards_balance: string;
    stamps_count: number;
    status: string;
}

interface Transaction {
    id: number;
    type: string;
    amount: string;
    created_at: string | null;
    purchase_amount: string | null;
    balance_after: string;
    wallet_type: 'cashback' | 'stamps';
}

interface ProgramConfig {
    stamps_per_reward: number;
    stamps_per_reward_label: string;
    stamp_icon: string;
}

const props = defineProps<{
    wallet: WalletSummary;
    recent_transactions: Transaction[];
    program: ProgramConfig;
}>();

const isActive = computed(() => props.wallet.status === 'active');
const isStamps = computed(() => props.wallet.type === 'stamps');

const balanceFormatted = computed(() =>
    new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(props.wallet.rewards_balance)),
);

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

            <!-- Top card: navy identity header with name + balance.
                 In cashback mode this is the wallet hero. In stamps
                 mode the paper loyalty card (next section) is the
                 hero, so this card shrinks to just identity. -->
            <section class="surface-card overflow-hidden">
                <header class="bg-primary p-5 text-on-primary">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-on-primary/70">
                                Teacha Rewards
                            </p>
                            <h1 class="mt-0.5 flex items-center gap-2 truncate text-xl font-semibold">
                                <span class="truncate">{{ wallet.first_name }}</span>
                                <span
                                    :class="wallet.type === 'stamps'
                                        ? 'bg-teal-500/90 text-white'
                                        : 'bg-amber-500/90 text-white'"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                >
                                    <span aria-hidden="true">{{ wallet.type === 'stamps' ? '\u{1F3F7}\u{FE0F}' : '\u{1F4B0}' }}</span>
                                    {{ t('dashboard.wallets.show.type_' + wallet.type) }}
                                </span>
                            </h1>
                            <p class="mt-0.5 font-mono text-xs tracking-widest text-on-primary/80">
                                {{ wallet.wallet_number }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-on-primary/70">
                                {{ isStamps ? t('dashboard.wallets.show.balance_stamps') : t('wallet.show.balance') }}
                            </p>
                            <p
                                v-if="isStamps"
                                class="mt-0.5 text-2xl font-bold tracking-tight tabular-nums"
                            >
                                {{ wallet.stamps_count }} / {{ program.stamps_per_reward }}
                            </p>
                            <p
                                v-else
                                class="mt-0.5 text-2xl font-bold tracking-tight tabular-nums"
                            >
                                {{ balanceFormatted }}&nbsp;Kč
                            </p>
                        </div>
                    </div>
                </header>
            </section>

            <!-- Paper loyalty card. Real-paper aesthetic: cream
                 surface, business-card aspect ratio, sits as its own
                 hero element. Only visible in stamps mode. -->
            <section
                v-if="isStamps"
                class="flex justify-center"
            >
                <StampCard
                    :stamps="wallet.stamps_count"
                    :total="program.stamps_per_reward"
                    :reward-label="program.stamps_per_reward_label"
                    :icon="program.stamp_icon"
                />
            </section>

            <!-- Barcode: the staff scans this at checkout. White body
                 so the code prints crisply even in bright light. -->
            <section class="surface-card overflow-hidden">
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

            <!-- Recent activity - tight list inside a single card -->
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
                                v-if="stampsEqRewards(tx, program.stamps_per_reward_label)"
                                class="mt-0.5 truncate text-[11px] text-on-surface-variant"
                            >
                                {{ stampsEqRewards(tx, program.stamps_per_reward_label) }}
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
                            {{ formatAmount(tx, wallet.type, program.stamps_per_reward) }}
                        </p>
                    </li>
                </ul>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    Users,
    UserX,
    ShoppingBag,
    CircleDollarSign,
    QrCode,
    Wallet as WalletIcon,
    ArrowRight,
} from '@lucide/vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import TransactionList from '@/components/reward/TransactionList.vue';

useI18n();
const { t } = useI18n();

interface Stats {
    active_wallets: number;
    disabled_wallets: number;
    today_purchase_count: number;
    today_cashback: string;
}

interface RecentTransaction {
    id: number;
    type: string;
    amount: string;
    wallet_first_name: string | null;
    wallet_number: string | null;
    wallet_public_token: string | null;
    staff_name: string | null;
    created_at: string | null;
}

const props = defineProps<{
    stats: Stats;
    recent_transactions: RecentTransaction[];
}>();

const cashbackNumber = computed(() => Number(props.stats.today_cashback));
const cashbackFormatted = computed(() =>
    new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(cashbackNumber.value),
);

interface Tile {
    label: string;
    value: string;
    icon: typeof Users;
    accent: 'primary' | 'success' | 'warning' | 'error';
}

const tiles = computed<Tile[]>(() => [
    { label: t('dashboard.dashboard.active_wallets'), value: String(props.stats.active_wallets), icon: Users, accent: 'success' },
    { label: t('dashboard.dashboard.disabled_wallets'), value: String(props.stats.disabled_wallets), icon: UserX, accent: 'warning' },
    { label: t('dashboard.dashboard.today_purchases'), value: String(props.stats.today_purchase_count), icon: ShoppingBag, accent: 'primary' },
    { label: t('dashboard.dashboard.today_cashback'), value: `${cashbackFormatted.value} Kč`, icon: CircleDollarSign, accent: 'primary' },
]);

function tileBg(accent: Tile['accent']): string {
    switch (accent) {
        case 'success':
            return 'bg-success-soft text-success';
        case 'warning':
            return 'bg-warning-soft text-warning';
        case 'error':
            return 'bg-error-soft text-error-red';
        default:
            return 'bg-primary-soft text-primary';
    }
}
</script>

<template>
    <Head :title="t('dashboard.dashboard.title')" />

    <AdminLayout :title="t('dashboard.dashboard.title')">
        <div class="space-y-6">
            <header>
                <h1 class="heading-2">
                    {{ t('dashboard.dashboard.heading') }}
                </h1>
                <p class="mt-1 label-help">
                    {{ t('dashboard.dashboard.subtitle') ?? t('dashboard.dashboard.heading') }}
                </p>
            </header>

            <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    v-for="tile in tiles"
                    :key="tile.label"
                    class="surface-card p-4"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl"
                        :class="tileBg(tile.accent)"
                    >
                        <component
                            :is="tile.icon"
                            :size="18"
                        />
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-on-surface">
                        {{ tile.value }}
                    </p>
                    <p class="label-eyebrow mt-1">
                        {{ tile.label }}
                    </p>
                </div>
            </section>

            <section>
                <h2 class="label-eyebrow mb-3">
                    {{ t('dashboard.dashboard.quick_actions') }}
                </h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <Link
                        href="/dashboard/scan"
                        class="group flex items-center gap-4 surface-card p-4 transition hover:border-primary"
                    >
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-soft text-primary transition group-hover:bg-primary group-hover:text-on-primary"
                        >
                            <QrCode :size="20" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-on-surface">
                                {{ t('dashboard.dashboard.scan_qr') }}
                            </p>
                        </div>
                        <ArrowRight :size="16" class="text-on-surface-variant transition group-hover:text-primary" />
                    </Link>
                    <Link
                        href="/dashboard/wallets"
                        class="group flex items-center gap-4 surface-card p-4 transition hover:border-primary"
                    >
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-soft text-primary transition group-hover:bg-primary group-hover:text-on-primary"
                        >
                            <WalletIcon :size="20" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-on-surface">
                                {{ t('dashboard.dashboard.view_wallets') }}
                            </p>
                        </div>
                        <ArrowRight :size="16" class="text-on-surface-variant transition group-hover:text-primary" />
                    </Link>
                </div>
            </section>

            <section class="surface-card p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="label-eyebrow">
                        {{ t('dashboard.dashboard.recent') }}
                    </h2>
                    <Link
                        href="/dashboard/transactions"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary transition hover:text-primary-container"
                    >
                        {{ t('dashboard.dashboard.view_all_transactions') }}
                        <ArrowRight :size="12" />
                    </Link>
                </div>
                <TransactionList
                    :transactions="recent_transactions"
                    :empty-message="t('dashboard.transactions.index.empty')"
                />
            </section>
        </div>
    </AdminLayout>
</template>

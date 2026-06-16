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
} from '@lucide/vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
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
</script>

<template>
    <Head :title="t('staff.dashboard.title')" />

    <StaffLayout :title="t('staff.dashboard.title')">
        <div class="space-y-6">
            <header>
                <h1 class="text-2xl font-semibold text-charcoal-900">
                    {{ t('staff.dashboard.heading') }}
                </h1>
            </header>

            <!-- Stat tiles -->
            <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    class="rounded-2xl border border-outline-glass bg-white p-4 shadow-sm"
                >
                    <div class="flex items-center gap-2 text-charcoal-500">
                        <Users :size="14" />
                        <span
                            class="text-[10px] font-semibold uppercase tracking-wider"
                        >
                            {{ t('staff.dashboard.active_wallets') }}
                        </span>
                    </div>
                    <p
                        class="mt-2 text-2xl font-semibold text-charcoal-900 sm:text-3xl"
                    >
                        {{ stats.active_wallets }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-outline-glass bg-white p-4 shadow-sm"
                >
                    <div class="flex items-center gap-2 text-charcoal-500">
                        <UserX :size="14" />
                        <span
                            class="text-[10px] font-semibold uppercase tracking-wider"
                        >
                            {{ t('staff.dashboard.disabled_wallets') }}
                        </span>
                    </div>
                    <p
                        class="mt-2 text-2xl font-semibold text-charcoal-900 sm:text-3xl"
                    >
                        {{ stats.disabled_wallets }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-outline-glass bg-white p-4 shadow-sm"
                >
                    <div class="flex items-center gap-2 text-charcoal-500">
                        <ShoppingBag :size="14" />
                        <span
                            class="text-[10px] font-semibold uppercase tracking-wider"
                        >
                            {{ t('staff.dashboard.today_purchases') }}
                        </span>
                    </div>
                    <p
                        class="mt-2 text-2xl font-semibold text-charcoal-900 sm:text-3xl"
                    >
                        {{ stats.today_purchase_count }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-outline-glass bg-white p-4 shadow-sm"
                >
                    <div class="flex items-center gap-2 text-charcoal-500">
                        <CircleDollarSign :size="14" />
                        <span
                            class="text-[10px] font-semibold uppercase tracking-wider"
                        >
                            {{ t('staff.dashboard.today_cashback') }}
                        </span>
                    </div>
                    <p
                        class="mt-2 text-xl font-semibold text-charcoal-900 sm:text-2xl"
                    >
                        {{ cashbackFormatted }}&nbsp;Kč
                    </p>
                </div>
            </section>

            <!-- Quick actions -->
            <section>
                <h2
                    class="mb-2 text-sm font-semibold uppercase tracking-wider text-charcoal-500"
                >
                    {{ t('staff.dashboard.quick_actions') }}
                </h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <Link
                        href="/staff/scan"
                        class="flex items-center gap-3 rounded-2xl border border-outline-glass bg-white p-4 shadow-sm transition hover:border-matcha-300 hover:bg-sage-50"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-matcha-500 to-matcha-700 text-white"
                        >
                            <QrCode :size="20" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-charcoal-900">
                                {{ t('staff.dashboard.scan_qr') }}
                            </p>
                        </div>
                    </Link>
                    <Link
                        href="/staff/wallets"
                        class="flex items-center gap-3 rounded-2xl border border-outline-glass bg-white p-4 shadow-sm transition hover:border-matcha-300 hover:bg-sage-50"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-matcha-500 to-matcha-700 text-white"
                        >
                            <WalletIcon :size="20" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-charcoal-900">
                                {{ t('staff.dashboard.view_wallets') }}
                            </p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Recent activity -->
            <section>
                <div class="mb-2 flex items-center justify-between">
                    <h2
                        class="text-sm font-semibold uppercase tracking-wider text-charcoal-500"
                    >
                        {{ t('staff.dashboard.recent') }}
                    </h2>
                    <Link
                        href="/staff/transactions"
                        class="text-xs font-semibold text-matcha-700 hover:text-matcha-800"
                    >
                        {{ t('staff.dashboard.view_all_transactions') }} →
                    </Link>
                </div>
                <TransactionList
                    :transactions="recent_transactions"
                    :empty-message="t('staff.transactions.index.empty')"
                />
            </section>
        </div>
    </StaffLayout>
</template>

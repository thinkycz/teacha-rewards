<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ArrowLeft } from '@lucide/vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import Brand from '@/components/ui/Brand.vue';
import TransactionList from '@/components/reward/TransactionList.vue';

useBoundLocale();
const { t } = useI18n();

interface WalletSummary {
    public_token: string;
    wallet_number: string;
    first_name: string;
    rewards_balance: string;
    lifetime_earned: string;
    lifetime_redeemed: string;
}

interface Transaction {
    id: number;
    type: string;
    amount: string;
    purchase_amount: string | null;
    balance_after: string;
    note: string | null;
    created_at: string | null;
    staff_name: string | null;
}

defineProps<{
    wallet: WalletSummary;
    transactions: Transaction[];
}>();
</script>

<template>
    <Head :title="t('wallet.activity.title', { name: wallet.first_name })" />

    <div class="min-h-screen bg-surface-bg text-on-surface">
        <header class="mx-auto flex max-w-md items-center justify-between px-6 py-6">
            <Link :href="`/w/${wallet.public_token}`">
                <Brand class="text-2xl" />
            </Link>
        </header>

        <main class="mx-auto max-w-md space-y-6 px-6 pb-20">
            <div>
                <Link
                    :href="`/w/${wallet.public_token}`"
                    class="inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant transition hover:text-on-surface"
                >
                    <ArrowLeft :size="14" />
                    {{ t('wallet.activity.back') }}
                </Link>
                <h1 class="heading-1 mt-3">
                    {{ t('wallet.activity.heading') }}
                </h1>
                <p class="mt-2 text-sm text-on-surface-variant">
                    {{ t('wallet.activity.subheading', { name: wallet.first_name }) }}
                </p>
            </div>

            <section
                v-if="transactions.length === 0"
                class="surface-card p-8 text-center"
            >
                <p class="text-on-surface-variant">
                    {{ t('wallet.activity.empty') }}
                </p>
            </section>

            <section v-else>
                <TransactionList
                    :transactions="transactions"
                    :show-balance-after="true"
                />
            </section>
        </main>
    </div>
</template>

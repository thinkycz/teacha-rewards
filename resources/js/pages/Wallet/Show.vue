<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useBoundLocale } from '@/composables/useBoundLocale';
import Brand from '@/components/ui/Brand.vue';
import WalletCard from '@/components/reward/WalletCard.vue';
import RewardsBalance from '@/components/reward/RewardsBalance.vue';
import TransactionList from '@/components/reward/TransactionList.vue';
import QRCodeBlock from '@/components/reward/QRCodeBlock.vue';

useBoundLocale();
const { t } = useI18n();

interface WalletSummary {
    public_token: string;
    wallet_number: string;
    first_name: string;
    rewards_balance: string;
    lifetime_earned: string;
    lifetime_redeemed: string;
    status: string;
}

interface Transaction {
    id: number;
    type: string;
    amount: string;
    balance_after: string;
    note: string | null;
    created_at: string | null;
    staff_name: string | null;
}

const props = defineProps<{
    wallet: WalletSummary;
    recent_transactions: Transaction[];
    wallet_url: string;
}>();

const isActive = computed(() => props.wallet.status === 'active');
</script>

<template>
    <Head :title="t('wallet.show.title', { name: wallet.first_name })" />

    <div class="min-h-screen bg-cream-50 text-charcoal-900">
        <header class="mx-auto flex max-w-md items-center justify-between px-6 py-6">
            <Link :href="'/'">
                <Brand class="text-2xl" />
            </Link>
        </header>

        <main class="mx-auto max-w-md space-y-8 px-6 pb-20">
            <section v-if="!isActive" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                {{ t('wallet.show.disabled_notice') }}
            </section>

            <WalletCard :wallet="wallet">
                <RewardsBalance :amount="wallet.rewards_balance" />
            </WalletCard>

            <section class="rounded-3xl bg-white p-6 shadow-soft ring-1 ring-sage-200">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-charcoal-500">
                    {{ t('wallet.show.qr_heading') }}
                </h2>
                <p class="mt-2 text-xs text-charcoal-500">
                    {{ t('wallet.show.qr_help') }}
                </p>
                <QRCodeBlock :url="wallet_url" class="mt-4" />
                <p class="mt-4 break-all text-center font-mono text-xs text-charcoal-500">
                    {{ wallet_url }}
                </p>
            </section>

            <section class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-sage-200">
                    <p class="text-xs uppercase tracking-wider text-charcoal-500">
                        {{ t('wallet.show.lifetime_earned') }}
                    </p>
                    <p class="mt-1 text-xl font-semibold text-charcoal-900">
                        {{ wallet.lifetime_earned }}&nbsp;Kč
                    </p>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-sage-200">
                    <p class="text-xs uppercase tracking-wider text-charcoal-500">
                        {{ t('wallet.show.lifetime_redeemed') }}
                    </p>
                    <p class="mt-1 text-xl font-semibold text-charcoal-900">
                        {{ wallet.lifetime_redeemed }}&nbsp;Kč
                    </p>
                </div>
            </section>

            <section v-if="recent_transactions.length > 0">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-charcoal-500">
                    {{ t('wallet.show.recent_heading') }}
                </h2>
                <TransactionList :transactions="recent_transactions" />
            </section>

            <div class="flex justify-center">
                <Link
                    :href="`/w/${wallet.public_token}/activity`"
                    class="text-sm font-medium text-matcha-700 hover:text-matcha-800"
                >
                    {{ t('wallet.show.view_all_activity') }}
                </Link>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ArrowRight, AlertTriangle } from '@lucide/vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import Brand from '@/components/ui/Brand.vue';
import WalletCard from '@/components/reward/WalletCard.vue';
import RewardsBalance from '@/components/reward/RewardsBalance.vue';
import TransactionList from '@/components/reward/TransactionList.vue';
import QRCodeBlock from '@/components/reward/QRCodeBlock.vue';
import BarcodeBlock from '@/components/reward/BarcodeBlock.vue';

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

    <div class="min-h-screen bg-surface-bg text-on-surface">
        <header class="mx-auto flex max-w-md items-center justify-between px-6 py-6">
            <Link :href="'/'">
                <Brand class="text-2xl" />
            </Link>
        </header>

        <main class="mx-auto max-w-md space-y-6 px-6 pb-20">
            <section
                v-if="!isActive"
                class="flex items-start gap-3 rounded-2xl border border-warning bg-warning-soft p-4 text-sm text-warning"
            >
                <AlertTriangle :size="16" class="mt-0.5 shrink-0" />
                <span>{{ t('wallet.show.disabled_notice') }}</span>
            </section>

            <WalletCard :wallet="wallet">
                <RewardsBalance :amount="wallet.rewards_balance" />
            </WalletCard>

            <section class="surface-card p-6">
                <h2 class="label-eyebrow">
                    {{ t('wallet.show.qr_heading') }}
                </h2>
                <p class="mt-1 label-help">
                    {{ t('wallet.show.qr_help') }}
                </p>
                <QRCodeBlock :url="wallet_url" class="mt-4" />
                <p class="mt-4 break-all text-center font-mono text-[11px] text-on-surface-variant">
                    {{ wallet_url }}
                </p>

                <h3 class="label-eyebrow mt-6">
                    {{ t('wallet.show.barcode_heading') }}
                </h3>
                <p class="mt-1 label-help">
                    {{ t('wallet.show.barcode_help') }}
                </p>
                <BarcodeBlock
                    :value="wallet.public_token"
                    class="mt-3"
                />
                <p class="mt-2 text-center font-mono text-[11px] text-on-surface-variant">
                    {{ wallet.wallet_number }}
                </p>
            </section>

            <section class="grid grid-cols-2 gap-4">
                <div class="surface-card p-4">
                    <p class="label-eyebrow">
                        {{ t('wallet.show.lifetime_earned') }}
                    </p>
                    <p class="mt-1 text-xl font-bold text-on-surface">
                        {{ wallet.lifetime_earned }}&nbsp;Kč
                    </p>
                </div>
                <div class="surface-card p-4">
                    <p class="label-eyebrow">
                        {{ t('wallet.show.lifetime_redeemed') }}
                    </p>
                    <p class="mt-1 text-xl font-bold text-on-surface">
                        {{ wallet.lifetime_redeemed }}&nbsp;Kč
                    </p>
                </div>
            </section>

            <section
                v-if="recent_transactions.length > 0"
                class="surface-card p-6"
            >
                <header class="mb-4 flex items-center justify-between">
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
                <TransactionList
                    :transactions="recent_transactions"
                    :empty-message="t('wallet.transactions.empty')"
                />
            </section>
        </main>
    </div>
</template>

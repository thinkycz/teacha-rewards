<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import TransactionItem from '@/components/reward/TransactionItem.vue';

useI18n();
const { t } = useI18n();

interface Transaction {
    id: number;
    type: string;
    amount: string;
    purchase_amount?: string | null;
    balance_after?: string | null;
    note?: string | null;
    wallet_type?: 'cashback' | 'stamps' | null;
    created_at: string | null;
    staff_name: string | null;
}

withDefaults(
    defineProps<{
        transactions: Transaction[];
        showBalanceAfter?: boolean;
        /**
         * Force a single mode for the whole list. Default is undefined
         * so each row's own `wallet_type` is used (correct for mixed
         * recent-activity lists like the staff dashboard).
         */
        stampsMode?: boolean;
        emptyMessage?: string;
        stampsPerReward?: number;
        rewardLabel?: string;
    }>(),
    {
        showBalanceAfter: false,
        stampsMode: undefined,
        emptyMessage: '',
        stampsPerReward: 10,
        rewardLabel: '',
    },
);
</script>

<template>
    <div
        v-if="transactions.length === 0"
        class="surface-card p-6 text-center text-sm text-on-surface-variant"
    >
        {{ emptyMessage || t('wallet.transactions.empty') }}
    </div>
    <ul v-else class="space-y-2">
        <li
            v-for="tx in transactions"
            :key="tx.id"
        >
            <TransactionItem
                :transaction="tx"
                :show-balance-after="showBalanceAfter"
                :stamps-mode="stampsMode"
                :stamps-per-reward="stampsPerReward"
                :reward-label="rewardLabel"
            />
        </li>
    </ul>
</template>

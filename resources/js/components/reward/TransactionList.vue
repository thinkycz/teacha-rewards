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
    balance_after: string;
    note: string | null;
    created_at: string | null;
    staff_name: string | null;
}

withDefaults(
    defineProps<{
        transactions: Transaction[];
        showBalanceAfter?: boolean;
        emptyMessage?: string;
    }>(),
    {
        showBalanceAfter: false,
        emptyMessage: '',
    },
);
</script>

<template>
    <div v-if="transactions.length === 0" class="rounded-2xl bg-white p-6 text-center text-sm text-charcoal-500 shadow-soft ring-1 ring-sage-200">
        {{ emptyMessage || t('wallet.transactions.empty') }}
    </div>
    <ul v-else class="space-y-2">
        <li v-for="tx in transactions" :key="tx.id">
            <TransactionItem :transaction="tx" :show-balance-after="showBalanceAfter" />
        </li>
    </ul>
</template>

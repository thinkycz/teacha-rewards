<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Transaction {
    id: number;
    type: string;
    amount: string;
    purchase_amount?: string | null;
    balance_after?: string | null;
    note?: string | null;
    created_at: string | null;
    staff_name: string | null;
}

const { t } = useI18n();

const props = withDefaults(
    defineProps<{
        transaction: Transaction;
        showBalanceAfter?: boolean;
    }>(),
    {
        showBalanceAfter: false,
    },
);

const amountNumber = computed(() => Number(props.transaction.amount));
const isPositive = computed(() => amountNumber.value >= 0);
const formattedAmount = computed(() => {
    const formatted = new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Math.abs(amountNumber.value));
    return (isPositive.value ? '+' : '−') + formatted;
});

const typeLabel = computed(() => {
    switch (props.transaction.type) {
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
            return props.transaction.type;
    }
});

const subtitle = computed(() => {
    const parts: string[] = [];
    if (props.transaction.purchase_amount) {
        parts.push(t('wallet.transactions.purchase', { amount: props.transaction.purchase_amount }));
    }
    if (props.transaction.note) {
        parts.push(props.transaction.note);
    }
    if (props.transaction.staff_name) {
        parts.push(props.transaction.staff_name);
    }
    return parts.join(' · ');
});

const dateLabel = computed(() => {
    if (!props.transaction.created_at) {
        return '';
    }
    try {
        return new Intl.DateTimeFormat(undefined, {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(props.transaction.created_at));
    } catch {
        return props.transaction.created_at;
    }
});
</script>

<template>
    <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-sage-200">
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-charcoal-900">
                    {{ typeLabel }}
                </p>
                <p v-if="subtitle" class="mt-0.5 truncate text-xs text-charcoal-500">
                    {{ subtitle }}
                </p>
                <p v-if="dateLabel" class="mt-0.5 text-xs text-charcoal-400">
                    {{ dateLabel }}
                </p>
            </div>
            <div class="text-right">
                <p
                    class="text-base font-semibold"
                    :class="isPositive ? 'text-matcha-700' : 'text-amber-700'"
                >
                    {{ formattedAmount }}&nbsp;Kč
                </p>
                <p v-if="showBalanceAfter" class="mt-0.5 text-xs text-charcoal-400">
                    {{ t('wallet.transactions.balance_after', { amount: transaction.balance_after }) }}
                </p>
            </div>
        </div>
    </div>
</template>

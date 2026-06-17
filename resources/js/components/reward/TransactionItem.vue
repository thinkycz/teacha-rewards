<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatDateTime } from '@/lib/date';

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
        stampsMode?: boolean;
    }>(),
    {
        showBalanceAfter: false,
        stampsMode: false,
    },
);

const amountNumber = computed(() => Number(props.transaction.amount));
const isPositive = computed(() => amountNumber.value >= 0);

// "5.00" -> "+5", "-2.00" -> "−2". In stamps mode the unit is
// whole stamps (no Kc). In cashback mode it's Kc to two decimals.
const formattedAmount = computed(() => {
    const abs = Math.abs(amountNumber.value);
    if (props.stampsMode) {
        const intStr = new Intl.NumberFormat('cs-CZ').format(abs);
        return (isPositive.value ? '+' : '−') + intStr;
    }
    const formatted = new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(abs);
    return (isPositive.value ? '+' : '−') + formatted;
});

const unitSuffix = computed(() => (props.stampsMode ? ' ' + t('common.stamps') : '&nbsp;Kč'));

const typeLabel = computed(() => {
    switch (props.transaction.type) {
        case 'purchase_cashback':
            return t('wallet.transactions.types.purchase_cashback');
        case 'redeem':
            return t('wallet.transactions.types.redeem');
        case 'stamp_earn':
            return t('wallet.transactions.types.stamp_earn');
        case 'stamp_redeem':
            return t('wallet.transactions.types.stamp_redeem');
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

const dateLabel = computed(() => formatDateTime(props.transaction.created_at));
</script>

<template>
    <div class="surface-card p-4">
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-on-surface">
                    {{ typeLabel }}
                </p>
                <p
                    v-if="subtitle"
                    class="mt-0.5 truncate text-xs text-on-surface-variant"
                >
                    {{ subtitle }}
                </p>
                <p
                    v-if="dateLabel"
                    class="mt-0.5 font-mono text-xs text-on-surface-variant/70"
                >
                    {{ dateLabel }}
                </p>
            </div>
            <div class="text-right">
                <p
                    class="text-base font-bold tabular-nums"
                    :class="isPositive ? 'text-success' : 'text-error-red'"
                    v-html="formattedAmount + unitSuffix"
                />
                <p
                    v-if="showBalanceAfter && transaction.balance_after !== null && transaction.balance_after !== undefined"
                    class="mt-0.5 text-xs text-on-surface-variant/70"
                >
                    {{ t('wallet.transactions.balance_after', { amount: transaction.balance_after }) }}
                </p>
            </div>
        </div>
    </div>
</template>

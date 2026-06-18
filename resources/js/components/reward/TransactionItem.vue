<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatDateTime } from '@/lib/date';
import { useTransactionFormat } from '@/composables/useTransactionFormat';

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

const { t } = useI18n();
const {
    formatAmount,
    formatBalance,
    stampsEqRewards,
    typeLabel: typeLabelFormat,
} = useTransactionFormat();

const props = withDefaults(
    defineProps<{
        transaction: Transaction;
        showBalanceAfter?: boolean;
        /**
         * Optional override for the wallet's type. When the row comes
         * with `wallet_type` already on it, we use that (covers mixed
         * recent-activity lists). When not set, we fall back to the
         * row's own `wallet_type` field. Last-resort default is
         * `cashback` for legacy callers that haven't been updated yet.
         */
        stampsMode?: boolean;
        /**
         * Program settings needed to compute the stamps cost for a
         * STAMP_REDEEM row (|amount| * stamps_per_reward).
         */
        stampsPerReward?: number;
        rewardLabel?: string;
    }>(),
    {
        showBalanceAfter: false,
        stampsMode: undefined,
        stampsPerReward: 10,
        rewardLabel: '',
    },
);

const amountNumber = computed(() => Number(props.transaction.amount));
const isPositive = computed(() => amountNumber.value >= 0);

// Resolve the effective wallet type for this row: caller-provided
// `stampsMode` wins (for backward compat), then the row's own
// `wallet_type`, then default to cashback.
const effectiveWalletType = computed<'cashback' | 'stamps'>(() => {
    if (props.stampsMode !== undefined) {
        return props.stampsMode ? 'stamps' : 'cashback';
    }
    return props.transaction.wallet_type === 'stamps' ? 'stamps' : 'cashback';
});

const formattedAmount = computed(() =>
    formatAmount(
        props.transaction,
        effectiveWalletType.value,
        props.stampsPerReward,
    ),
);

const formattedBalance = computed(() => {
    if (
        props.transaction.balance_after === null ||
        props.transaction.balance_after === undefined
    ) {
        return null;
    }
    return formatBalance(
        props.transaction.balance_after,
        effectiveWalletType.value,
    );
});

const eqRewards = computed(() => {
    if (props.transaction.type !== 'stamp_redeem') {
        return null;
    }
    return stampsEqRewards(props.transaction, props.rewardLabel);
});

const subtitle = computed(() => {
    const parts: string[] = [];
    if (eqRewards.value) {
        parts.push(eqRewards.value);
    }
    if (props.transaction.purchase_amount) {
        parts.push(
            t('wallet.transactions.purchase', {
                amount: props.transaction.purchase_amount,
            }),
        );
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
                    {{ typeLabelFormat(transaction.type) }}
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
                >
                    {{ formattedAmount }}
                </p>
                <p
                    v-if="showBalanceAfter && formattedBalance"
                    class="mt-0.5 text-xs text-on-surface-variant/70"
                >
                    {{
                        t('wallet.transactions.balance_after', {
                            amount: formattedBalance,
                        })
                    }}
                </p>
            </div>
        </div>
    </div>
</template>

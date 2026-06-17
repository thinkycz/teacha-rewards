<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    ExternalLink,
    ShoppingBag,
    ArrowDownToLine,
    Sliders,
    Power,
    PowerOff,
    AlertTriangle,
} from '@lucide/vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Select from '@/components/ui/Select.vue';
import FieldError from '@/components/ui/FieldError.vue';
import TransactionList from '@/components/reward/TransactionList.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { fieldError } from '@/composables/useFieldError';
import { formatDateTime } from '@/lib/date';

const { t } = useI18n();

interface WalletSummary {
    id: number;
    public_token: string;
    wallet_number: string;
    first_name: string;
    phone: string;
    phone_normalized: string;
    rewards_balance: string;
    lifetime_earned: string;
    lifetime_redeemed: string;
    status: 'active' | 'disabled';
    last_used_at: string | null;
}

interface WalletTransaction {
    id: number;
    type: string;
    amount: string;
    purchase_amount: string | null;
    cashback_rate: string | null;
    balance_before: string;
    balance_after: string;
    note: string | null;
    staff_name: string | null;
    created_at: string | null;
}

const props = defineProps<{
    wallet: WalletSummary;
    transactions: WalletTransaction[];
}>();

const isActive = computed(() => props.wallet.status === 'active');

const balanceNumber = computed(() => Number(props.wallet.rewards_balance));
const balanceFormatted = computed(() =>
    new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(balanceNumber.value),
);

const lifetimeEarnedFormatted = computed(() =>
    new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(props.wallet.lifetime_earned)),
);
const lifetimeRedeemedFormatted = computed(() =>
    new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(props.wallet.lifetime_redeemed)),
);

type Action = 'purchase' | 'redeem' | 'adjust' | null;
const openAction = ref<Action>(null);

function openPanel(action: Action): void {
    openAction.value = openAction.value === action ? null : action;
}

const purchaseForm = useForm({ purchase_amount: '' });
const redeemForm = useForm({ amount: '' });
const adjustForm = useForm({
    type: 'add' as 'add' | 'subtract' | 'set',
    amount: '',
    note: '',
});

function submitPurchase(): void {
    purchaseForm.post(`/wallets/${props.wallet.id}/purchase`, {
        preserveScroll: true,
        onSuccess: () => {
            purchaseForm.reset();
            openAction.value = null;
        },
    });
}

function submitRedeem(): void {
    redeemForm.post(`/wallets/${props.wallet.id}/redeem`, {
        preserveScroll: true,
        onSuccess: () => {
            redeemForm.reset();
            openAction.value = null;
        },
    });
}

function submitAdjust(): void {
    adjustForm.post(`/wallets/${props.wallet.id}/adjust`, {
        preserveScroll: true,
        onSuccess: () => {
            adjustForm.reset();
            openAction.value = null;
        },
    });
}

const confirmDialog = useConfirmDialog();

async function toggleStatus(): Promise<void> {
    if (isActive.value) {
        const ok = await confirmDialog.confirm(t('dashboard.wallets.show.disable_confirm'), {
            variant: 'danger',
            confirmLabel: t('dashboard.wallets.show.disable'),
        });
        if (!ok) {
            return;
        }
        router.post(`/wallets/${props.wallet.id}/disable`, {}, { preserveScroll: true });
    } else {
        const ok = await confirmDialog.confirm(t('dashboard.wallets.show.enable_confirm'), {
            confirmLabel: t('dashboard.wallets.show.enable'),
        });
        if (!ok) {
            return;
        }
        router.post(`/wallets/${props.wallet.id}/enable`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="t('dashboard.wallets.show.title', { name: wallet.first_name })" />

    <AdminLayout :title="t('dashboard.wallets.show.title', { name: wallet.first_name })">
        <div class="space-y-6">
            <section
                v-if="!isActive"
                class="surface-card border-warning bg-warning-soft p-4 text-sm text-warning"
            >
                <div class="flex items-start gap-2">
                    <AlertTriangle :size="16" class="mt-0.5 shrink-0" />
                    <p>{{ t('dashboard.wallets.show.disabled_notice') }}</p>
                </div>
            </section>

            <!-- Combined header card: brand + identity + balance + metadata. -->
            <section class="surface-card overflow-hidden">
                <header class="bg-primary p-5 text-on-primary">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-on-primary/70">
                                Teacha Rewards
                            </p>
                            <h2 class="mt-0.5 truncate text-xl font-semibold">
                                {{ wallet.first_name }}
                            </h2>
                            <p class="mt-0.5 font-mono text-xs tracking-widest text-on-primary/80">
                                {{ wallet.wallet_number }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-on-primary/70">
                                {{ t('dashboard.wallets.show.balance') }}
                            </p>
                            <p class="mt-0.5 text-2xl font-bold tracking-tight">
                                {{ balanceFormatted }}&nbsp;Kč
                            </p>
                        </div>
                    </div>
                </header>

                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 p-5 text-xs sm:grid-cols-4">
                    <div>
                        <dt class="label-eyebrow">
                            {{ t('dashboard.wallets.show.phone') }}
                        </dt>
                        <dd class="mt-1 font-mono text-sm text-on-surface">
                            {{ wallet.phone }}
                        </dd>
                    </div>
                    <div>
                        <dt class="label-eyebrow">
                            {{ t('dashboard.wallets.show.lifetime_earned') }}
                        </dt>
                        <dd class="mt-1 text-sm text-on-surface">
                            {{ lifetimeEarnedFormatted }}&nbsp;Kč
                        </dd>
                    </div>
                    <div>
                        <dt class="label-eyebrow">
                            {{ t('dashboard.wallets.show.lifetime_redeemed') }}
                        </dt>
                        <dd class="mt-1 text-sm text-on-surface">
                            {{ lifetimeRedeemedFormatted }}&nbsp;Kč
                        </dd>
                    </div>
                    <div>
                        <dt class="label-eyebrow">
                            {{ t('dashboard.wallets.show.last_used') }}
                        </dt>
                        <dd class="mt-1 text-sm text-on-surface">
                            {{ wallet.last_used_at ? formatDateTime(wallet.last_used_at) : t('dashboard.wallets.show.never_used') }}
                        </dd>
                    </div>
                </dl>

                <footer class="flex items-center justify-end border-t border-outline-glass px-5 py-3">
                    <Link
                        :href="`/w/${wallet.public_token}`"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-primary transition hover:text-primary-container"
                    >
                        {{ t('dashboard.wallets.show.view_public') }}
                        <ExternalLink :size="12" />
                    </Link>
                </footer>
            </section>

            <section>
                <h2 class="label-eyebrow mb-3">
                    {{ t('dashboard.wallets.show.actions_heading') }}
                </h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <button
                        type="button"
                        :disabled="!isActive"
                        class="flex items-center justify-center gap-2 surface-card px-3 py-3 text-xs font-semibold text-on-surface transition hover:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-primary bg-primary-soft': openAction === 'purchase' }"
                        @click="openPanel('purchase')"
                    >
                        <ShoppingBag :size="16" />
                        {{ t('dashboard.wallets.show.log_purchase') }}
                    </button>
                    <button
                        type="button"
                        :disabled="!isActive"
                        class="flex items-center justify-center gap-2 surface-card px-3 py-3 text-xs font-semibold text-on-surface transition hover:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-primary bg-primary-soft': openAction === 'redeem' }"
                        @click="openPanel('redeem')"
                    >
                        <ArrowDownToLine :size="16" />
                        {{ t('dashboard.wallets.show.redeem') }}
                    </button>
                    <button
                        type="button"
                        :disabled="!isActive"
                        class="flex items-center justify-center gap-2 surface-card px-3 py-3 text-xs font-semibold text-on-surface transition hover:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-primary bg-primary-soft': openAction === 'adjust' }"
                        @click="openPanel('adjust')"
                    >
                        <Sliders :size="16" />
                        {{ t('dashboard.wallets.show.manual_adjust') }}
                    </button>
                    <button
                        type="button"
                        class="flex items-center justify-center gap-2 surface-card px-3 py-3 text-xs font-semibold text-on-surface transition hover:border-warning hover:bg-warning-soft"
                        @click="toggleStatus"
                    >
                        <component
                            :is="isActive ? PowerOff : Power"
                            :size="16"
                        />
                        {{ isActive ? t('dashboard.wallets.show.disable') : t('dashboard.wallets.show.enable') }}
                    </button>
                </div>
            </section>

            <!-- Log purchase panel -->
            <section
                v-if="openAction === 'purchase'"
                class="surface-card border-primary p-5"
            >
                <form
                    class="space-y-4"
                    @submit.prevent="submitPurchase"
                >
                    <div class="space-y-2">
                        <Label for="purchase_amount" required>
                            {{ t('dashboard.forms.purchase_amount') }}
                        </Label>
                        <Input
                            id="purchase_amount"
                            v-model="purchaseForm.purchase_amount"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0.01"
                            :placeholder="t('dashboard.forms.purchase_amount')"
                            :invalid="fieldError(purchaseForm.errors, 'purchase_amount', 'purchase').invalid"
                            :described-by="fieldError(purchaseForm.errors, 'purchase_amount', 'purchase').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('dashboard.forms.purchase_amount_help') }}
                        </p>
                        <FieldError v-bind="fieldError(purchaseForm.errors, 'purchase_amount', 'purchase')" />
                    </div>
                    <Button
                        type="submit"
                        :disabled="purchaseForm.processing"
                    >
                        {{ t('dashboard.forms.submit_purchase') }}
                    </Button>
                </form>
            </section>

            <!-- Redeem panel -->
            <section
                v-if="openAction === 'redeem'"
                class="surface-card border-primary p-5"
            >
                <form
                    class="space-y-4"
                    @submit.prevent="submitRedeem"
                >
                    <div class="space-y-2">
                        <Label for="redeem_amount" required>
                            {{ t('dashboard.forms.redeem_amount') }}
                        </Label>
                        <Input
                            id="redeem_amount"
                            v-model="redeemForm.amount"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0.01"
                            :max="wallet.rewards_balance"
                            :placeholder="t('dashboard.forms.redeem_amount')"
                            :invalid="fieldError(redeemForm.errors, 'amount', 'redeem').invalid"
                            :described-by="fieldError(redeemForm.errors, 'amount', 'redeem').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('dashboard.forms.redeem_amount_help') }}
                        </p>
                        <FieldError v-bind="fieldError(redeemForm.errors, 'amount', 'redeem')" />
                    </div>
                    <Button
                        type="submit"
                        :disabled="redeemForm.processing"
                    >
                        {{ t('dashboard.forms.submit_redeem') }}
                    </Button>
                </form>
            </section>

            <!-- Adjust panel -->
            <section
                v-if="openAction === 'adjust'"
                class="surface-card border-primary p-5"
            >
                <form
                    class="space-y-4"
                    @submit.prevent="submitAdjust"
                >
                    <div class="space-y-2">
                        <Label for="adjust_type" required>
                            {{ t('dashboard.forms.adjust_type') }}
                        </Label>
                        <Select
                            id="adjust_type"
                            v-model="adjustForm.type"
                            :options="[
                                { value: 'add', label: t('dashboard.forms.adjust_type_add') },
                                { value: 'subtract', label: t('dashboard.forms.adjust_type_subtract') },
                                { value: 'set', label: t('dashboard.forms.adjust_type_set') },
                            ]"
                            :invalid="fieldError(adjustForm.errors, 'type', 'adjust').invalid"
                            :described-by="fieldError(adjustForm.errors, 'type', 'adjust').describedBy"
                            required
                        />
                        <FieldError v-bind="fieldError(adjustForm.errors, 'type', 'adjust')" />
                    </div>

                    <div class="space-y-2">
                        <Label for="adjust_amount" required>
                            {{ t('dashboard.forms.adjust_amount') }}
                        </Label>
                        <Input
                            id="adjust_amount"
                            v-model="adjustForm.amount"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0.01"
                            :placeholder="t('dashboard.forms.adjust_amount')"
                            :invalid="fieldError(adjustForm.errors, 'amount', 'adjust').invalid"
                            :described-by="fieldError(adjustForm.errors, 'amount', 'adjust').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('dashboard.forms.adjust_amount_help') }}
                        </p>
                        <FieldError v-bind="fieldError(adjustForm.errors, 'amount', 'adjust')" />
                    </div>

                    <div class="space-y-2">
                        <Label for="adjust_note" required>
                            {{ t('dashboard.forms.adjust_note') }}
                        </Label>
                        <Input
                            id="adjust_note"
                            v-model="adjustForm.note"
                            type="text"
                            :placeholder="t('dashboard.forms.adjust_note')"
                            :invalid="fieldError(adjustForm.errors, 'note', 'adjust').invalid"
                            :described-by="fieldError(adjustForm.errors, 'note', 'adjust').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('dashboard.forms.adjust_note_help') }}
                        </p>
                        <FieldError v-bind="fieldError(adjustForm.errors, 'note', 'adjust')" />
                    </div>

                    <Button
                        type="submit"
                        :disabled="adjustForm.processing"
                    >
                        {{ t('dashboard.forms.submit_adjust') }}
                    </Button>
                </form>
            </section>

            <!-- Recent transactions -->
            <section>
                <h2 class="label-eyebrow mb-3">
                    {{ t('dashboard.dashboard.recent') }}
                </h2>
                <TransactionList
                    :transactions="transactions"
                    show-balance-after
                    :empty-message="t('dashboard.transactions.index.empty')"
                />
            </section>
        </div>
    </AdminLayout>
</template>

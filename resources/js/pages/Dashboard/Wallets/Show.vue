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
import WalletCard from '@/components/reward/WalletCard.vue';
import RewardsBalance from '@/components/reward/RewardsBalance.vue';
import TransactionList from '@/components/reward/TransactionList.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { fieldError } from '@/composables/useFieldError';

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
    purchaseForm.post(`/dashboard/wallets/${props.wallet.id}/purchase`, {
        preserveScroll: true,
        onSuccess: () => {
            purchaseForm.reset();
            openAction.value = null;
        },
    });
}

function submitRedeem(): void {
    redeemForm.post(`/dashboard/wallets/${props.wallet.id}/redeem`, {
        preserveScroll: true,
        onSuccess: () => {
            redeemForm.reset();
            openAction.value = null;
        },
    });
}

function submitAdjust(): void {
    adjustForm.post(`/dashboard/wallets/${props.wallet.id}/adjust`, {
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
        router.post(`/dashboard/wallets/${props.wallet.id}/disable`, {}, { preserveScroll: true });
    } else {
        const ok = await confirmDialog.confirm(t('dashboard.wallets.show.enable_confirm'), {
            confirmLabel: t('dashboard.wallets.show.enable'),
        });
        if (!ok) {
            return;
        }
        router.post(`/dashboard/wallets/${props.wallet.id}/enable`, {}, { preserveScroll: true });
    }
}

function formatDateTime(value: string | null): string {
    if (value === null) {
        return t('dashboard.wallets.show.never_used');
    }
    return new Intl.DateTimeFormat('cs-CZ', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
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

            <WalletCard :wallet="wallet">
                <RewardsBalance :amount="wallet.rewards_balance" />
            </WalletCard>

            <!-- Customer metadata -->
            <section class="surface-card grid grid-cols-2 gap-3 p-4 text-xs sm:grid-cols-3">
                <div>
                    <p class="label-eyebrow">
                        {{ t('dashboard.wallets.show.customer') }}
                    </p>
                    <p class="mt-1 text-sm text-on-surface">
                        {{ wallet.first_name }}
                    </p>
                </div>
                <div>
                    <p class="label-eyebrow">
                        {{ t('dashboard.wallets.show.phone') }}
                    </p>
                    <p class="mt-1 font-mono text-sm text-on-surface">
                        {{ wallet.phone }}
                    </p>
                </div>
                <div>
                    <p class="label-eyebrow">
                        {{ t('dashboard.wallets.show.wallet_number') }}
                    </p>
                    <p class="mt-1 font-mono text-sm text-on-surface">
                        {{ wallet.wallet_number }}
                    </p>
                </div>
                <div>
                    <p class="label-eyebrow">
                        {{ t('dashboard.wallets.show.lifetime_earned') }}
                    </p>
                    <p class="mt-1 text-sm text-on-surface">
                        {{ wallet.lifetime_earned }}&nbsp;Kč
                    </p>
                </div>
                <div>
                    <p class="label-eyebrow">
                        {{ t('dashboard.wallets.show.lifetime_redeemed') }}
                    </p>
                    <p class="mt-1 text-sm text-on-surface">
                        {{ wallet.lifetime_redeemed }}&nbsp;Kč
                    </p>
                </div>
                <div>
                    <p class="label-eyebrow">
                        {{ t('dashboard.wallets.show.last_used') }}
                    </p>
                    <p class="mt-1 text-sm text-on-surface">
                        {{ formatDateTime(wallet.last_used_at) }}
                    </p>
                </div>
            </section>

            <div class="flex justify-end">
                <Link
                    :href="`/w/${wallet.public_token}`"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-primary transition hover:text-primary-container"
                >
                    {{ t('dashboard.wallets.show.view_public') }}
                    <ExternalLink :size="12" />
                </Link>
            </div>

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

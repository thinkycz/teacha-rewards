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
    Plus,
    Minus,
    Gift,
} from '@lucide/vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Select from '@/components/ui/Select.vue';
import FieldError from '@/components/ui/FieldError.vue';
import StampCard from '@/components/reward/StampCard.vue';
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
    stamps_count: number;
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

interface ProgramConfig {
    mode: 'cashback' | 'stamps';
    stamps_per_reward: number;
    stamps_per_reward_label: string;
    stamp_icon: string;
}

const props = defineProps<{
    wallet: WalletSummary;
    transactions: WalletTransaction[];
    program: ProgramConfig;
}>();

const isActive = computed(() => props.wallet.status === 'active');
const isStamps = computed(() => props.program.mode === 'stamps');

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

// Stamps-mode: how many rewards can the cashier redeem right now?
const maxRedeemable = computed(() => {
    const per = Math.max(1, props.program.stamps_per_reward);
    return Math.floor(props.wallet.stamps_count / per);
});

type Action = 'purchase' | 'redeem' | 'adjust' | 'earn' | 'redeemStamps' | null;
const openAction = ref<Action>(null);

function openPanel(action: Action): void {
    openAction.value = openAction.value === action ? null : action;
}

// Cashback forms
const purchaseForm = useForm({ purchase_amount: '' });
const redeemForm = useForm({ amount: '' });
const adjustForm = useForm({
    type: 'add' as 'add' | 'subtract' | 'set',
    amount: '',
    note: '',
});

// Stamps forms
const earnForm = useForm<{ count: string }>({ count: '1' });
const stampRedeemForm = useForm<{ rewards: string }>({ rewards: '1' });

function bumpEarn(delta: number): void {
    const next = Math.max(1, Math.min(100, Number(earnForm.count) + delta));
    earnForm.count = String(next);
}

function bumpRedeem(delta: number): void {
    const next = Math.max(1, Math.min(maxRedeemable.value, Number(stampRedeemForm.rewards) + delta));
    stampRedeemForm.rewards = String(next);
}

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

function submitEarn(): void {
    earnForm.post(`/wallets/${props.wallet.id}/stamps/earn`, {
        preserveScroll: true,
        onSuccess: () => {
            earnForm.count = '1';
            openAction.value = null;
        },
    });
}

function submitStampRedeem(): void {
    stampRedeemForm.post(`/wallets/${props.wallet.id}/stamps/redeem`, {
        preserveScroll: true,
        onSuccess: () => {
            stampRedeemForm.rewards = '1';
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

            <!-- Top card: navy identity header (name + balance)
                 plus customer metadata. The paper loyalty card
                 (next section) is the visual hero in stamps mode. -->
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
                            <p
                                v-if="isStamps"
                                class="mt-0.5 text-2xl font-bold tracking-tight tabular-nums"
                            >
                                {{ wallet.stamps_count }} / {{ program.stamps_per_reward }}
                            </p>
                            <p
                                v-else
                                class="mt-0.5 text-2xl font-bold tracking-tight tabular-nums"
                            >
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

            <!-- Paper loyalty card. Compact admin version: real-paper
                 aesthetic without the brand strip so the admin grid
                 stays dense. Only visible in stamps mode. -->
            <section
                v-if="isStamps"
                class="flex justify-center"
            >
                <StampCard
                    :stamps="wallet.stamps_count"
                    :total="program.stamps_per_reward"
                    :reward-label="program.stamps_per_reward_label"
                    :icon="program.stamp_icon"
                    compact
                />
            </section>

            <section>
                <h2 class="label-eyebrow mb-3">
                    {{ t('dashboard.wallets.show.actions_heading') }}
                </h2>

                <!-- Cashback mode actions -->
                <div
                    v-if="!isStamps"
                    class="grid grid-cols-2 gap-3 sm:grid-cols-4"
                >
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

                <!-- Stamps mode actions -->
                <div
                    v-else
                    class="grid grid-cols-2 gap-3 sm:grid-cols-4"
                >
                    <button
                        type="button"
                        :disabled="!isActive"
                        class="flex items-center justify-center gap-2 surface-card px-3 py-3 text-xs font-semibold text-on-surface transition hover:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-primary bg-primary-soft': openAction === 'earn' }"
                        @click="openPanel('earn')"
                    >
                        <Plus :size="16" />
                        {{ t('dashboard.wallets.show.add_stamps') }}
                    </button>
                    <button
                        type="button"
                        :disabled="!isActive || maxRedeemable < 1"
                        class="flex items-center justify-center gap-2 surface-card px-3 py-3 text-xs font-semibold text-on-surface transition hover:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-primary bg-primary-soft': openAction === 'redeemStamps' }"
                        @click="openPanel('redeemStamps')"
                    >
                        <Gift :size="16" />
                        {{ t('dashboard.wallets.show.redeem_stamps') }}
                        <span
                            v-if="maxRedeemable > 0"
                            class="ml-1 rounded-full bg-primary-soft px-1.5 text-[10px] font-bold text-primary"
                        >
                            {{ maxRedeemable }}
                        </span>
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

            <!-- Cashback: Log purchase -->
            <section
                v-if="!isStamps && openAction === 'purchase'"
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

            <!-- Cashback: Redeem -->
            <section
                v-if="!isStamps && openAction === 'redeem'"
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

            <!-- Stamps: Add stamps -->
            <section
                v-if="isStamps && openAction === 'earn'"
                class="surface-card border-primary p-5"
            >
                <form
                    class="space-y-4"
                    @submit.prevent="submitEarn"
                >
                    <div class="space-y-2">
                        <Label for="earn_count" required>
                            {{ t('dashboard.wallets.show.stamp_count') }}
                        </Label>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-outline-glass bg-surface-container-lowest text-on-surface transition hover:border-primary disabled:opacity-40"
                                :disabled="Number(earnForm.count) <= 1"
                                @click="bumpEarn(-1)"
                            >
                                <Minus :size="14" />
                            </button>
                            <Input
                                id="earn_count"
                                v-model="earnForm.count"
                                type="number"
                                inputmode="numeric"
                                step="1"
                                min="1"
                                max="100"
                                class="text-center"
                                :invalid="fieldError(earnForm.errors, 'count', 'earn').invalid"
                                :described-by="fieldError(earnForm.errors, 'count', 'earn').describedBy"
                                required
                            />
                            <button
                                type="button"
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-outline-glass bg-surface-container-lowest text-on-surface transition hover:border-primary disabled:opacity-40"
                                :disabled="Number(earnForm.count) >= 100"
                                @click="bumpEarn(1)"
                            >
                                <Plus :size="14" />
                            </button>
                        </div>
                        <p class="label-help">
                            {{ t('dashboard.wallets.show.stamp_count_help') }}
                        </p>
                        <FieldError v-bind="fieldError(earnForm.errors, 'count', 'earn')" />
                    </div>
                    <Button
                        type="submit"
                        :disabled="earnForm.processing"
                    >
                        {{ t('dashboard.wallets.show.submit_add_stamps') }}
                    </Button>
                </form>
            </section>

            <!-- Stamps: Redeem free reward -->
            <section
                v-if="isStamps && openAction === 'redeemStamps'"
                class="surface-card border-primary p-5"
            >
                <form
                    class="space-y-4"
                    @submit.prevent="submitStampRedeem"
                >
                    <div class="space-y-2">
                        <Label for="redeem_rewards" required>
                            {{ t('dashboard.wallets.show.rewards_count') }}
                        </Label>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-outline-glass bg-surface-container-lowest text-on-surface transition hover:border-primary disabled:opacity-40"
                                :disabled="Number(stampRedeemForm.rewards) <= 1"
                                @click="bumpRedeem(-1)"
                            >
                                <Minus :size="14" />
                            </button>
                            <Input
                                id="redeem_rewards"
                                v-model="stampRedeemForm.rewards"
                                type="number"
                                inputmode="numeric"
                                step="1"
                                min="1"
                                :max="maxRedeemable"
                                class="text-center"
                                :invalid="fieldError(stampRedeemForm.errors, 'rewards', 'stamps_redeem').invalid"
                                :described-by="fieldError(stampRedeemForm.errors, 'rewards', 'stamps_redeem').describedBy"
                                required
                            />
                            <button
                                type="button"
                                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-outline-glass bg-surface-container-lowest text-on-surface transition hover:border-primary disabled:opacity-40"
                                :disabled="Number(stampRedeemForm.rewards) >= maxRedeemable"
                                @click="bumpRedeem(1)"
                            >
                                <Plus :size="14" />
                            </button>
                        </div>
                        <p class="label-help">
                            {{
                                t('dashboard.wallets.show.rewards_count_help', {
                                    per_reward: program.stamps_per_reward,
                                })
                            }}
                        </p>
                        <FieldError v-bind="fieldError(stampRedeemForm.errors, 'rewards', 'stamps_redeem')" />
                        <button
                            v-if="maxRedeemable > 0"
                            type="button"
                            class="text-xs font-semibold text-primary transition hover:text-primary-container"
                            @click="stampRedeemForm.rewards = String(maxRedeemable)"
                        >
                            {{ t('dashboard.wallets.show.redeem_max', { count: maxRedeemable }) }}
                        </button>
                    </div>
                    <Button
                        type="submit"
                        :disabled="stampRedeemForm.processing"
                    >
                        {{ t('dashboard.wallets.show.submit_redeem_stamps') }}
                    </Button>
                </form>
            </section>

            <!-- Manual adjust (both modes) -->
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
                    :show-balance-after="true"
                    :stamps-mode="isStamps"
                    :empty-message="t('dashboard.transactions.index.empty')"
                />
            </section>
        </div>
    </AdminLayout>
</template>

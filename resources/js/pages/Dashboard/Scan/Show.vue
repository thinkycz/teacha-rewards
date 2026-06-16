<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    ShoppingBag,
    ArrowDownToLine,
    Sliders,
    ExternalLink,
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
import { fieldError } from '@/composables/useFieldError';

useI18n();
const { t } = useI18n();

interface WalletSummary {
    id: number;
    public_token: string;
    wallet_number: string;
    first_name: string;
    rewards_balance: string;
    lifetime_earned: string;
    lifetime_redeemed: string;
    status: 'active' | 'disabled';
}

const props = defineProps<{
    wallet: WalletSummary;
}>();

const isActive = computed(() => props.wallet.status === 'active');

// Action panels (only one open at a time)
type Action = 'purchase' | 'redeem' | 'adjust' | null;
const openAction = ref<Action>(null);

function openPanel(action: Action): void {
    openAction.value = openAction.value === action ? null : action;
}

// Forms
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
</script>

<template>
    <Head :title="t('dashboard.scan.show.title', { name: wallet.first_name })" />

    <AdminLayout :title="t('dashboard.scan.show.title', { name: wallet.first_name })">
        <div class="space-y-6">
            <section
                v-if="!isActive"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
            >
                <div class="flex items-start gap-2">
                    <AlertTriangle :size="16" class="mt-0.5 shrink-0" />
                    <p>{{ t('dashboard.scan.show.disabled_notice') }}</p>
                </div>
            </section>

            <WalletCard :wallet="wallet">
                <RewardsBalance :amount="wallet.rewards_balance" />
            </WalletCard>

            <div class="flex justify-end">
                <Link
                    :href="`/dashboard/wallets/${wallet.id}`"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-matcha-700 hover:text-matcha-800"
                >
                    {{ t('dashboard.scan.show.view_full') }}
                    <ExternalLink :size="12" />
                </Link>
            </div>

            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-charcoal-500">
                    {{ t('dashboard.scan.show.actions_heading') }}
                </h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <button
                        type="button"
                        :disabled="!isActive"
                        class="flex items-center justify-center gap-2 rounded-2xl border border-outline-glass bg-white px-4 py-4 text-sm font-semibold text-charcoal-900 shadow-sm transition hover:border-matcha-300 hover:bg-sage-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-matcha-500 bg-sage-50': openAction === 'purchase' }"
                        @click="openPanel('purchase')"
                    >
                        <ShoppingBag :size="18" />
                        {{ t('dashboard.scan.show.log_purchase') }}
                    </button>
                    <button
                        type="button"
                        :disabled="!isActive"
                        class="flex items-center justify-center gap-2 rounded-2xl border border-outline-glass bg-white px-4 py-4 text-sm font-semibold text-charcoal-900 shadow-sm transition hover:border-matcha-300 hover:bg-sage-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-matcha-500 bg-sage-50': openAction === 'redeem' }"
                        @click="openPanel('redeem')"
                    >
                        <ArrowDownToLine :size="18" />
                        {{ t('dashboard.scan.show.redeem') }}
                    </button>
                    <button
                        type="button"
                        :disabled="!isActive"
                        class="flex items-center justify-center gap-2 rounded-2xl border border-outline-glass bg-white px-4 py-4 text-sm font-semibold text-charcoal-900 shadow-sm transition hover:border-matcha-300 hover:bg-sage-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-matcha-500 bg-sage-50': openAction === 'adjust' }"
                        @click="openPanel('adjust')"
                    >
                        <Sliders :size="18" />
                        {{ t('dashboard.scan.show.manual_adjust') }}
                    </button>
                </div>
            </section>

            <!-- Log purchase panel -->
            <section
                v-if="openAction === 'purchase'"
                class="rounded-2xl border border-matcha-300 bg-white p-5 shadow-sm"
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
                        <p class="text-[10px] text-charcoal-500">
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
                class="rounded-2xl border border-matcha-300 bg-white p-5 shadow-sm"
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
                        <p class="text-[10px] text-charcoal-500">
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
                class="rounded-2xl border border-matcha-300 bg-white p-5 shadow-sm"
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
                        <p class="text-[10px] text-charcoal-500">
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
                        <p class="text-[10px] text-charcoal-500">
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
        </div>
    </AdminLayout>
</template>

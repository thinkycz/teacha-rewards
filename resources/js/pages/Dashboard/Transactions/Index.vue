<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Search, X, ChevronRight } from '@lucide/vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Button from '@/components/ui/Button.vue';

const { t } = useI18n();

interface Transaction {
    id: number;
    type: string;
    amount: string;
    purchase_amount: string | null;
    cashback_rate: string | null;
    balance_before: string;
    balance_after: string;
    note: string | null;
    wallet_id: number;
    wallet_first_name: string | null;
    wallet_number: string | null;
    wallet_public_token: string | null;
    staff_name: string | null;
    created_at: string | null;
}

interface Filters {
    q: string;
    type: string;
    wallet_id: number;
}

const props = defineProps<{
    transactions: Transaction[];
    filters: Filters;
    type_options: string[];
}>();

const search = ref(props.filters.q);
const type = ref(props.filters.type);

function applyFilters(): void {
    const params: Record<string, string> = {};
    if (search.value.trim() !== '') {
        params['q'] = search.value.trim();
    }
    if (type.value !== '') {
        params['type'] = type.value;
    }
    router.visit('/dashboard/transactions', {
        method: 'get',
        data: params,
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters(): void {
    search.value = '';
    type.value = '';
    applyFilters();
}

const hasActiveFilters = computed(() => search.value.trim() !== '' || type.value !== '');

function typeLabel(value: string): string {
    switch (value) {
        case 'purchase_cashback':
            return t('dashboard.transactions.index.type_purchase_cashback');
        case 'redeem':
            return t('dashboard.transactions.index.type_redeem');
        case 'manual_add':
            return t('dashboard.transactions.index.type_manual_add');
        case 'manual_subtract':
            return t('dashboard.transactions.index.type_manual_subtract');
        case 'manual_set':
            return t('dashboard.transactions.index.type_manual_set');
        default:
            return value;
    }
}

function formatAmount(value: string): string {
    return new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Math.abs(Number(value)));
}

function signedAmount(value: string): string {
    const num = Number(value);
    const sign = num >= 0 ? '+' : '−';
    return sign + formatAmount(value);
}

function formatDateTime(value: string | null): string {
    if (value === null) {
        return '';
    }
    return new Intl.DateTimeFormat('cs-CZ', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
}

const typeOptions = computed(() => [
    { value: '', label: t('dashboard.transactions.index.type_all') },
    ...props.type_options.map((value) => ({ value, label: typeLabel(value) })),
]);
</script>

<template>
    <Head :title="t('dashboard.transactions.index.title')" />

    <AdminLayout :title="t('dashboard.transactions.index.title')">
        <div class="space-y-5">
            <header>
                <h1 class="heading-2">
                    {{ t('dashboard.transactions.index.heading') }}
                </h1>
            </header>

            <!-- Filters -->
            <section class="surface-card p-4">
                <form
                    class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto]"
                    @submit.prevent="applyFilters"
                >
                    <div class="relative">
                        <Search
                            :size="14"
                            class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-on-surface-variant"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            name="q"
                            :placeholder="t('dashboard.transactions.index.search_placeholder')"
                            class="pl-9"
                        />
                    </div>
                    <Select
                        v-model="type"
                        name="type"
                        :options="typeOptions"
                    />
                    <Button
                        type="submit"
                        class="justify-center"
                    >
                        {{ t('dashboard.wallets.index.search_placeholder').split(' ')[0] }}
                    </Button>
                </form>
                <div
                    v-if="hasActiveFilters"
                    class="mt-3 flex justify-end"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-on-surface-variant transition hover:text-on-surface"
                        @click="clearFilters"
                    >
                        <X :size="12" />
                        {{ t('common.cancel') }}
                    </button>
                </div>
            </section>

            <!-- Results -->
            <section v-if="transactions.length === 0">
                <div class="surface-card p-6 text-center text-sm text-on-surface-variant">
                    {{ t('dashboard.transactions.index.empty') }}
                </div>
            </section>

            <ul v-else class="space-y-2">
                <li
                    v-for="tx in transactions"
                    :key="tx.id"
                >
                    <component
                        :is="tx.wallet_id ? Link : 'div'"
                        :href="tx.wallet_id ? `/dashboard/wallets/${tx.wallet_id}` : undefined"
                        class="group block surface-card p-4 transition hover:border-primary"
                        :class="{ 'cursor-pointer': !!tx.wallet_id }"
                    >
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-primary-soft px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-primary">
                                        {{ typeLabel(tx.type) }}
                                    </span>
                                    <span class="text-[10px] text-on-surface-variant">
                                        {{ formatDateTime(tx.created_at) }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-sm font-semibold text-on-surface">
                                    {{ tx.wallet_first_name ?? '—' }}
                                    <span
                                        v-if="tx.wallet_number"
                                        class="ml-1 font-mono text-[10px] text-on-surface-variant"
                                    >
                                        {{ tx.wallet_number }}
                                    </span>
                                </p>
                                <p
                                    v-if="tx.purchase_amount"
                                    class="text-[10px] text-on-surface-variant"
                                >
                                    {{ t('dashboard.transactions.index.purchase_amount') }}: {{ tx.purchase_amount }} Kč
                                </p>
                                <p
                                    v-if="tx.note"
                                    class="text-[10px] text-on-surface-variant"
                                >
                                    {{ t('dashboard.transactions.index.note') }}: {{ tx.note }}
                                </p>
                                <p
                                    v-if="tx.staff_name"
                                    class="text-[10px] text-on-surface-variant"
                                >
                                    {{ tx.staff_name }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-sm font-semibold"
                                    :class="Number(tx.amount) >= 0 ? 'text-success' : 'text-error-red'"
                                >
                                    {{ signedAmount(tx.amount) }}&nbsp;Kč
                                </p>
                                <p class="label-eyebrow">
                                    {{ t('dashboard.transactions.index.balance_after') }}
                                </p>
                                <p class="text-xs text-on-surface">
                                    {{ tx.balance_after }}&nbsp;Kč
                                </p>
                            </div>
                            <ChevronRight
                                v-if="tx.wallet_id"
                                :size="16"
                                class="shrink-0 self-center text-on-surface-variant transition group-hover:text-primary"
                            />
                        </div>
                    </component>
                </li>
            </ul>
        </div>
    </AdminLayout>
</template>

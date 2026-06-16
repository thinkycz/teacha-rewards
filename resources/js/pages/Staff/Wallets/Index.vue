<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Search, ChevronRight, X } from '@lucide/vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Button from '@/components/ui/Button.vue';

useI18n();
const { t } = useI18n();

interface Wallet {
    id: number;
    public_token: string;
    wallet_number: string;
    first_name: string;
    phone: string;
    rewards_balance: string;
    lifetime_earned: string;
    lifetime_redeemed: string;
    status: 'active' | 'disabled';
    last_used_at: string | null;
}

interface Filters {
    q: string;
    status: 'all' | 'active' | 'disabled';
    sort: '' | 'earned' | 'redeemed' | 'balance';
}

const props = defineProps<{
    wallets: Wallet[];
    filters: Filters;
}>();

// Local form state, pre-filled from server.
const search = ref(props.filters.q);
const status = ref<Filters['status']>(props.filters.status);
const sort = ref<Filters['sort']>(props.filters.sort);

function applyFilters(): void {
    const params: Record<string, string> = {};
    if (search.value.trim() !== '') {
        params['q'] = search.value.trim();
    }
    if (status.value !== 'all') {
        params['status'] = status.value;
    }
    if (sort.value !== '') {
        params['sort'] = sort.value;
    }
    router.visit('/staff/wallets', {
        method: 'get',
        data: params,
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters(): void {
    search.value = '';
    status.value = 'all';
    sort.value = '';
    applyFilters();
}

const hasActiveFilters = computed(
    () => search.value.trim() !== '' || status.value !== 'all' || sort.value !== '',
);

function formatAmount(value: string): string {
    return new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value));
}
</script>

<template>
    <Head :title="t('staff.wallets.index.title')" />

    <StaffLayout :title="t('staff.wallets.index.title')">
        <div class="space-y-5">
            <header>
                <h1 class="text-2xl font-semibold text-charcoal-900">
                    {{ t('staff.wallets.index.heading') }}
                </h1>
            </header>

            <!-- Filters -->
            <section
                class="rounded-2xl border border-outline-glass bg-white p-4 shadow-sm"
            >
                <form
                    class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto_auto]"
                    @submit.prevent="applyFilters"
                >
                    <div class="relative">
                        <Search
                            :size="14"
                            class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-charcoal-400"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            name="q"
                            :placeholder="t('staff.wallets.index.search_placeholder')"
                            class="pl-8"
                        />
                    </div>
                    <Select
                        v-model="status"
                        name="status"
                        :options="[
                            { value: 'all', label: t('staff.wallets.index.status_all') },
                            { value: 'active', label: t('staff.wallets.index.status_active') },
                            { value: 'disabled', label: t('staff.wallets.index.status_disabled') },
                        ]"
                    />
                    <Select
                        v-model="sort"
                        name="sort"
                        :options="[
                            { value: '', label: t('staff.wallets.index.sort_recent') },
                            { value: 'earned', label: t('staff.wallets.index.sort_earned') },
                            { value: 'redeemed', label: t('staff.wallets.index.sort_redeemed') },
                            { value: 'balance', label: t('staff.wallets.index.sort_balance') },
                        ]"
                    />
                    <Button
                        type="submit"
                        class="justify-center"
                    >
                        {{ t('staff.wallets.index.search_placeholder').split(' ')[0] }}
                    </Button>
                </form>
                <div
                    v-if="hasActiveFilters"
                    class="mt-2 flex justify-end"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-charcoal-500 hover:text-charcoal-700"
                        @click="clearFilters"
                    >
                        <X :size="12" />
                        {{ t('common.cancel') }}
                    </button>
                </div>
            </section>

            <!-- Results -->
            <section v-if="wallets.length === 0">
                <div
                    class="rounded-2xl border border-outline-glass bg-white p-6 text-center text-sm text-charcoal-500 shadow-sm"
                >
                    {{ t('staff.wallets.index.empty') }}
                </div>
            </section>

            <ul v-else class="space-y-2">
                <li
                    v-for="wallet in wallets"
                    :key="wallet.id"
                >
                    <Link
                        :href="`/staff/wallets/${wallet.id}`"
                        class="flex items-center gap-3 rounded-2xl border border-outline-glass bg-white p-4 shadow-sm transition hover:border-matcha-300 hover:bg-sage-50"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-matcha-500 to-matcha-700 text-sm font-bold text-white"
                        >
                            {{ wallet.first_name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-charcoal-900">
                                    {{ wallet.first_name }}
                                </p>
                                <span
                                    v-if="wallet.status === 'disabled'"
                                    class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-700"
                                >
                                    {{ t('staff.status.disabled') }}
                                </span>
                            </div>
                            <p class="truncate font-mono text-xs text-charcoal-500">
                                {{ wallet.wallet_number }} · {{ wallet.phone }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-charcoal-900">
                                {{ formatAmount(wallet.rewards_balance) }}&nbsp;Kč
                            </p>
                            <p class="text-[10px] uppercase tracking-wider text-charcoal-500">
                                {{ t('staff.wallets.index.balance') }}
                            </p>
                        </div>
                        <ChevronRight
                            :size="16"
                            class="shrink-0 text-charcoal-400"
                        />
                    </Link>
                </li>
            </ul>
        </div>
    </StaffLayout>
</template>

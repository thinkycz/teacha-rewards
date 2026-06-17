<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Search, ChevronRight, X } from '@lucide/vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Button from '@/components/ui/Button.vue';

useI18n();
const { t } = useI18n();

interface Wallet {
    id: number;
    public_token: string;
    wallet_number: string;
    type: 'cashback' | 'stamps';
    first_name: string;
    phone: string;
    rewards_balance: string;
    stamps_count: number;
    lifetime_earned: string;
    lifetime_redeemed: string;
    status: 'active' | 'disabled';
    last_used_at: string | null;
}

interface ProgramConfig {
    stamps_per_reward: number;
    stamps_per_reward_label: string;
    stamp_icon: string;
}

interface Filters {
    q: string;
    status: 'all' | 'active' | 'disabled';
    sort: '' | 'earned' | 'redeemed' | 'balance';
}

const props = defineProps<{
    wallets: Wallet[];
    filters: Filters;
    program: ProgramConfig;
}>();

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
    router.visit('/wallets', {
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
    <Head :title="t('dashboard.wallets.index.title')" />

    <AdminLayout :title="t('dashboard.wallets.index.title')">
        <div class="space-y-5">
            <header>
                <h1 class="heading-2">
                    {{ t('dashboard.wallets.index.heading') }}
                </h1>
            </header>

            <section class="surface-card p-4">
                <form
                    class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto_auto_auto]"
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
                            :placeholder="t('dashboard.wallets.index.search_placeholder')"
                            class="pl-9"
                        />
                    </div>
                    <Select
                        v-model="status"
                        name="status"
                        :options="[
                            { value: 'all', label: t('dashboard.wallets.index.status_all') },
                            { value: 'active', label: t('dashboard.wallets.index.status_active') },
                            { value: 'disabled', label: t('dashboard.wallets.index.status_disabled') },
                        ]"
                    />
                    <Select
                        v-model="sort"
                        name="sort"
                        :options="[
                            { value: '', label: t('dashboard.wallets.index.sort_recent') },
                            { value: 'earned', label: t('dashboard.wallets.index.sort_earned') },
                            { value: 'redeemed', label: t('dashboard.wallets.index.sort_redeemed') },
                            { value: 'balance', label: t('dashboard.wallets.index.sort_balance') },
                        ]"
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

            <section v-if="wallets.length === 0">
                <div class="surface-card p-6 text-center text-sm text-on-surface-variant">
                    {{ t('dashboard.wallets.index.empty') }}
                </div>
            </section>

            <ul v-else class="space-y-2">
                <li
                    v-for="wallet in wallets"
                    :key="wallet.id"
                >
                    <Link
                        :href="`/wallets/${wallet.id}`"
                        class="group flex items-center gap-3 surface-card p-4 transition hover:border-primary"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary"
                        >
                            {{ wallet.first_name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-on-surface">
                                    {{ wallet.first_name }}
                                </p>
                                <!-- Type badge: distinct color + icon per type so
                                     the cashier can scan a mixed list at a glance.
                                     Cashback = amber/gold (money metaphor),
                                     Stamps = teal (collection metaphor). -->
                                <span
                                    :class="wallet.type === 'stamps'
                                        ? 'bg-teal-50 text-teal-700 ring-1 ring-teal-200'
                                        : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'"
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                >
                                    <span aria-hidden="true">{{ wallet.type === 'stamps' ? '\u{1F3F7}\u{FE0F}' : '\u{1F4B0}' }}</span>
                                    {{ t('dashboard.wallets.index.type_' + wallet.type) }}
                                </span>
                                <span
                                    v-if="wallet.status === 'disabled'"
                                    class="rounded-full bg-warning-soft px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-warning"
                                >
                                    {{ t('dashboard.status.disabled') }}
                                </span>
                            </div>
                            <p class="truncate font-mono text-xs text-on-surface-variant">
                                {{ wallet.wallet_number }} · {{ wallet.phone }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p
                                v-if="wallet.type === 'stamps'"
                                class="text-sm font-bold text-on-surface tabular-nums"
                            >
                                {{ wallet.stamps_count }} / {{ program.stamps_per_reward }}
                            </p>
                            <p
                                v-else
                                class="text-sm font-bold text-on-surface tabular-nums"
                                v-html="formatAmount(wallet.rewards_balance) + '&nbsp;Kč'"
                            />
                            <p class="label-eyebrow">
                                {{ wallet.type === 'stamps' ? t('dashboard.wallets.index.balance_stamps') : t('dashboard.wallets.index.balance') }}
                            </p>
                        </div>
                        <ChevronRight
                            :size="16"
                            class="shrink-0 text-on-surface-variant transition group-hover:text-primary"
                        />
                    </Link>
                </li>
            </ul>
        </div>
    </AdminLayout>
</template>

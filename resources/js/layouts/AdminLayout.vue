<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    QrCode,
    Wallet as WalletIcon,
    Receipt,
    Settings as SettingsIcon,
    LogOut,
    ExternalLink,
} from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FlashAlerts from '@/components/ui/FlashAlerts.vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import { useSharedProps } from '@/composables/useSharedProps';

defineProps<{
    title: string;
}>();

const { auth, activeUrl } = useSharedProps();
const { t } = useI18n();

useBoundLocale();

interface NavItem {
    href: string;
    labelKey: string;
    match: RegExp;
    icon: typeof LayoutDashboard;
    requireAdmin?: boolean;
}

const navItems = computed<NavItem[]>(() => [
    {
        href: '/dashboard',
        labelKey: 'staff.nav.dashboard',
        match: /^\/dashboard\/?$/,
        icon: LayoutDashboard,
    },
    {
        href: '/dashboard/scan',
        labelKey: 'staff.nav.scan',
        match: /^\/dashboard\/scan/,
        icon: QrCode,
    },
    {
        href: '/dashboard/wallets',
        labelKey: 'staff.nav.wallets',
        match: /^\/dashboard\/wallets/,
        icon: WalletIcon,
    },
    {
        href: '/dashboard/transactions',
        labelKey: 'staff.nav.transactions',
        match: /^\/dashboard\/transactions/,
        icon: Receipt,
    },
    {
        href: '/dashboard/settings',
        labelKey: 'staff.nav.settings',
        match: /^\/dashboard\/settings/,
        icon: SettingsIcon,
        requireAdmin: true,
    },
]);

const visibleNav = computed(() =>
    navItems.value.filter((item) => {
        if (item.requireAdmin === true) {
            return auth.value.user?.role === 'admin';
        }
        return true;
    }),
);

const currentPath = computed(() => activeUrl.value);

function isActive(item: NavItem): boolean {
    return item.match.test(currentPath.value);
}

function logout(): void {
    router.post('/logout');
}

const roleLabel = computed(() => {
    const role = auth.value.user?.role;
    if (role === 'admin') {
        return t('staff.layout.role_admin');
    }
    if (role === 'staff') {
        return t('staff.layout.role_staff');
    }
    return '';
});
</script>

<template>
    <Head :title="title" />

    <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-primary focus:px-4 focus:py-2 focus:text-xs focus:font-bold focus:text-white"
    >
        {{ t('nav.skip_to_main') }}
    </a>

    <div class="flex min-h-screen bg-surface-bg font-sans antialiased">
        <!-- Desktop sidebar -->
        <aside
            class="sticky top-0 z-20 hidden h-screen w-64 shrink-0 flex-col border-r border-outline-glass bg-white lg:flex"
        >
            <div class="flex items-center gap-2 border-b border-outline-glass px-5 py-4">
                <Link
                    href="/"
                    class="flex items-center gap-2 text-charcoal-700 transition hover:text-charcoal-900"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-matcha-500 to-matcha-700 text-xs font-bold text-white"
                    >
                        T
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-sm font-semibold">
                            {{ t('staff.layout.app_name') }}
                        </span>
                        <span
                            v-if="roleLabel"
                            class="text-[10px] font-semibold uppercase tracking-wider text-charcoal-500"
                        >
                            {{ roleLabel }}
                        </span>
                    </div>
                </Link>
            </div>

            <nav class="flex-1 overflow-y-auto p-3">
                <ul class="space-y-1">
                    <li
                        v-for="item in visibleNav"
                        :key="item.href"
                    >
                        <Link
                            :href="item.href"
                            :class="[
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                isActive(item)
                                    ? 'bg-matcha-50 text-matcha-800'
                                    : 'text-charcoal-700 hover:bg-sage-50 hover:text-charcoal-900',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :size="18"
                                :stroke-width="isActive(item) ? 2.5 : 2"
                            />
                            <span>{{ t(item.labelKey) }}</span>
                        </Link>
                    </li>
                </ul>
            </nav>

            <div class="border-t border-outline-glass p-3">
                <Link
                    href="/"
                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-charcoal-700 transition hover:bg-sage-50 hover:text-charcoal-900"
                >
                    <ExternalLink :size="16" />
                    <span>{{ t('marketing.tagline') }}</span>
                </Link>
                <button
                    type="button"
                    class="mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-charcoal-700 transition hover:bg-sage-50 hover:text-charcoal-900"
                    @click="logout"
                >
                    <LogOut :size="16" />
                    <span>{{ t('staff.nav.logout') }}</span>
                </button>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <!-- Top bar (mobile + desktop right rail) -->
            <header
                class="sticky top-0 z-20 flex items-center justify-between border-b border-outline-glass bg-white px-4 py-3 shadow-sm lg:px-8"
            >
                <div class="flex items-center gap-2 lg:hidden">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-matcha-500 to-matcha-700 text-xs font-bold text-white"
                    >
                        T
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-sm font-semibold text-charcoal-900">
                            {{ t('staff.layout.app_name') }}
                        </span>
                        <span
                            v-if="roleLabel"
                            class="text-[10px] font-semibold uppercase tracking-wider text-charcoal-500"
                        >
                            {{ roleLabel }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3 lg:ml-auto">
                    <span
                        v-if="auth.user?.name"
                        class="hidden text-sm text-charcoal-700 sm:inline"
                    >
                        {{ auth.user.name }}
                    </span>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-outline-glass bg-white px-3 text-xs font-semibold text-charcoal-700 transition hover:bg-sage-50"
                        @click="logout"
                    >
                        <LogOut :size="14" />
                        <span class="hidden sm:inline">
                            {{ t('staff.nav.logout') }}
                        </span>
                    </button>
                </div>
            </header>

            <main
                id="main-content"
                class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-10 lg:py-10"
            >
                <FlashAlerts />
                <slot />
            </main>

            <!-- Mobile bottom tab bar (lg:hidden shows it) -->
            <nav
                class="sticky bottom-0 z-20 border-t border-outline-glass bg-white shadow-[0_-2px_8px_rgba(15,23,42,0.04)] lg:hidden"
            >
                <ul class="grid grid-flow-col auto-cols-fr">
                    <li
                        v-for="item in visibleNav"
                        :key="item.href"
                        class="flex"
                    >
                        <Link
                            :href="item.href"
                            :class="[
                                'flex flex-1 flex-col items-center gap-0.5 py-2 text-[10px] font-semibold uppercase tracking-wide transition',
                                isActive(item)
                                    ? 'text-matcha-700'
                                    : 'text-charcoal-500 hover:text-charcoal-700',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :size="20"
                                :stroke-width="isActive(item) ? 2.5 : 2"
                            />
                            <span>{{ t(item.labelKey) }}</span>
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>

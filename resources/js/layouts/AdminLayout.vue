<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    QrCode,
    Wallet as WalletIcon,
    Receipt,
    Settings as SettingsIcon,
    LogOut,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import Brand from '@/components/ui/Brand.vue';
import FlashAlerts from '@/components/ui/FlashAlerts.vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import { useSharedProps } from '@/composables/useSharedProps';

defineProps<{
    title: string;
}>();

const { auth, activeUrl } = useSharedProps();
const { t } = useI18n();
useBoundLocale();
const mobileHistoryOpen = ref(false);

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
        labelKey: 'dashboard.nav.dashboard',
        match: /^\/dashboard\/?$/,
        icon: LayoutDashboard,
    },
    {
        href: '/dashboard/scan',
        labelKey: 'dashboard.nav.scan',
        match: /^\/dashboard\/scan/,
        icon: QrCode,
    },
    {
        href: '/dashboard/wallets',
        labelKey: 'dashboard.nav.wallets',
        match: /^\/dashboard\/wallets/,
        icon: WalletIcon,
    },
    {
        href: '/dashboard/transactions',
        labelKey: 'dashboard.nav.transactions',
        match: /^\/dashboard\/transactions/,
        icon: Receipt,
    },
    {
        href: '/dashboard/settings',
        labelKey: 'dashboard.nav.settings',
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

const userInitials = computed(() => {
    const email = auth.value.user?.email ?? '';
    if (email === '') {
        return '?';
    }
    return email.substring(0, 2).toUpperCase();
});

const userLabel = computed(() => {
    const user = auth.value.user;
    if (user === null) {
        return '';
    }
    if (user.name !== null && user.name !== '') {
        return user.name;
    }
    return user.email.split('@')[0] ?? '';
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

    <div
        class="flex h-screen flex-col overflow-hidden bg-surface-bg font-sans antialiased md:flex-row"
    >
        <!-- Desktop Persistent Sidebar -->
        <aside
            class="sticky top-0 z-20 hidden h-screen w-64 flex-col border-r border-outline-glass bg-surface-container px-4 py-6 text-left md:flex"
        >
            <div class="mb-8 px-2">
                <Brand href="/dashboard" />
            </div>

            <nav class="flex-1 space-y-1.5 overflow-y-auto">
                <Link
                    v-for="item in visibleNav"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-all',
                        isActive(item)
                            ? 'border-r-2 border-primary bg-surface-container-low font-bold text-primary shadow-[inset_0_1px_0_rgba(255,255,255,0.3)]'
                            : 'text-on-surface-variant hover:bg-surface-container-low',
                    ]"
                >
                    <component :is="item.icon" :size="16" />
                    {{ t(item.labelKey) }}
                </Link>
            </nav>

            <!-- Footer: User Identity -->
            <div
                class="flex items-center justify-between gap-2 border-t border-outline-glass pt-4 px-2"
            >
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <div
                        aria-hidden="true"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-outline-glass bg-surface-container-low font-heading text-xs font-bold text-primary"
                    >
                        {{ userInitials }}
                    </div>
                    <div class="min-w-0 overflow-hidden">
                        <p
                            class="truncate text-xs font-semibold text-on-surface"
                        >
                            {{ userLabel }}
                        </p>
                        <p
                            class="truncate text-[9px] text-on-surface-variant opacity-85 font-medium"
                        >
                            {{ auth.user ? auth.user.email : '' }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1">
                    <button
                        @click="logout"
                        class="cursor-pointer rounded-lg p-1.5 text-on-surface-variant transition-all hover:bg-error-red/10 hover:text-error-red"
                        :title="t('dashboard.nav.logout')"
                        :aria-label="t('dashboard.nav.logout')"
                    >
                        <LogOut :size="14" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile Top Navigation Header -->
        <header
            class="glass-panel sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-outline-glass px-4 shadow-sm md:hidden"
        >
            <div class="flex items-center gap-2">
                <Brand href="/dashboard" />
            </div>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="rounded-lg p-2 text-on-surface-variant transition-all"
                    :class="
                        mobileHistoryOpen
                            ? 'bg-surface-container-low text-primary'
                            : ''
                    "
                    :title="t('dashboard.nav.scan')"
                    :aria-label="t('dashboard.nav.scan')"
                    :aria-expanded="mobileHistoryOpen"
                    @click="mobileHistoryOpen = !mobileHistoryOpen"
                >
                    <QrCode :size="16" />
                </button>
                <Link
                    href="/dashboard"
                    :class="[
                        'rounded-lg p-2 transition-all',
                        isActive(navItems[0]!)
                            ? 'font-bold text-primary bg-surface-container-low'
                            : 'text-on-surface-variant',
                    ]"
                    @click="mobileHistoryOpen = false"
                >
                    <LayoutDashboard :size="16" />
                </Link>
                <Link
                    href="/dashboard/wallets"
                    :class="[
                        'rounded-lg p-2 transition-all',
                        activeUrl.startsWith('/dashboard/wallets')
                            ? 'font-bold text-primary bg-surface-container-low'
                            : 'text-on-surface-variant',
                    ]"
                    @click="mobileHistoryOpen = false"
                >
                    <WalletIcon :size="16" />
                </Link>
                <Link
                    href="/dashboard/transactions"
                    :class="[
                        'rounded-lg p-2 transition-all',
                        activeUrl.startsWith('/dashboard/transactions')
                            ? 'font-bold text-primary bg-surface-container-low'
                            : 'text-on-surface-variant',
                    ]"
                    @click="mobileHistoryOpen = false"
                >
                    <Receipt :size="16" />
                </Link>
                <Link
                    v-if="auth.user?.role === 'admin'"
                    href="/dashboard/settings"
                    :class="[
                        'rounded-lg p-2 transition-all',
                        activeUrl.startsWith('/dashboard/settings')
                            ? 'font-bold text-primary bg-surface-container-low'
                            : 'text-on-surface-variant',
                    ]"
                    @click="mobileHistoryOpen = false"
                >
                    <SettingsIcon :size="16" />
                </Link>
                <button
                    @click="logout"
                    class="rounded-lg p-2 text-on-surface-variant transition-all hover:text-error-red"
                >
                    <LogOut :size="16" />
                </button>
            </div>
        </header>

        <div
            v-if="mobileHistoryOpen"
            class="glass-panel z-20 max-h-64 overflow-y-auto border-b border-outline-glass px-4 py-3 md:hidden"
        >
            <div class="space-y-1">
                <Link
                    v-for="item in visibleNav"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-on-surface-variant hover:bg-surface-container-low"
                    @click="mobileHistoryOpen = false"
                >
                    <component :is="item.icon" :size="14" />
                    {{ t(item.labelKey) }}
                </Link>
            </div>
        </div>

        <main
            id="main-content"
            class="flex-1 overflow-y-auto bg-surface-bg px-4 py-6 sm:px-6 sm:py-8 md:px-8 md:py-10"
        >
            <FlashAlerts />
            <slot />
        </main>
    </div>
</template>

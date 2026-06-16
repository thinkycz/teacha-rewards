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
        href: '/staff',
        labelKey: 'staff.nav.dashboard',
        match: /^\/staff\/?$/,
        icon: LayoutDashboard,
    },
    {
        href: '/staff/scan',
        labelKey: 'staff.nav.scan',
        match: /^\/staff\/scan/,
        icon: QrCode,
    },
    {
        href: '/staff/wallets',
        labelKey: 'staff.nav.wallets',
        match: /^\/staff\/wallets/,
        icon: WalletIcon,
    },
    {
        href: '/staff/transactions',
        labelKey: 'staff.nav.transactions',
        match: /^\/staff\/transactions/,
        icon: Receipt,
    },
    {
        href: '/staff/settings',
        labelKey: 'staff.nav.settings',
        match: /^\/staff\/settings/,
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

    <div class="flex min-h-screen flex-col bg-surface-bg font-sans antialiased">
        <!-- Top app bar -->
        <header
            class="sticky top-0 z-20 flex items-center justify-between border-b border-outline-glass bg-white px-4 py-3 shadow-sm"
        >
            <div class="flex items-center gap-2">
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

            <div class="flex items-center gap-2">
                <span
                    v-if="auth.user?.name"
                    class="hidden text-xs text-charcoal-600 sm:inline"
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

        <!-- Main content -->
        <main
            id="main-content"
            class="mx-auto w-full max-w-3xl flex-1 px-4 py-6 sm:px-6 sm:py-8"
        >
            <FlashAlerts />
            <slot />
        </main>

        <!-- Bottom tab bar (mobile-first) -->
        <nav
            class="sticky bottom-0 z-20 border-t border-outline-glass bg-white shadow-[0_-2px_8px_rgba(15,23,42,0.04)]"
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
</template>

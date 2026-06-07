<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import Brand from '@/components/ui/Brand.vue';
import Button from '@/components/ui/Button.vue';
import FlashAlerts from '@/components/ui/FlashAlerts.vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import { useSharedProps } from '@/composables/useSharedProps';

defineProps<{
    title: string;
}>();

const { auth } = useSharedProps();
const { t } = useI18n();

useBoundLocale();

const currentPath = computed<string>(() => usePage().url);

function isActive(href: string): boolean {
    return currentPath.value.startsWith(href);
}

function logout(): void {
    router.post('/logout');
}
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-gray-50">
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:rounded-md focus:bg-blue-700 focus:px-3 focus:py-2 focus:text-sm focus:text-white"
        >
            {{ t('nav.skip_to_main') }}
        </a>

        <header class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4"
            >
                <Brand
                    href="/dashboard"
                    class="text-base font-semibold text-gray-950"
                />

                <nav
                    class="flex items-center gap-2"
                    :aria-label="t('nav.primary')"
                >
                    <Link
                        href="/dashboard"
                        :aria-current="
                            isActive('/dashboard') ? 'page' : undefined
                        "
                        class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        {{ t('nav.dashboard') }}
                    </Link>
                    <Link
                        href="/settings/profile"
                        :aria-current="
                            isActive('/settings') ? 'page' : undefined
                        "
                        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        <UserRound class="size-4" />
                        {{ t('nav.profile') }}
                    </Link>
                    <LocaleSwitcher v-if="auth.user" />
                    <Button variant="ghost" class="gap-2" @click="logout">
                        <LogOut class="size-4" />
                        {{ t('nav.logout') }}
                    </Button>
                </nav>
            </div>
        </header>

        <main id="main" class="mx-auto max-w-6xl px-4 py-8" tabindex="-1">
            <div class="mb-6 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-950">
                        {{ title }}
                    </h1>
                    <p v-if="auth.user" class="mt-1 text-sm text-gray-600">
                        {{ auth.user.email }}
                    </p>
                </div>
            </div>

            <FlashAlerts />

            <slot />
        </main>
    </div>
</template>

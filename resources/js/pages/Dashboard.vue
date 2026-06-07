<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ShieldCheck, KeyRound, UserRound, ArrowRight } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import { useSharedProps } from '@/composables/useSharedProps';

const { auth } = useSharedProps();
const { t } = useI18n();

useBoundLocale();
</script>

<template>
    <AppLayout :title="t('dashboard.title')">
        <!-- Dashboard Top Ambient Indicator -->
        <div class="mb-8 flex flex-col items-start gap-4">
            <div
                class="inline-flex items-center gap-2 rounded-full border border-outline-glass bg-surface-container px-3 py-1 font-mono text-[10px] font-bold tracking-wider text-primary"
            >
                <span
                    class="h-2 w-2 animate-pulse rounded-full bg-primary"
                ></span>
                {{ t('dashboard.welcome') }}
            </div>

            <h1
                class="font-heading text-3xl font-black tracking-tight text-on-surface md:text-4xl"
            >
                Laravel Inertia
                <span
                    class="bg-gradient-to-r from-primary to-secondary-cyan bg-clip-text text-transparent"
                    >Workspace.</span
                >
            </h1>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <section
                class="rounded-2xl border border-outline-glass bg-surface-container-lowest p-6 shadow-sm flex flex-col justify-between"
            >
                <div>
                    <div
                        class="mb-4 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"
                    >
                        <UserRound :size="18" />
                    </div>
                    <h3 class="font-heading text-sm font-bold text-on-surface">
                        {{ t('dashboard.welcome') }}
                    </h3>
                    <p
                        class="mt-2 text-xs leading-normal text-on-surface-variant font-medium"
                    >
                        {{ auth.user?.email }}
                    </p>
                </div>
            </section>

            <section
                class="rounded-2xl border border-outline-glass bg-surface-container-lowest p-6 shadow-sm flex flex-col justify-between"
            >
                <div>
                    <div
                        class="mb-4 flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50 text-orange-600"
                    >
                        <ShieldCheck :size="18" />
                    </div>
                    <h3 class="font-heading text-sm font-bold text-on-surface">
                        {{ t('dashboard.settings_heading') }}
                    </h3>
                    <p
                        class="mt-2 text-xs leading-normal text-on-surface-variant font-medium"
                    >
                        {{ t('dashboard.settings_description') }}
                    </p>
                </div>
            </section>

            <section
                class="rounded-2xl border border-outline-glass bg-surface-container-lowest p-6 shadow-sm flex flex-col justify-between gap-4"
            >
                <div>
                    <div
                        class="mb-4 flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <KeyRound :size="18" />
                    </div>
                    <h3 class="font-heading text-sm font-bold text-on-surface">
                        {{ t('dashboard.manage_profile') }}
                    </h3>
                    <p
                        class="mt-2 text-xs leading-normal text-on-surface-variant font-medium"
                    >
                        {{ t('settings.profile.title') }}
                    </p>
                </div>
                <Link
                    href="/settings/profile"
                    class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:text-primary-container"
                >
                    {{ t('settings.profile.title') }} <ArrowRight :size="14" />
                </Link>
            </section>
        </div>
    </AppLayout>
</template>

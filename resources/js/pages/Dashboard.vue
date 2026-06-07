<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, KeyRound, UserRound } from '@lucide/vue';
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
        <div class="grid gap-4 md:grid-cols-3">
            <section
                class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
            >
                <UserRound class="mb-4 size-5 text-blue-700" />
                <h2 class="text-base font-semibold text-gray-950">
                    {{ t('dashboard.welcome') }}
                </h2>
                <p class="mt-2 text-sm text-gray-600">{{ auth.user?.email }}</p>
            </section>

            <section
                class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
            >
                <CheckCircle2 class="mb-4 size-5 text-emerald-700" />
                <h2 class="text-base font-semibold text-gray-950">
                    {{ t('dashboard.settings_heading') }}
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ t('dashboard.settings_description') }}
                </p>
            </section>

            <section
                class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
            >
                <KeyRound class="mb-4 size-5 text-gray-700" />
                <h2 class="text-base font-semibold text-gray-950">
                    {{ t('dashboard.manage_profile') }}
                </h2>
                <Link
                    href="/settings/profile"
                    class="mt-2 inline-block text-sm font-medium text-blue-700"
                >
                    {{ t('settings.profile.title') }}
                </Link>
            </section>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useBoundLocale } from '@/composables/useBoundLocale';
import Brand from '@/components/ui/Brand.vue';

useBoundLocale();
const { t } = useI18n();
</script>

<template>
    <Head :title="t('pwa.offline.title')" />

    <div class="min-h-screen bg-cream-50 text-charcoal-900">
        <header class="mx-auto flex max-w-md items-center justify-between px-6 py-6">
            <Link :href="'/'">
                <Brand class="text-2xl" />
            </Link>
        </header>

        <main class="mx-auto max-w-md px-6 py-16 text-center">
            <h1 class="text-3xl font-semibold leading-tight text-charcoal-900">
                {{ t('pwa.offline.heading') }}
            </h1>
            <p class="mt-4 text-base leading-relaxed text-charcoal-600">
                {{ t('pwa.offline.body') }}
            </p>
            <Link
                :href="'/'"
                class="mt-8 inline-flex items-center justify-center rounded-2xl bg-matcha-600 px-6 py-3 text-sm font-semibold text-white shadow-matcha transition hover:bg-matcha-700"
            >
                {{ t('pwa.offline.retry') }}
            </Link>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useBoundLocale } from '@/composables/useBoundLocale';
import Brand from '@/components/ui/Brand.vue';

useBoundLocale();
const { t } = useI18n();

defineProps<{
    programName: string;
}>();
</script>

<template>
    <Head :title="programName" />

    <div class="min-h-screen bg-cream-50 text-charcoal-900">
        <header class="mx-auto flex max-w-md items-center justify-between px-6 py-6">
            <Brand class="text-2xl" />
            <Link
                :href="'/staff'"
                class="text-sm font-medium text-charcoal-500 hover:text-charcoal-900"
            >
                {{ t('nav.staff_login') }}
            </Link>
        </header>

        <main class="mx-auto max-w-md px-6 pb-16">
            <section class="space-y-6 pt-12">
                <p class="text-sm font-medium uppercase tracking-wider text-matcha-600">
                    {{ t('marketing.tagline') }}
                </p>
                <h1 class="text-4xl font-semibold leading-tight text-charcoal-900">
                    {{ t('marketing.headline') }}
                </h1>
                <p class="text-lg leading-relaxed text-charcoal-600">
                    {{ t('marketing.subheadline') }}
                </p>
            </section>

            <section class="mt-12 space-y-4 rounded-3xl bg-white p-6 shadow-soft ring-1 ring-sage-200">
                <h2 class="text-lg font-semibold text-charcoal-900">
                    {{ t('marketing.how_it_works') }}
                </h2>
                <ol class="space-y-3 text-sm text-charcoal-600">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-matcha-100 text-xs font-semibold text-matcha-700">1</span>
                        <span>{{ t('marketing.step_1') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-matcha-100 text-xs font-semibold text-matcha-700">2</span>
                        <span>{{ t('marketing.step_2') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-matcha-100 text-xs font-semibold text-matcha-700">3</span>
                        <span>{{ t('marketing.step_3') }}</span>
                    </li>
                </ol>
            </section>

            <Link
                :href="'/wallet'"
                class="mt-10 flex w-full items-center justify-center rounded-2xl bg-matcha-600 px-6 py-4 text-base font-semibold text-white shadow-matcha transition hover:bg-matcha-700"
            >
                {{ t('marketing.cta') }}
            </Link>

            <p class="mt-4 text-center text-xs text-charcoal-500">
                {{ t('marketing.privacy') }}
            </p>

            <p class="mt-2 text-center text-xs">
                <Link
                    :href="'/install'"
                    class="font-semibold text-matcha-700 hover:text-matcha-800"
                >
                    {{ t('pwa.install.guide_title') }} →
                </Link>
            </p>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Share, Smartphone, Monitor } from '@lucide/vue';
import { useBoundLocale } from '@/composables/useBoundLocale';

useBoundLocale();
const { t } = useI18n();
</script>

<template>
    <Head :title="t('pwa.install.guide_title')" />

    <div class="mx-auto max-w-md px-6 py-12">
        <header class="text-center">
            <h1 class="text-2xl font-semibold text-charcoal-900">
                {{ t('pwa.install.guide_title') }}
            </h1>
            <p class="mt-3 text-sm text-charcoal-600">
                {{ t('pwa.install.guide_intro') }}
            </p>
        </header>

        <section class="mt-10 space-y-6">
            <article
                class="rounded-2xl border border-outline-glass bg-white p-5 shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-sage-50 text-matcha-700"
                    >
                        <Share :size="20" />
                    </div>
                    <h2 class="text-sm font-semibold text-charcoal-900">
                        {{ t('pwa.install.guide_ios') }}
                    </h2>
                </div>
                <ol class="mt-4 space-y-2 text-sm text-charcoal-700">
                    <li class="flex gap-2">
                        <span class="font-mono text-xs text-charcoal-500"
                            >1.</span
                        >
                        <span>{{ t('pwa.install.ios_step_1') }}</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="font-mono text-xs text-charcoal-500"
                            >2.</span
                        >
                        <span>{{ t('pwa.install.ios_step_2') }}</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="font-mono text-xs text-charcoal-500"
                            >3.</span
                        >
                        <span>{{ t('pwa.install.ios_step_3') }}</span>
                    </li>
                </ol>
            </article>

            <article
                class="rounded-2xl border border-outline-glass bg-white p-5 shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-sage-50 text-matcha-700"
                    >
                        <Smartphone :size="20" />
                    </div>
                    <h2 class="text-sm font-semibold text-charcoal-900">
                        {{ t('pwa.install.guide_android') }}
                    </h2>
                </div>
                <p class="mt-3 text-sm text-charcoal-700">
                    {{ t('pwa.install.guide_intro') }}
                </p>
            </article>

            <article
                class="rounded-2xl border border-outline-glass bg-white p-5 shadow-sm"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-sage-50 text-matcha-700"
                    >
                        <Monitor :size="20" />
                    </div>
                    <h2 class="text-sm font-semibold text-charcoal-900">
                        {{ t('pwa.install.guide_chrome') }}
                    </h2>
                </div>
                <p class="mt-3 text-sm text-charcoal-700">
                    {{ t('pwa.install.guide_chrome_body') }}
                </p>
            </article>
        </section>

        <div class="mt-10 text-center">
            <Link
                href="/"
                class="text-xs font-semibold text-matcha-700 hover:text-matcha-800"
            >
                ← {{ t('marketing.tagline') }}
            </Link>
        </div>
    </div>
</template>

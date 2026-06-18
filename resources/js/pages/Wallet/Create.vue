<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ArrowLeft } from '@lucide/vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import Brand from '@/components/ui/Brand.vue';
import WalletCreateForm from '@/components/marketing/WalletCreateForm.vue';

useBoundLocale();
const { t } = useI18n();
</script>

<template>
    <Head :title="t('wallet.create.title')" />

    <div class="min-h-screen bg-surface-bg text-on-surface">
        <header
            class="mx-auto flex max-w-md items-center justify-between px-6 py-6"
        >
            <Link :href="'/'">
                <Brand class="text-2xl" />
            </Link>
            <Link
                href="/"
                class="inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant transition hover:text-on-surface"
            >
                <ArrowLeft :size="14" />
                {{ t('common.cancel') }}
            </Link>
        </header>

        <main class="mx-auto max-w-md px-6 pb-16 pt-2">
            <div class="surface-card p-7 sm:p-8">
                <header class="mb-6 space-y-2">
                    <h1 class="heading-2">
                        {{ t('wallet.create.heading') }}
                    </h1>
                    <p class="label-help">
                        {{ t('wallet.create.subheading') }}
                    </p>
                </header>

                <WalletCreateForm />
            </div>
        </main>
    </div>
</template>

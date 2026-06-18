<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Check, Wallet, QrCode, Gift } from '@lucide/vue';
import { useBoundLocale } from '@/composables/useBoundLocale';
import Brand from '@/components/ui/Brand.vue';
import WalletCreateForm from '@/components/marketing/WalletCreateForm.vue';

useBoundLocale();
const { t } = useI18n();

defineProps<{
    programName: string;
}>();
</script>

<template>
    <Head :title="programName" />

    <div class="min-h-screen bg-surface-bg text-on-surface">
        <header
            class="mx-auto flex max-w-md items-center justify-center px-6 py-8"
        >
            <Brand class="text-2xl" />
        </header>

        <main class="mx-auto max-w-md px-6 pb-20">
            <h1
                class="text-center text-2xl font-bold tracking-tight text-on-surface"
            >
                {{ t('marketing.headline') }}
            </h1>
            <p class="mt-2 text-center text-sm text-on-surface-variant">
                {{ t('marketing.subheadline') }}
            </p>

            <div class="surface-card mt-6 p-7 shadow-md sm:p-8">
                <WalletCreateForm />

                <p
                    class="mt-5 flex items-start gap-2 text-xs text-on-surface-variant"
                >
                    <Check :size="14" class="mt-0.5 shrink-0 text-success" />
                    <span>{{ t('wallet.create.privacy') }}</span>
                </p>
            </div>

            <!-- How it works: compact 3-step row -->
            <ol class="mt-8 space-y-3">
                <li class="flex items-center gap-3 text-sm text-on-surface">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary"
                    >
                        <Wallet :size="16" />
                    </div>
                    <span>{{ t('marketing.step_1') }}</span>
                </li>
                <li class="flex items-center gap-3 text-sm text-on-surface">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary"
                    >
                        <QrCode :size="16" />
                    </div>
                    <span>{{ t('marketing.step_2') }}</span>
                </li>
                <li class="flex items-center gap-3 text-sm text-on-surface">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary"
                    >
                        <Gift :size="16" />
                    </div>
                    <span>{{ t('marketing.step_3') }}</span>
                </li>
            </ol>

            <p class="mt-8 text-center text-xs text-on-surface-variant">
                <Link
                    :href="'/install'"
                    class="font-semibold text-primary transition hover:text-primary-container"
                >
                    {{ t('pwa.install.guide_title') }} →
                </Link>
            </p>
        </main>
    </div>
</template>

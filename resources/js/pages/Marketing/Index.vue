<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Wallet, QrCode, Gift, Check } from '@lucide/vue';
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
        <!-- Header -->
        <header class="mx-auto flex max-w-5xl items-center justify-between px-6 py-6">
            <Brand class="text-2xl" />
            <Link
                :href="'/dashboard'"
                class="text-sm font-medium text-on-surface-variant transition hover:text-on-surface"
            >
                {{ t('nav.staff_login') }}
            </Link>
        </header>

        <main class="mx-auto max-w-5xl px-6 pb-20">
            <!-- Hero + form grid -->
            <section class="grid items-start gap-10 pt-12 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:pt-20">
                <!-- Left: pitch -->
                <div class="space-y-8">
                    <p class="label-eyebrow text-primary">
                        {{ t('marketing.tagline') }}
                    </p>
                    <h1 class="text-4xl font-bold leading-[1.1] tracking-tight text-on-surface sm:text-5xl">
                        {{ t('marketing.headline') }}
                    </h1>
                    <p class="max-w-lg text-lg leading-relaxed text-on-surface-variant">
                        {{ t('marketing.subheadline') }}
                    </p>

                    <ol class="space-y-4 pt-2">
                        <li class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                                <Wallet :size="20" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">
                                    {{ t('marketing.step_1') }}
                                </p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                                <QrCode :size="20" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">
                                    {{ t('marketing.step_2') }}
                                </p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                                <Gift :size="20" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">
                                    {{ t('marketing.step_3') }}
                                </p>
                            </div>
                        </li>
                    </ol>

                    <ul class="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-3">
                        <li class="flex items-center gap-2 text-xs text-on-surface-variant">
                            <Check :size="14" class="shrink-0 text-success" />
                            {{ t('marketing.bullet_no_email') ?? 'No email' }}
                        </li>
                        <li class="flex items-center gap-2 text-xs text-on-surface-variant">
                            <Check :size="14" class="shrink-0 text-success" />
                            {{ t('marketing.bullet_no_password') ?? 'No password' }}
                        </li>
                        <li class="flex items-center gap-2 text-xs text-on-surface-variant">
                            <Check :size="14" class="shrink-0 text-success" />
                            {{ t('marketing.bullet_install') ?? 'Add to home screen' }}
                        </li>
                    </ul>
                </div>

                <!-- Right: card with the wallet form -->
                <div class="surface-card p-7 shadow-md sm:p-9">
                    <header class="mb-6 space-y-2">
                        <h2 class="heading-3">
                            {{ t('wallet.create.heading') }}
                        </h2>
                        <p class="label-help">
                            {{ t('wallet.create.subheading') }}
                        </p>
                    </header>

                    <WalletCreateForm />

                    <p class="mt-6 flex items-start gap-2 text-xs text-on-surface-variant">
                        <Check :size="14" class="mt-0.5 shrink-0 text-success" />
                        <span>{{ t('wallet.create.privacy') }}</span>
                    </p>
                </div>
            </section>

            <p class="mt-16 text-center text-xs text-on-surface-variant">
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

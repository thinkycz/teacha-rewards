<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { X, Share, Download } from '@lucide/vue';
import { usePwaInstall } from '@/composables/usePwaInstall';

const { t } = useI18n();
const { canShowChromePrompt, isIosSafari, isInstalled, prompt } = usePwaInstall();

const STORAGE_KEY = 'teacha.install-banner.dismissed-at';
const VISIBLE_AFTER_DAYS = 7;

const isVisible = ref<boolean>(false);

function shouldShow(): boolean {
    if (typeof localStorage === 'undefined') {
        return false;
    }
    const dismissedAt = localStorage.getItem(STORAGE_KEY);
    if (dismissedAt === null) {
        return true;
    }
    const ms = Date.now() - Number(dismissedAt);
    return ms > VISIBLE_AFTER_DAYS * 24 * 60 * 60 * 1000;
}

onMounted(() => {
    if (isInstalled.value) {
        return;
    }
    if (canShowChromePrompt.value || isIosSafari.value) {
        isVisible.value = shouldShow();
    }
});

function dismiss(): void {
    isVisible.value = false;
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, String(Date.now()));
    }
}

async function install(): Promise<void> {
    const outcome = await prompt();
    if (outcome === 'unavailable' || outcome === 'dismissed') {
        // iOS Safari or the user declined. Keep the banner visible
        // so they can read the manual steps.
        return;
    }
    isVisible.value = false;
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <aside
            v-if="isVisible"
            class="fixed inset-x-3 bottom-20 z-30 mx-auto max-w-md rounded-2xl border border-matcha-300 bg-white p-4 shadow-matcha sm:bottom-6"
            role="region"
            :aria-label="t('pwa.install.banner_heading')"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-matcha-500 to-matcha-700 text-white"
                >
                    <Download :size="20" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-charcoal-900">
                        {{ t('pwa.install.banner_heading') }}
                    </p>
                    <p class="mt-1 text-xs text-charcoal-600">
                        {{ t('pwa.install.banner_body') }}
                    </p>

                    <ol
                        v-if="isIosSafari"
                        class="mt-3 space-y-1 text-xs text-charcoal-600"
                    >
                        <li class="flex items-start gap-2">
                            <Share
                                :size="14"
                                class="mt-0.5 shrink-0"
                            />
                            <span>{{ t('pwa.install.ios_step_1') }}</span>
                        </li>
                        <li class="ml-5">
                            {{ t('pwa.install.ios_step_2') }}
                        </li>
                        <li class="ml-5">
                            {{ t('pwa.install.ios_step_3') }}
                        </li>
                    </ol>

                    <div class="mt-3 flex items-center gap-2">
                        <button
                            v-if="canShowChromePrompt"
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-xl bg-matcha-600 px-4 text-xs font-semibold text-white transition hover:bg-matcha-700"
                            @click="install"
                        >
                            {{ t('pwa.install.banner_install') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-xl border border-outline-glass bg-white px-3 text-xs font-semibold text-charcoal-700 transition hover:bg-sage-50"
                            @click="dismiss"
                        >
                            {{ t('pwa.install.banner_dismiss') }}
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1 text-charcoal-500 transition hover:bg-sage-50 hover:text-charcoal-700"
                    :aria-label="t('pwa.install.banner_dismiss')"
                    @click="dismiss"
                >
                    <X :size="16" />
                </button>
            </div>
        </aside>
    </Transition>
</template>

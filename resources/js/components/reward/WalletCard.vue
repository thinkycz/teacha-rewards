<script setup lang="ts">
import { useI18n } from 'vue-i18n';

useI18n();

defineProps<{
    wallet: {
        first_name: string;
        wallet_number: string;
    };
}>();
</script>

<template>
    <article
        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-matcha-500 via-matcha-600 to-matcha-700 p-6 text-white shadow-matcha"
    >
        <div class="absolute right-0 top-0 h-32 w-32 -translate-y-12 translate-x-12 rounded-full bg-white/10" />
        <div class="absolute bottom-0 left-0 h-24 w-24 -translate-x-8 translate-y-8 rounded-full bg-white/5" />

        <div class="relative">
            <p class="text-xs font-medium uppercase tracking-widest text-white/70">
                Teacha Rewards
            </p>
            <p class="mt-1 text-2xl font-semibold">
                {{ wallet.first_name }}
            </p>
            <p class="mt-6 font-mono text-lg tracking-widest">
                {{ wallet.wallet_number }}
            </p>
            <div class="mt-4">
                <slot />
            </div>
        </div>
    </article>
</template>

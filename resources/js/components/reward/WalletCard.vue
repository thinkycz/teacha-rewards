<script setup lang="ts">
import { useI18n } from 'vue-i18n';

useI18n();

defineProps<{
    wallet: {
        first_name: string;
        wallet_number: string;
    };
    compact?: boolean;
}>();
</script>

<template>
    <article
        class="relative overflow-hidden rounded-2xl bg-primary p-4 text-on-primary shadow-lg"
        :class="compact ? 'rounded-xl p-3' : ''"
    >
        <div class="relative flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p
                    class="text-[10px] font-semibold uppercase tracking-widest text-on-primary/70"
                >
                    Teacha Rewards
                </p>
                <p
                    class="mt-0.5 truncate font-semibold"
                    :class="compact ? 'text-base' : 'text-xl'"
                >
                    {{ wallet.first_name }}
                </p>
                <p
                    class="mt-0.5 font-mono text-xs tracking-widest text-on-primary/80"
                >
                    {{ wallet.wallet_number }}
                </p>
            </div>
            <div v-if="$slots.default" class="shrink-0 text-right">
                <slot />
            </div>
        </div>
    </article>
</template>

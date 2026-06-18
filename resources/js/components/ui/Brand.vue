<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useSharedProps } from '@/composables/useSharedProps';
import { useI18n } from 'vue-i18n';

withDefaults(
    defineProps<{
        href?: string;
        // 'mark' renders just the logo mark; 'full' (default) renders
        // the mark + the wordmark + the small REWARDS subtitle.
        variant?: 'mark' | 'full';
    }>(),
    {
        href: '/',
        variant: 'full',
    },
);

const { app } = useSharedProps();
const { t } = useI18n();
</script>

<template>
    <Link
        :href="href"
        class="flex items-center gap-2.5 font-medium select-none"
    >
        <img
            :src="'/favicon.svg?v=2'"
            :alt="app.name"
            class="h-9 w-9 shrink-0"
            aria-hidden="true"
        />
        <div v-if="variant === 'full'" class="text-left">
            <h1
                class="mb-0.5 font-heading text-sm font-bold tracking-tight text-on-surface leading-none"
            >
                {{ app.name }}
            </h1>
            <p
                class="font-mono text-[9px] font-semibold tracking-wider text-on-surface-variant uppercase opacity-75 leading-none"
            >
                {{ t('brand.subtitle') }}
            </p>
        </div>
    </Link>
</template>

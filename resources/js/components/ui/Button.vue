<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        type?: 'button' | 'submit';
        class?: string;
        disabled?: boolean;
    }>(),
    {
        type: 'button',
        class: '',
        disabled: false,
    },
);
</script>

<template>
    <button
        :type="props.type"
        :disabled="props.disabled"
        :class="
            cn(
                'inline-flex h-11 items-center justify-center rounded-xl bg-primary px-5 text-sm font-semibold text-on-primary shadow-sm transition hover:bg-primary-container active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer gap-2',
                props.class,
            )
        "
    >
        <slot />
    </button>
</template>

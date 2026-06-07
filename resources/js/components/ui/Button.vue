<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        type?: 'button' | 'submit';
        variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
        class?: string;
        disabled?: boolean;
    }>(),
    {
        type: 'button',
        variant: 'primary',
        class: '',
        disabled: false,
    },
);

const variants = {
    primary: 'border-blue-700 bg-blue-700 text-white hover:bg-blue-800',
    secondary: 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50',
    ghost: 'border-transparent bg-transparent text-gray-700 hover:bg-gray-100',
    danger: 'border-red-700 bg-red-700 text-white hover:bg-red-800',
};
</script>

<template>
    <button
        :type="props.type"
        :disabled="props.disabled"
        :class="
            cn(
                'inline-flex h-10 items-center justify-center rounded-md border px-4 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-60',
                variants[props.variant],
                props.class,
            )
        "
    >
        <slot />
    </button>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        for?: string;
        required?: boolean;
    }>(),
    {
        for: undefined,
        required: false,
    },
);
</script>

<template>
    <label :for="$props.for" class="label-eyebrow">
        <slot />
        <span v-if="required" class="ml-1 text-error-red" aria-hidden="true"
            >*</span
        >
    </label>
</template>

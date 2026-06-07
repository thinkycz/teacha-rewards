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
    <label :for="$props.for" class="text-sm font-medium text-gray-800">
        <slot />
        <span v-if="required" class="ml-0.5 text-red-700" aria-hidden="true"
            >*</span
        >
    </label>
</template>

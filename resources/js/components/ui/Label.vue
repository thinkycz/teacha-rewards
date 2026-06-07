<script setup lang="ts">
defineProps<{
    for?: string;
}>();
</script>

<template>
    <label :for="$props.for" class="text-sm font-medium text-gray-800">
        <slot />
    </label>
</template>

<script setup lang="ts">
defineProps<{
    message?: string | undefined;
}>();
</script>

<template>
    <p v-if="message" class="text-sm text-red-700" role="alert">
        {{ message }}
    </p>
</template>

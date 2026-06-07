<script setup lang="ts">
withDefaults(
    defineProps<{
        id?: string;
        message?: string | undefined;
    }>(),
    {
        id: undefined,
        message: undefined,
    },
);
</script>

<template>
    <p v-if="message" :id="$props.id" class="text-sm text-red-700" role="alert">
        {{ message }}
    </p>
</template>

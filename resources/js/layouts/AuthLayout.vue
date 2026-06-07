<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Alert from '@/components/ui/Alert.vue';
import { useSharedProps } from '@/composables/useSharedProps';

defineProps<{
    title: string;
    subtitle: string;
}>();

const { app, flash } = useSharedProps();
</script>

<template>
    <Head :title="title" />

    <main
        class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-10"
    >
        <section class="w-full max-w-md">
            <Link
                href="/login"
                class="mb-8 block text-center text-lg font-semibold text-gray-950"
            >
                {{ app.name }}
            </Link>

            <div
                class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-950">
                        {{ title }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">{{ subtitle }}</p>
                </div>

                <Alert v-if="flash.success" variant="success" class="mb-5">{{
                    flash.success
                }}</Alert>
                <Alert v-if="flash.error" variant="error" class="mb-5">{{
                    flash.error
                }}</Alert>

                <slot />
            </div>
        </section>
    </main>
</template>

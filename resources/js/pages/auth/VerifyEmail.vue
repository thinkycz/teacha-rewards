<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import { useBoundLocale } from '@/composables/useBoundLocale';

const form = useForm({});
const { t } = useI18n();

useBoundLocale();

function submit(): void {
    form.post('/verify-email');
}
</script>
<template>
    <AppLayout :title="t('auth.verify.title')">
        <section
            class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
        >
            <p class="max-w-2xl text-sm text-gray-700">
                {{ t('auth.verify.description') }}
            </p>

            <Button class="mt-5" :disabled="form.processing" @click="submit">{{
                t('auth.verify.submit')
            }}</Button>
        </section>
    </AppLayout>
</template>

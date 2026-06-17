<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Save, ArrowRight, Printer } from '@lucide/vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import FieldError from '@/components/ui/FieldError.vue';
import { fieldError } from '@/composables/useFieldError';

const { t } = useI18n();

interface Settings {
    cashback_rate: string;
    currency: string;
    program_name: string;
    store_name: string;
}

const props = defineProps<{
    settings: Settings;
}>();

const form = useForm({
    cashback_rate: props.settings.cashback_rate,
    currency: props.settings.currency,
    program_name: props.settings.program_name,
    store_name: props.settings.store_name,
});

function submit(): void {
    form.post('/settings', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="t('dashboard.settings.index.title')" />

    <AdminLayout :title="t('dashboard.settings.index.title')">
        <div class="space-y-6">
            <header>
                <h1 class="heading-2">
                    {{ t('dashboard.settings.index.heading') }}
                </h1>
                <p class="mt-1 text-sm text-on-surface-variant">
                    {{ t('dashboard.settings.index.subheading') }}
                </p>
            </header>

            <form
                class="surface-card space-y-5 p-5"
                @submit.prevent="submit"
            >
                <div class="space-y-2">
                    <Label for="cashback_rate" required>
                        {{ t('dashboard.settings.index.cashback_rate_label') }}
                    </Label>
                    <Input
                        id="cashback_rate"
                        v-model="form.cashback_rate"
                        type="number"
                        inputmode="decimal"
                        step="0.01"
                        min="0"
                        max="100"
                        :invalid="fieldError(form.errors, 'cashback_rate', 'settings').invalid"
                        :described-by="fieldError(form.errors, 'cashback_rate', 'settings').describedBy"
                        required
                    />
                    <p class="label-help">
                        {{ t('dashboard.settings.index.cashback_rate_help') }}
                    </p>
                    <FieldError v-bind="fieldError(form.errors, 'cashback_rate', 'settings')" />
                </div>

                <div class="space-y-2">
                    <Label for="currency" required>
                        {{ t('dashboard.settings.index.currency_label') }}
                    </Label>
                    <Input
                        id="currency"
                        v-model="form.currency"
                        type="text"
                        maxlength="3"
                        :placeholder="t('dashboard.settings.index.currency_label')"
                        :invalid="fieldError(form.errors, 'currency', 'settings').invalid"
                        :described-by="fieldError(form.errors, 'currency', 'settings').describedBy"
                        required
                    />
                    <p class="label-help">
                        {{ t('dashboard.settings.index.currency_help') }}
                    </p>
                    <FieldError v-bind="fieldError(form.errors, 'currency', 'settings')" />
                </div>

                <div class="space-y-2">
                    <Label for="program_name" required>
                        {{ t('dashboard.settings.index.program_name_label') }}
                    </Label>
                    <Input
                        id="program_name"
                        v-model="form.program_name"
                        type="text"
                        :placeholder="t('dashboard.settings.index.program_name_label')"
                        :invalid="fieldError(form.errors, 'program_name', 'settings').invalid"
                        :described-by="fieldError(form.errors, 'program_name', 'settings').describedBy"
                        required
                    />
                    <p class="label-help">
                        {{ t('dashboard.settings.index.program_name_help') }}
                    </p>
                    <FieldError v-bind="fieldError(form.errors, 'program_name', 'settings')" />
                </div>

                <div class="space-y-2">
                    <Label for="store_name" required>
                        {{ t('dashboard.settings.index.store_name_label') }}
                    </Label>
                    <Input
                        id="store_name"
                        v-model="form.store_name"
                        type="text"
                        :placeholder="t('dashboard.settings.index.store_name_label')"
                        :invalid="fieldError(form.errors, 'store_name', 'settings').invalid"
                        :described-by="fieldError(form.errors, 'store_name', 'settings').describedBy"
                        required
                    />
                    <p class="label-help">
                        {{ t('dashboard.settings.index.store_name_help') }}
                    </p>
                    <FieldError v-bind="fieldError(form.errors, 'store_name', 'settings')" />
                </div>

                <div class="pt-2">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        <Save :size="14" />
                        {{ t('dashboard.settings.index.submit') }}
                    </Button>
                </div>
            </form>

            <Link
                href="/store-qr"
                class="surface-card group flex items-center justify-between gap-4 p-5 transition hover:border-primary"
            >
                <div>
                    <h2 class="heading-3">
                        {{ t('dashboard.store_qr.title') }}
                    </h2>
                    <p class="mt-1 text-xs text-on-surface-variant">
                        {{ t('dashboard.store_qr.headline') }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-on-primary transition group-hover:bg-primary-container">
                    <Printer :size="14" />
                    {{ t('dashboard.store_qr.print') }}
                    <ArrowRight :size="14" />
                </span>
            </Link>
        </div>
    </AdminLayout>
</template>

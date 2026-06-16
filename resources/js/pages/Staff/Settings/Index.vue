<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Save } from '@lucide/vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import FieldError from '@/components/ui/FieldError.vue';
import { fieldError } from '@/composables/useFieldError';

useI18n();
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
    form.post('/staff/settings', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="t('staff.settings.index.title')" />

    <StaffLayout :title="t('staff.settings.index.title')">
        <div class="space-y-6">
            <header>
                <h1 class="text-2xl font-semibold text-charcoal-900">
                    {{ t('staff.settings.index.heading') }}
                </h1>
                <p class="mt-1 text-sm text-charcoal-600">
                    {{ t('staff.settings.index.subheading') }}
                </p>
            </header>

            <form
                class="space-y-5 rounded-2xl border border-outline-glass bg-white p-5 shadow-sm"
                @submit.prevent="submit"
            >
                <div class="space-y-2">
                    <Label for="cashback_rate" required>
                        {{ t('staff.settings.index.cashback_rate_label') }}
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
                    <p class="text-[10px] text-charcoal-500">
                        {{ t('staff.settings.index.cashback_rate_help') }}
                    </p>
                    <FieldError v-bind="fieldError(form.errors, 'cashback_rate', 'settings')" />
                </div>

                <div class="space-y-2">
                    <Label for="currency" required>
                        {{ t('staff.settings.index.currency_label') }}
                    </Label>
                    <Input
                        id="currency"
                        v-model="form.currency"
                        type="text"
                        maxlength="3"
                        :placeholder="t('staff.settings.index.currency_label')"
                        :invalid="fieldError(form.errors, 'currency', 'settings').invalid"
                        :described-by="fieldError(form.errors, 'currency', 'settings').describedBy"
                        required
                    />
                    <p class="text-[10px] text-charcoal-500">
                        {{ t('staff.settings.index.currency_help') }}
                    </p>
                    <FieldError v-bind="fieldError(form.errors, 'currency', 'settings')" />
                </div>

                <div class="space-y-2">
                    <Label for="program_name" required>
                        {{ t('staff.settings.index.program_name_label') }}
                    </Label>
                    <Input
                        id="program_name"
                        v-model="form.program_name"
                        type="text"
                        :placeholder="t('staff.settings.index.program_name_label')"
                        :invalid="fieldError(form.errors, 'program_name', 'settings').invalid"
                        :described-by="fieldError(form.errors, 'program_name', 'settings').describedBy"
                        required
                    />
                    <p class="text-[10px] text-charcoal-500">
                        {{ t('staff.settings.index.program_name_help') }}
                    </p>
                    <FieldError v-bind="fieldError(form.errors, 'program_name', 'settings')" />
                </div>

                <div class="space-y-2">
                    <Label for="store_name" required>
                        {{ t('staff.settings.index.store_name_label') }}
                    </Label>
                    <Input
                        id="store_name"
                        v-model="form.store_name"
                        type="text"
                        :placeholder="t('staff.settings.index.store_name_label')"
                        :invalid="fieldError(form.errors, 'store_name', 'settings').invalid"
                        :described-by="fieldError(form.errors, 'store_name', 'settings').describedBy"
                        required
                    />
                    <p class="text-[10px] text-charcoal-500">
                        {{ t('staff.settings.index.store_name_help') }}
                    </p>
                    <FieldError v-bind="fieldError(form.errors, 'store_name', 'settings')" />
                </div>

                <div class="pt-2">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        <Save :size="14" />
                        {{ t('staff.settings.index.submit') }}
                    </Button>
                </div>
            </form>
        </div>
    </StaffLayout>
</template>

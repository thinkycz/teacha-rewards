<script setup lang="ts">
// Re-export of the existing `<Input>` with `type="tel"`, an explicit
// `inputmode="tel"`, and an i18n-aware placeholder. The behavior of
// the input is unchanged; this wrapper exists so form code reads
// naturally and the placeholder is centralized in i18n.
import { useI18n } from 'vue-i18n';
import Input from '@/components/ui/Input.vue';

const { t } = useI18n();

withDefaults(
    defineProps<{
        id?: string;
        name?: string;
        invalid?: boolean;
        describedBy?: string;
        required?: boolean;
        maxlength?: number;
        pattern?: string;
    }>(),
    {
        id: undefined,
        name: 'phone',
        invalid: false,
        describedBy: undefined,
        required: true,
        maxlength: undefined,
        pattern: undefined,
    },
);
</script>

<template>
    <Input
        :id="id"
        :name="name"
        type="tel"
        inputmode="tel"
        autocomplete="tel"
        :placeholder="t('wallet.create.phone_placeholder')"
        :invalid="invalid"
        :described-by="describedBy"
        :required="required"
        :maxlength="maxlength"
        :pattern="pattern"
    />
</template>

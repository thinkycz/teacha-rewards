<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { fieldError } from '@/composables/useFieldError';

useI18n();
const { t } = useI18n();
</script>

<template>
    <Form
        v-slot="{ errors, processing }"
        action="/wallet"
        method="post"
        class="space-y-5"
    >
        <div class="space-y-2">
            <Label for="phone" required>
                {{ t('wallet.create.phone_label') }}
            </Label>
            <Input
                id="phone"
                name="phone"
                type="tel"
                inputmode="tel"
                autocomplete="tel"
                :placeholder="t('wallet.create.phone_placeholder')"
                :invalid="fieldError(errors, 'phone', 'wallet').invalid"
                :described-by="fieldError(errors, 'phone', 'wallet').describedBy"
                required
            />
            <FieldError v-bind="fieldError(errors, 'phone', 'wallet')" />
        </div>

        <div class="space-y-2">
            <Label for="first_name" required>
                {{ t('wallet.create.first_name_label') }}
            </Label>
            <Input
                id="first_name"
                name="first_name"
                type="text"
                autocomplete="given-name"
                :placeholder="t('wallet.create.first_name_placeholder')"
                :invalid="fieldError(errors, 'first_name', 'wallet').invalid"
                :described-by="fieldError(errors, 'first_name', 'wallet').describedBy"
                required
            />
            <FieldError v-bind="fieldError(errors, 'first_name', 'wallet')" />
        </div>

        <Button
            type="submit"
            :disabled="processing"
            class="w-full justify-center"
        >
            {{ t('wallet.create.submit') }}
        </Button>
    </Form>
</template>

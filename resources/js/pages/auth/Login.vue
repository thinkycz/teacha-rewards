<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import FormField from '@/components/ui/FormField.vue';
import Input from '@/components/ui/Input.vue';
import { useBoundLocale } from '@/composables/useBoundLocale';

type LoginFields = {
    email: string;
    password: string;
};

const { t } = useI18n();

useBoundLocale();
</script>

<template>
    <AuthLayout
        :title="t('auth.login.title')"
        :subtitle="t('auth.login.subtitle')"
    >
        <Form
            v-slot="{ errors, processing }"
            action="/login"
            method="post"
            :reset-on-error="['password']"
            class="space-y-5"
        >
            <FormField
                :label="t('fields.email')"
                :error="
                    (
                        errors as LoginFields extends object
                            ? LoginFields
                            : never
                    )['email']
                "
                required
            >
                <template #default="{ id, describedBy, invalid }">
                    <Input
                        :id="id"
                        name="email"
                        type="email"
                        autocomplete="email"
                        :aria-describedby="describedBy"
                        :invalid="invalid"
                        required
                    />
                </template>
            </FormField>

            <FormField
                :label="t('fields.password')"
                :error="
                    (
                        errors as LoginFields extends object
                            ? LoginFields
                            : never
                    )['password']
                "
                required
            >
                <template #default="{ id, describedBy, invalid }">
                    <Input
                        :id="id"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        :aria-describedby="describedBy"
                        :invalid="invalid"
                        required
                    />
                </template>
            </FormField>

            <Button type="submit" class="w-full" :disabled="processing">{{
                t('auth.login.submit')
            }}</Button>
        </Form>

        <div class="mt-6 flex items-center justify-between text-sm">
            <Link
                href="/forgot-password"
                class="font-medium text-blue-700 hover:text-blue-800"
                >{{ t('auth.login.forgot_link') }}</Link
            >
            <Link
                href="/register"
                class="font-medium text-blue-700 hover:text-blue-800"
                >{{ t('auth.register.title') }}</Link
            >
        </div>
    </AuthLayout>
</template>

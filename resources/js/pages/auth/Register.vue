<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Select from '@/components/ui/Select.vue';

type RegisterFields = {
    email: string;
    password: string;
    locale: string;
};

const props = defineProps<{
    locales: string[];
}>();

const localeLabels: Record<string, string> = {
    en: 'English',
    cs: 'Čeština',
    sk: 'Slovenčina',
};

const localeOptions = props.locales.map((value) => ({
    value,
    label: localeLabels[value] ?? value,
}));
</script>

<template>
    <AuthLayout
        title="Create account"
        subtitle="Start with an authenticated Inertia workspace."
    >
        <Form
            v-slot="{ errors, processing }"
            action="/register"
            method="post"
            :reset-on-error="['password']"
            class="space-y-5"
        >
            <div class="space-y-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                />
                <FieldError
                    :message="
                        (
                            errors as RegisterFields extends object
                                ? RegisterFields
                                : never
                        )['email']
                    "
                />
            </div>

            <div class="space-y-2">
                <Label for="password">Password</Label>
                <Input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    required
                />
                <FieldError
                    :message="
                        (
                            errors as RegisterFields extends object
                                ? RegisterFields
                                : never
                        )['password']
                    "
                />
            </div>

            <div class="space-y-2">
                <Label for="locale">Locale</Label>
                <Select
                    id="locale"
                    name="locale"
                    :options="localeOptions"
                    :default-value="locales[0] ?? 'en'"
                    required
                />
                <FieldError
                    :message="
                        (
                            errors as RegisterFields extends object
                                ? RegisterFields
                                : never
                        )['locale']
                    "
                />
            </div>

            <Button type="submit" class="w-full" :disabled="processing"
                >Register</Button
            >
        </Form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Already have an account?
            <Link
                href="/login"
                class="font-medium text-blue-700 hover:text-blue-800"
                >Log in</Link
            >
        </p>
    </AuthLayout>
</template>

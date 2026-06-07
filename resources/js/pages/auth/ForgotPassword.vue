<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

type ForgotPasswordFields = {
    email: string;
};
</script>

<template>
    <AuthLayout
        title="Forgot password"
        subtitle="Send a new generated password using the core mail flow."
    >
        <Form
            v-slot="{ errors, processing }"
            action="/forgot-password"
            method="post"
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
                            errors as ForgotPasswordFields extends object
                                ? ForgotPasswordFields
                                : never
                        )['email']
                    "
                />
            </div>

            <Button type="submit" class="w-full" :disabled="processing"
                >Send password</Button
            >
        </Form>

        <p class="mt-6 text-center text-sm">
            <Link
                href="/login"
                class="font-medium text-blue-700 hover:text-blue-800"
                >Back to login</Link
            >
        </p>
    </AuthLayout>
</template>

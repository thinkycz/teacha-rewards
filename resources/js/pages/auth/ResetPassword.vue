<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

type ResetPasswordFields = {
    email: string;
    token: string;
    password: string;
};

defineProps<{
    email: string;
    token: string;
}>();
</script>

<template>
    <AuthLayout
        title="Reset password"
        subtitle="Set a new password with a valid reset token."
    >
        <Form
            v-slot="{ errors, processing }"
            action="/reset-password"
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
                    :default-value="email"
                    required
                />
                <FieldError
                    :message="
                        (
                            errors as ResetPasswordFields extends object
                                ? ResetPasswordFields
                                : never
                        )['email']
                    "
                />
            </div>

            <div class="space-y-2">
                <Label for="token">Token</Label>
                <Input
                    id="token"
                    name="token"
                    autocomplete="one-time-code"
                    :default-value="token"
                    required
                />
                <FieldError
                    :message="
                        (
                            errors as ResetPasswordFields extends object
                                ? ResetPasswordFields
                                : never
                        )['token']
                    "
                />
            </div>

            <div class="space-y-2">
                <Label for="password">New password</Label>
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
                            errors as ResetPasswordFields extends object
                                ? ResetPasswordFields
                                : never
                        )['password']
                    "
                />
            </div>

            <Button type="submit" class="w-full" :disabled="processing"
                >Reset password</Button
            >
        </Form>
    </AuthLayout>
</template>

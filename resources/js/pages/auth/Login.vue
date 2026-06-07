<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

type LoginFields = {
    email: string;
    password: string;
};
</script>

<template>
    <AuthLayout title="Log in" subtitle="Enter your credentials to continue.">
        <Form
            v-slot="{ errors, processing }"
            action="/login"
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
                            errors as LoginFields extends object
                                ? LoginFields
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
                    autocomplete="current-password"
                    required
                />
                <FieldError
                    :message="
                        (
                            errors as LoginFields extends object
                                ? LoginFields
                                : never
                        )['password']
                    "
                />
            </div>

            <Button type="submit" class="w-full" :disabled="processing"
                >Log in</Button
            >
        </Form>

        <div class="mt-6 flex items-center justify-between text-sm">
            <Link
                href="/forgot-password"
                class="font-medium text-blue-700 hover:text-blue-800"
                >Forgot password?</Link
            >
            <Link
                href="/register"
                class="font-medium text-blue-700 hover:text-blue-800"
                >Create account</Link
            >
        </div>
    </AuthLayout>
</template>

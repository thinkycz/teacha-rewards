<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

const form = useForm({
    email: '',
    password: '',
});

function submit(): void {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthLayout title="Log in" subtitle="Enter your credentials to continue.">
        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                />
                <FieldError :message="form.errors.email" />
            </div>

            <div class="space-y-2">
                <Label for="password">Password</Label>
                <Input
                    id="password"
                    v-model="form.password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                />
                <FieldError :message="form.errors.password" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing"
                >Log in</Button
            >
        </form>

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

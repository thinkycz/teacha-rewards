<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

const form = useForm({
    email: '',
});

function submit(): void {
    form.post('/forgot-password');
}
</script>

<template>
    <AuthLayout
        title="Forgot password"
        subtitle="Send a new generated password using the core mail flow."
    >
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

            <Button type="submit" class="w-full" :disabled="form.processing"
                >Send password</Button
            >
        </form>

        <p class="mt-6 text-center text-sm">
            <Link
                href="/login"
                class="font-medium text-blue-700 hover:text-blue-800"
                >Back to login</Link
            >
        </p>
    </AuthLayout>
</template>

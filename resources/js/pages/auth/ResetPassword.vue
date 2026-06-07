<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
});

function submit(): void {
    form.post('/reset-password', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthLayout
        title="Reset password"
        subtitle="Set a new password with a valid reset token."
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

            <div class="space-y-2">
                <Label for="token">Token</Label>
                <Input
                    id="token"
                    v-model="form.token"
                    name="token"
                    autocomplete="one-time-code"
                    required
                />
                <FieldError :message="form.errors.token" />
            </div>

            <div class="space-y-2">
                <Label for="password">New password</Label>
                <Input
                    id="password"
                    v-model="form.password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    required
                />
                <FieldError :message="form.errors.password" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing"
                >Reset password</Button
            >
        </form>
    </AuthLayout>
</template>

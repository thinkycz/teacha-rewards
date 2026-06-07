<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import FlashAlerts from '@/components/ui/FlashAlerts.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';

const form = useForm({
    password: '',
    new_password: '',
});

function submit(): void {
    form.post('/settings/password', {
        onFinish: () => form.reset(),
    });
}
</script>

<template>
    <AppLayout title="Password settings">
        <section
            class="max-w-xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
        >
            <FlashAlerts />

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="password">Current password</Label>
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

                <div class="space-y-2">
                    <Label for="new_password">New password</Label>
                    <Input
                        id="new_password"
                        v-model="form.new_password"
                        name="new_password"
                        type="password"
                        autocomplete="new-password"
                        required
                    />
                    <FieldError :message="form.errors.new_password" />
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing"
                        >Update password</Button
                    >
                    <Link
                        href="/settings/profile"
                        class="text-sm font-medium text-blue-700"
                        >Back to profile</Link
                    >
                </div>
            </form>
        </section>
    </AppLayout>
</template>

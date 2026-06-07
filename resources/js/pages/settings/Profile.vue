<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import FlashAlerts from '@/components/ui/FlashAlerts.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Select from '@/components/ui/Select.vue';
import { useSharedProps } from '@/composables/useSharedProps';

const { user, app } = useSharedProps();

const localeOptions = [
    { value: 'en', label: 'English' },
    { value: 'cs', label: 'Čeština' },
    { value: 'sk', label: 'Slovenčina' },
];

const form = useForm({
    email: user.value?.email ?? '',
    locale: user.value?.locale ?? app.value.locale,
});

function submit(): void {
    form.post('/settings/profile');
}
</script>

<template>
    <AppLayout title="Profile settings">
        <section
            class="max-w-xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
        >
            <FlashAlerts />

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
                    <Label for="locale">Locale</Label>
                    <Select
                        id="locale"
                        v-model="form.locale"
                        name="locale"
                        :options="localeOptions"
                        required
                    />
                    <FieldError :message="form.errors.locale" />
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing"
                        >Save profile</Button
                    >
                    <Link
                        href="/settings/password"
                        class="text-sm font-medium text-blue-700"
                        >Change password</Link
                    >
                </div>
            </form>
        </section>
    </AppLayout>
</template>

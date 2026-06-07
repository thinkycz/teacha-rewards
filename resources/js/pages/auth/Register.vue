<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import Select from '@/components/ui/Select.vue';

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

const form = useForm({
    email: '',
    password: '',
    locale: props.locales[0] ?? 'en',
});

function submit(): void {
    form.post('/register', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <AuthLayout
        title="Create account"
        subtitle="Start with an authenticated Inertia workspace."
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
                <Label for="password">Password</Label>
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

            <Button type="submit" class="w-full" :disabled="form.processing"
                >Register</Button
            >
        </form>

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

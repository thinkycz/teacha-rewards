<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useBoundLocale } from '@/composables/useBoundLocale';
import Brand from '@/components/ui/Brand.vue';
import Button from '@/components/ui/Button.vue';
import FieldError from '@/components/ui/FieldError.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import { fieldError } from '@/composables/useFieldError';

useBoundLocale();
const { t } = useI18n();
</script>

<template>
    <Head :title="t('wallet.create.title')" />

    <div class="min-h-screen bg-cream-50 text-charcoal-900">
        <header class="mx-auto flex max-w-md items-center justify-between px-6 py-6">
            <Link :href="'/'">
                <Brand class="text-2xl" />
            </Link>
        </header>

        <main class="mx-auto max-w-md px-6 pb-16">
            <h1 class="mt-8 text-3xl font-semibold leading-tight text-charcoal-900">
                {{ t('wallet.create.heading') }}
            </h1>
            <p class="mt-3 text-sm leading-relaxed text-charcoal-600">
                {{ t('wallet.create.subheading') }}
            </p>

            <Form
                v-slot="{ errors, processing }"
                action="/wallet"
                method="post"
                class="mt-8 space-y-5"
            >
                <div class="space-y-2">
                    <Label for="phone">{{ t('wallet.create.phone_label') }}</Label>
                    <Input
                        id="phone"
                        name="phone"
                        type="tel"
                        inputmode="tel"
                        autocomplete="tel"
                        :placeholder="t('wallet.create.phone_placeholder')"
                        :invalid="fieldError(errors, 'phone', 'wallet').invalid"
                        :described-by="
                            fieldError(errors, 'phone', 'wallet').describedBy
                        "
                        required
                    />
                    <FieldError v-bind="fieldError(errors, 'phone', 'wallet')" />
                </div>

                <div class="space-y-2">
                    <Label for="first_name">{{ t('wallet.create.first_name_label') }}</Label>
                    <Input
                        id="first_name"
                        name="first_name"
                        type="text"
                        autocomplete="given-name"
                        :placeholder="t('wallet.create.first_name_placeholder')"
                        :invalid="fieldError(errors, 'first_name', 'wallet').invalid"
                        :described-by="
                            fieldError(errors, 'first_name', 'wallet').describedBy
                        "
                        required
                    />
                    <FieldError v-bind="fieldError(errors, 'first_name', 'wallet')" />
                </div>

                <Button
                    type="submit"
                    :disabled="processing"
                    class="w-full justify-center bg-matcha-600 py-4 text-base font-semibold text-white shadow-matcha transition hover:bg-matcha-700"
                >
                    {{ t('wallet.create.submit') }}
                </Button>
            </Form>

            <p class="mt-6 text-center text-xs text-charcoal-500">
                {{ t('wallet.create.privacy') }}
            </p>
        </main>
    </div>
</template>

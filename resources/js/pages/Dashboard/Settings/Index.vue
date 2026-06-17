<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Save, ArrowRight, Printer } from '@lucide/vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Label from '@/components/ui/Label.vue';
import FieldError from '@/components/ui/FieldError.vue';
import { fieldError } from '@/composables/useFieldError';

const { t } = useI18n();

interface Settings {
    cashback_rate: string;
    currency: string;
    program_name: string;
    store_name: string;
    program_mode: 'cashback' | 'stamps';
    stamps_per_reward: string;
    stamps_per_reward_label: string;
}

const props = defineProps<{
    settings: Settings;
}>();

const form = useForm({
    cashback_rate: props.settings.cashback_rate,
    currency: props.settings.currency,
    program_name: props.settings.program_name,
    store_name: props.settings.store_name,
    program_mode: props.settings.program_mode,
    stamps_per_reward: props.settings.stamps_per_reward,
    stamps_per_reward_label: props.settings.stamps_per_reward_label,
});

function submit(): void {
    form.post('/settings', {
        preserveScroll: true,
    });
}

const isCashback = computed(() => form.program_mode === 'cashback');
const isStamps = computed(() => form.program_mode === 'stamps');
</script>

<template>
    <Head :title="t('dashboard.settings.index.title')" />

    <AdminLayout :title="t('dashboard.settings.index.title')">
        <div class="space-y-6">
            <header>
                <h1 class="heading-2">
                    {{ t('dashboard.settings.index.heading') }}
                </h1>
                <p class="mt-1 text-sm text-on-surface-variant">
                    {{ t('dashboard.settings.index.subheading') }}
                </p>
            </header>

            <form
                class="surface-card space-y-6 p-5"
                @submit.prevent="submit"
            >
                <!-- Program mode toggle -->
                <fieldset class="space-y-3">
                    <legend class="block">
                        <span class="font-sans text-xs font-semibold uppercase tracking-wider text-on-surface-variant">
                            {{ t('settings.program_mode_label') }}
                        </span>
                    </legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                            :class="isCashback ? 'border-primary bg-primary-soft' : 'border-outline-glass bg-surface-container-lowest hover:border-primary'"
                        >
                            <input
                                v-model="form.program_mode"
                                type="radio"
                                value="cashback"
                                name="program_mode"
                                class="mt-1 accent-primary"
                            >
                            <div>
                                <p class="text-sm font-semibold text-on-surface">
                                    {{ t('settings.mode_cashback') }}
                                </p>
                                <p class="mt-1 text-xs text-on-surface-variant">
                                    {{ t('settings.mode_cashback_help') }}
                                </p>
                            </div>
                        </label>
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                            :class="isStamps ? 'border-primary bg-primary-soft' : 'border-outline-glass bg-surface-container-lowest hover:border-primary'"
                        >
                            <input
                                v-model="form.program_mode"
                                type="radio"
                                value="stamps"
                                name="program_mode"
                                class="mt-1 accent-primary"
                            >
                            <div>
                                <p class="text-sm font-semibold text-on-surface">
                                    {{ t('settings.mode_stamps') }}
                                </p>
                                <p class="mt-1 text-xs text-on-surface-variant">
                                    {{ t('settings.mode_stamps_help') }}
                                </p>
                            </div>
                        </label>
                    </div>
                    <FieldError v-bind="fieldError(form.errors, 'program_mode', 'settings')" />
                    <p class="label-help">{{ t('settings.program_mode_help') }}</p>
                </fieldset>

                <!-- Cashback-mode fields -->
                <div
                    v-if="isCashback"
                    class="grid gap-5 sm:grid-cols-2"
                >
                    <div class="space-y-2">
                        <Label for="cashback_rate" required>
                            {{ t('dashboard.settings.index.cashback_rate_label') }}
                        </Label>
                        <Input
                            id="cashback_rate"
                            v-model="form.cashback_rate"
                            type="number"
                            inputmode="decimal"
                            step="0.01"
                            min="0"
                            max="100"
                            :invalid="fieldError(form.errors, 'cashback_rate', 'settings').invalid"
                            :described-by="fieldError(form.errors, 'cashback_rate', 'settings').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('dashboard.settings.index.cashback_rate_help') }}
                        </p>
                        <FieldError v-bind="fieldError(form.errors, 'cashback_rate', 'settings')" />
                    </div>

                    <div class="space-y-2">
                        <Label for="currency" required>
                            {{ t('dashboard.settings.index.currency_label') }}
                        </Label>
                        <Input
                            id="currency"
                            v-model="form.currency"
                            type="text"
                            maxlength="3"
                            :placeholder="t('dashboard.settings.index.currency_label')"
                            :invalid="fieldError(form.errors, 'currency', 'settings').invalid"
                            :described-by="fieldError(form.errors, 'currency', 'settings').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('dashboard.settings.index.currency_help') }}
                        </p>
                        <FieldError v-bind="fieldError(form.errors, 'currency', 'settings')" />
                    </div>
                </div>

                <!-- Stamps-mode fields -->
                <div
                    v-if="isStamps"
                    class="grid gap-5 sm:grid-cols-2"
                >
                    <div class="space-y-2">
                        <Label for="stamps_per_reward" required>
                            {{ t('settings.stamps_per_reward_label') }}
                        </Label>
                        <Input
                            id="stamps_per_reward"
                            v-model="form.stamps_per_reward"
                            type="number"
                            inputmode="numeric"
                            step="1"
                            min="1"
                            max="1000"
                            :invalid="fieldError(form.errors, 'stamps_per_reward', 'settings').invalid"
                            :described-by="fieldError(form.errors, 'stamps_per_reward', 'settings').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('settings.stamps_per_reward_help') }}
                        </p>
                        <FieldError v-bind="fieldError(form.errors, 'stamps_per_reward', 'settings')" />
                    </div>

                    <div class="space-y-2">
                        <Label for="stamps_per_reward_label" required>
                            {{ t('settings.stamps_per_reward_label_label') }}
                        </Label>
                        <Input
                            id="stamps_per_reward_label"
                            v-model="form.stamps_per_reward_label"
                            type="text"
                            maxlength="64"
                            :placeholder="t('settings.stamps_per_reward_label_label')"
                            :invalid="fieldError(form.errors, 'stamps_per_reward_label', 'settings').invalid"
                            :described-by="fieldError(form.errors, 'stamps_per_reward_label', 'settings').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('settings.stamps_per_reward_label_help') }}
                        </p>
                        <FieldError v-bind="fieldError(form.errors, 'stamps_per_reward_label', 'settings')" />
                    </div>
                </div>

                <!-- Common fields -->
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="program_name" required>
                            {{ t('dashboard.settings.index.program_name_label') }}
                        </Label>
                        <Input
                            id="program_name"
                            v-model="form.program_name"
                            type="text"
                            :placeholder="t('dashboard.settings.index.program_name_label')"
                            :invalid="fieldError(form.errors, 'program_name', 'settings').invalid"
                            :described-by="fieldError(form.errors, 'program_name', 'settings').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('dashboard.settings.index.program_name_help') }}
                        </p>
                        <FieldError v-bind="fieldError(form.errors, 'program_name', 'settings')" />
                    </div>

                    <div class="space-y-2">
                        <Label for="store_name" required>
                            {{ t('dashboard.settings.index.store_name_label') }}
                        </Label>
                        <Input
                            id="store_name"
                            v-model="form.store_name"
                            type="text"
                            :placeholder="t('dashboard.settings.index.store_name_label')"
                            :invalid="fieldError(form.errors, 'store_name', 'settings').invalid"
                            :described-by="fieldError(form.errors, 'store_name', 'settings').describedBy"
                            required
                        />
                        <p class="label-help">
                            {{ t('dashboard.settings.index.store_name_help') }}
                        </p>
                        <FieldError v-bind="fieldError(form.errors, 'store_name', 'settings')" />
                    </div>
                </div>

                <div class="pt-2">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        <Save :size="14" />
                        {{ t('dashboard.settings.index.submit') }}
                    </Button>
                </div>
            </form>

            <Link
                href="/store-qr"
                class="surface-card group flex items-center justify-between gap-4 p-5 transition hover:border-primary"
            >
                <div>
                    <h2 class="heading-3">
                        {{ t('dashboard.store_qr.title') }}
                    </h2>
                    <p class="mt-1 text-xs text-on-surface-variant">
                        {{ t('dashboard.store_qr.headline') }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-on-primary transition group-hover:bg-primary-container">
                    <Printer :size="14" />
                    {{ t('dashboard.store_qr.print') }}
                    <ArrowRight :size="14" />
                </span>
            </Link>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const model = defineModel<string | number | null>();

const props = withDefaults(
    defineProps<{
        id?: string;
        name?: string;
        autocomplete?: string;
        required?: boolean;
        class?: string;
        options: Array<{ value: string; label: string }>;
        placeholder?: string;
        defaultValue?: string;
        invalid?: boolean;
        describedBy?: string;
    }>(),
    {
        id: undefined,
        name: undefined,
        autocomplete: undefined,
        required: false,
        class: '',
        placeholder: undefined,
        defaultValue: undefined,
        invalid: false,
        describedBy: undefined,
    },
);

const selectId = computed(
    () => props.id ?? `select-${Math.random().toString(36).slice(2, 9)}`,
);
</script>

<template>
    <select
        :id="selectId"
        v-model="model"
        :name="props.name"
        :autocomplete="props.autocomplete"
        :required="props.required"
        :aria-invalid="props.invalid ? 'true' : undefined"
        :aria-describedby="props.describedBy"
        :class="
            cn(
                'h-10 w-full rounded-md border bg-white px-3 text-sm text-gray-950 outline-none transition focus:ring-2 focus:ring-blue-100',
                props.invalid
                    ? 'border-red-700 focus:border-red-700'
                    : 'border-gray-300 focus:border-blue-700',
                props.class,
            )
        "
    >
        <option v-if="props.placeholder" value="">
            {{ props.placeholder }}
        </option>
        <option
            v-for="option in props.options"
            :key="option.value"
            :value="option.value"
            :selected="props.defaultValue === option.value"
        >
            {{ option.label }}
        </option>
    </select>
</template>

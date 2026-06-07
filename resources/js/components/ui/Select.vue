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
    }>(),
    {
        id: undefined,
        name: undefined,
        autocomplete: undefined,
        required: false,
        class: '',
        placeholder: undefined,
        defaultValue: undefined,
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
        :class="
            cn(
                'h-10 w-full rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-950 outline-none transition focus:border-blue-700 focus:ring-2 focus:ring-blue-100',
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

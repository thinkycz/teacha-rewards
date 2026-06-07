<script setup lang="ts">
import { cn } from '@/lib/utils';

const model = defineModel<string | number | null>();

withDefaults(
    defineProps<{
        id?: string;
        name?: string;
        type?: string;
        autocomplete?: string;
        placeholder?: string;
        class?: string;
        required?: boolean;
        defaultValue?: string;
        invalid?: boolean;
        describedBy?: string;
    }>(),
    {
        id: undefined,
        name: undefined,
        type: 'text',
        autocomplete: undefined,
        placeholder: undefined,
        class: '',
        required: false,
        defaultValue: undefined,
        invalid: false,
        describedBy: undefined,
    },
);
</script>

<template>
    <input
        :id="$props.id"
        :value="model ?? $props.defaultValue"
        :name="$props.name"
        :type="$props.type"
        :autocomplete="$props.autocomplete"
        :placeholder="$props.placeholder"
        :required="$props.required"
        :aria-invalid="$props.invalid ? 'true' : undefined"
        :aria-describedby="$props.describedBy"
        :class="
            cn(
                'h-10 w-full rounded-md border bg-white px-3 text-sm text-gray-950 outline-none transition placeholder:text-gray-400 focus:ring-2 focus:ring-blue-100',
                $props.invalid
                    ? 'border-red-700 focus:border-red-700'
                    : 'border-gray-300 focus:border-blue-700',
                $props.class,
            )
        "
        @input="model = ($event.target as HTMLInputElement).value"
    />
</template>

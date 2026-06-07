<script setup lang="ts">
import { computed, useId } from 'vue';
import FieldError from '@/components/ui/FieldError.vue';
import Label from '@/components/ui/Label.vue';

const props = withDefaults(
    defineProps<{
        label: string;
        error?: string | undefined;
        required?: boolean;
    }>(),
    {
        error: undefined,
        required: false,
    },
);

const generatedId = useId();
const inputId = computed(() => `field-${generatedId}`);
const errorId = computed(() => `${inputId.value}-error`);
</script>

<template>
    <div class="space-y-2">
        <Label :for="inputId" :required="required">{{ label }}</Label>
        <slot
            :id="inputId"
            :described-by="error ? errorId : undefined"
            :invalid="!!error"
        />
        <FieldError :id="errorId" :message="error" />
    </div>
</template>

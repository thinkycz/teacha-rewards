<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import JsBarcode from 'jsbarcode';

const props = withDefaults(
    defineProps<{
        value: string;
        format?: string;
        height?: number;
        displayValue?: boolean;
        fontSize?: number;
        margin?: number;
    }>(),
    {
        format: 'CODE128',
        height: 64,
        displayValue: true,
        fontSize: 12,
        margin: 8,
    },
);

const svgRef = ref<SVGSVGElement | null>(null);
const error = ref<string | null>(null);

function render(): void {
    if (svgRef.value === null) {
        return;
    }
    try {
        JsBarcode(svgRef.value, props.value, {
            format: props.format,
            height: props.height,
            displayValue: props.displayValue,
            fontSize: props.fontSize,
            margin: props.margin,
            background: '#FAF8F1',
            lineColor: '#1f2937',
        });
        error.value = null;
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Barcode render failed';
    }
}

onMounted(render);
watch(
    () => [props.value, props.format, props.height, props.displayValue, props.fontSize, props.margin],
    render,
);
</script>

<template>
    <div class="rounded-xl bg-cream-50 px-3 py-3">
        <svg
            ref="svgRef"
            class="block h-auto w-full"
            role="img"
            :aria-label="value"
        />
        <p
            v-if="error"
            class="mt-2 text-center text-xs text-error-red"
        >
            {{ error }}
        </p>
    </div>
</template>

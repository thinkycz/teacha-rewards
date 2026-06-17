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
        // Default to false: callers typically render a human-readable
        // label (wallet_number) below the bars themselves, and the
        // encoded value (e.g. public_token) is a secret-ish token we
        // don't want printed under the barcode for shoulder-surfing
        // reasons.
        displayValue: false,
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
            background: '#f8fafc',
            lineColor: '#0f172a',
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
    <div class="surface-card bg-surface-container-lowest p-3">
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

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import QRCode from 'qrcode';

const props = withDefaults(
    defineProps<{
        url: string;
        size?: number;
    }>(),
    {
        size: 240,
    },
);

const dataUrl = ref<string>('');
const error = ref<string | null>(null);

async function render(): Promise<void> {
    try {
        dataUrl.value = await QRCode.toDataURL(props.url, {
            errorCorrectionLevel: 'M',
            margin: 1,
            width: props.size,
            color: {
                dark: '#1f2937',
                light: '#FAF8F1',
            },
        });
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'QR render failed';
    }
}

onMounted(() => {
    void render();
});

watch(
    () => props.url,
    () => {
        void render();
    },
);
</script>

<template>
    <div class="flex justify-center">
        <div
            v-if="error"
            class="surface-card border border-error-red bg-error-soft p-4 text-sm text-error-red"
        >
            {{ error }}
        </div>
        <img
            v-else
            :src="dataUrl"
            :alt="url"
            :width="size"
            :height="size"
            class="rounded-2xl border border-outline-glass bg-white p-2"
        />
    </div>
</template>

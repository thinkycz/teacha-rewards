<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = withDefaults(
    defineProps<{
        amount: string;
        size?: 'lg' | 'md' | 'sm';
    }>(),
    { size: 'md' },
);

const numericAmount = computed(() => Number(props.amount));
const formatted = computed(() => {
    return new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numericAmount.value);
});

const valueClass = computed(() => {
    switch (props.size) {
        case 'lg':
            return 'text-3xl font-bold tracking-tight';
        case 'sm':
            return 'text-base font-semibold tracking-tight';
        default:
            return 'text-2xl font-bold tracking-tight';
    }
});
</script>

<template>
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-on-primary/70">
            {{ t('wallet.balance.available') }}
        </p>
        <p
            class="mt-0.5 text-on-primary"
            :class="valueClass"
        >
            {{ formatted }}&nbsp;Kč
        </p>
    </div>
</template>

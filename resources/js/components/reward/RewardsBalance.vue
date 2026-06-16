<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    amount: string;
}>();

const numericAmount = computed(() => Number(props.amount));
const formatted = computed(() => {
    return new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numericAmount.value);
});
</script>

<template>
    <div>
        <p class="text-xs font-medium uppercase tracking-widest text-white/70">
            {{ t('wallet.balance.available') }}
        </p>
        <p class="mt-1 text-4xl font-bold tracking-tight">
            {{ formatted }}&nbsp;Kč
        </p>
        <p class="mt-1 text-xs text-white/70">
            {{ t('wallet.balance.discount') }}
        </p>
    </div>
</template>

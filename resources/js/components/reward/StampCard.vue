<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = withDefaults(
    defineProps<{
        stamps: number;
        total: number;
        rewardLabel?: string;
        compact?: boolean;
    }>(),
    {
        rewardLabel: '',
        compact: false,
    },
);

const filledCount = computed(() => Math.max(0, Math.min(props.stamps, props.total)));
const remaining = computed(() => Math.max(0, props.total - props.stamps));
const isFull = computed(() => filledCount.value >= props.total);

// Build a 1-based array so we can index slots in the template.
const slots = computed(() =>
    Array.from({ length: Math.max(1, props.total) }, (_, i) => ({
        index: i,
        filled: i < filledCount.value,
    })),
);

const columnsClass = computed(() => {
    // Lay the slots out in a roughly square grid: 5 cols up to 10,
    // then scale up. Keeps the card visually compact at any threshold.
    const n = Math.max(1, props.total);
    if (n <= 5) {
        return 'grid-cols-5';
    }
    if (n <= 8) {
        return 'grid-cols-4';
    }
    if (n <= 10) {
        return 'grid-cols-5';
    }
    if (n <= 12) {
        return 'grid-cols-4';
    }
    if (n <= 15) {
        return 'grid-cols-5';
    }
    return 'grid-cols-6';
});

const sizeClass = computed(() =>
    props.compact ? 'h-7 w-7 text-[10px]' : 'h-9 w-9 text-xs',
);
</script>

<template>
    <div>
        <div
            class="grid gap-2"
            :class="columnsClass"
            role="list"
            :aria-label="
                t('wallet.stamps.card_label', {
                    filled: filledCount,
                    total,
                    label: rewardLabel,
                })
            "
        >
            <div
                v-for="slot in slots"
                :key="slot.index"
                role="listitem"
                class="flex items-center justify-center rounded-full border-2 font-bold transition"
                :class="[
                    sizeClass,
                    slot.filled
                        ? 'border-primary bg-primary text-on-primary shadow-[0_2px_6px_rgba(15,23,42,0.18)]'
                        : 'border-outline-glass bg-surface-container-low text-on-surface-variant',
                ]"
            >
                <span aria-hidden="true">
                    {{ slot.filled ? '✓' : '·' }}
                </span>
            </div>
        </div>
        <p
            v-if="rewardLabel"
            class="mt-3 text-center text-xs text-on-surface-variant"
        >
            <span v-if="isFull" class="font-semibold text-primary">
                {{ t('dashboard.wallets.show.card_full', { label: rewardLabel }) }}
            </span>
            <span v-else>
                {{
                    t('dashboard.wallets.show.stamps_until_reward', {
                        remaining,
                        label: rewardLabel,
                    })
                }}
            </span>
        </p>
    </div>
</template>

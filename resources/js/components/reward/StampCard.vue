<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = withDefaults(
    defineProps<{
        stamps: number;
        total: number;
        rewardLabel?: string;
        icon?: string;
        compact?: boolean;
    }>(),
    {
        rewardLabel: '',
        icon: '\u{1F375}', // matcha bowl 🍵
        compact: false,
    },
);

const filledCount = computed(() => Math.max(0, Math.min(props.stamps, props.total)));
const remaining = computed(() => Math.max(0, props.total - props.stamps));
const isFull = computed(() => filledCount.value >= props.total);

const slots = computed(() =>
    Array.from({ length: Math.max(1, props.total) }, (_, i) => ({
        index: i,
        filled: i < filledCount.value,
    })),
);

// Square-ish slot grid. 5 cols up to 10, scale up beyond.
const columnsClass = computed(() => {
    const n = Math.max(1, props.total);
    if (n <= 4) return 'grid-cols-4';
    if (n <= 5) return 'grid-cols-5';
    if (n <= 6) return 'grid-cols-6';
    if (n <= 8) return 'grid-cols-4';
    if (n <= 9) return 'grid-cols-3';
    if (n <= 10) return 'grid-cols-5';
    if (n <= 12) return 'grid-cols-4';
    if (n <= 15) return 'grid-cols-5';
    if (n <= 16) return 'grid-cols-4';
    if (n <= 20) return 'grid-cols-5';
    return 'grid-cols-6';
});

// Tile sizes — generous in the full customer view, smaller in the
// admin sidebar header.
const tileClass = computed(() =>
    props.compact
        ? 'h-11 w-11 text-lg rounded-xl'
        : 'h-16 w-16 sm:h-20 sm:w-20 text-3xl sm:text-4xl rounded-2xl',
);
</script>

<template>
    <div>
        <div
            class="grid gap-2 sm:gap-3"
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
                class="relative flex items-center justify-center border-2 transition"
                :class="[
                    tileClass,
                    slot.filled
                        ? 'border-transparent bg-primary text-on-primary shadow-[0_4px_12px_rgba(15,23,42,0.25)]'
                        : 'border-dashed border-outline-glass bg-surface-container-lowest/60 text-on-surface-variant/40',
                ]"
            >
                <span
                    v-if="slot.filled"
                    aria-hidden="true"
                    class="leading-none select-none drop-shadow-[0_1px_0_rgba(0,0,0,0.15)]"
                >{{ icon }}</span>
                <span
                    v-else
                    aria-hidden="true"
                    class="leading-none select-none"
                >{{ icon }}</span>
                <!-- Inner ring on filled slots so the icon reads as
                     'stamped' rather than just 'in a circle'. -->
                <span
                    v-if="slot.filled"
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-1 rounded-[inherit] border border-white/20"
                />
            </div>
        </div>
        <p
            v-if="rewardLabel"
            class="mt-3 text-center text-xs text-on-surface-variant"
        >
            <span
                v-if="isFull"
                class="inline-flex items-center gap-1 rounded-full bg-primary px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-on-primary shadow-sm"
            >
                <span aria-hidden="true">{{ icon }}</span>
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

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
    }>(),
    {
        rewardLabel: '',
        icon: '\u{1F375}',
    },
);

const filledCount = computed(() =>
    Math.max(0, Math.min(props.stamps, props.total)),
);
const remaining = computed(() => Math.max(0, props.total - props.stamps));
const isFull = computed(() => filledCount.value >= props.total);

const slots = computed(() =>
    Array.from({ length: Math.max(1, props.total) }, (_, i) => ({
        index: i,
        filled: i < filledCount.value,
    })),
);

// Layout: aim for roughly a 2-row grid so the card looks like a
// real loyalty card. Tuned for the business-card aspect ratio.
const layout = computed(() => {
    const n = Math.max(1, props.total);
    if (n <= 5) return { cols: 'grid-cols-5', rows: 1 };
    if (n <= 8) return { cols: 'grid-cols-4', rows: 2 };
    if (n <= 10) return { cols: 'grid-cols-5', rows: 2 };
    if (n <= 12) return { cols: 'grid-cols-4', rows: 3 };
    if (n <= 15) return { cols: 'grid-cols-5', rows: 3 };
    if (n <= 16) return { cols: 'grid-cols-4', rows: 4 };
    if (n <= 20) return { cols: 'grid-cols-5', rows: 4 };
    return { cols: 'grid-cols-6', rows: 4 };
});
</script>

<template>
    <!-- Paper loyalty card. Real-paper aesthetic: cream surface with
         a faint paper grain, business-card aspect ratio (85 x 55mm),
         max-w 26rem. The same dimensions render on the public wallet
         page and the admin wallet detail page; the admin grid wraps
         it in the same `flex justify-center` it uses elsewhere so the
         card sits centered in its column. -->
    <article
        class="paper-card paper-card-full"
        role="group"
        :aria-label="
            t('wallet.stamps.card_label', {
                filled: filledCount,
                total,
                label: rewardLabel,
            })
        "
    >
        <header class="paper-card-brand">
            <span class="brand-name">Teacha Rewards</span>
            <span v-if="rewardLabel" aria-hidden="true" class="brand-sep"
                >·</span
            >
            <span v-if="rewardLabel" class="brand-reward">{{
                rewardLabel
            }}</span>
            <span aria-hidden="true" class="brand-icon">{{ icon }}</span>
        </header>

        <div
            class="paper-card-slots"
            :class="layout.cols"
            :style="{
                gridTemplateRows: `repeat(${layout.rows}, minmax(0, 1fr))`,
            }"
        >
            <div
                v-for="slot in slots"
                :key="slot.index"
                role="img"
                :aria-label="
                    slot.filled
                        ? t('wallet.stamps.filled', { icon })
                        : t('wallet.stamps.empty', { icon })
                "
                class="paper-slot paper-slot-full"
                :class="slot.filled ? 'paper-slot-filled' : 'paper-slot-empty'"
            >
                <span
                    v-if="slot.filled"
                    aria-hidden="true"
                    class="slot-emoji"
                    >{{ icon }}</span
                >
            </div>
        </div>

        <footer class="paper-card-counter">
            <span
                v-if="isFull"
                class="inline-flex items-center gap-1 rounded-full bg-primary px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-on-primary shadow-sm"
            >
                <span aria-hidden="true">{{ icon }}</span>
                {{
                    t('dashboard.wallets.show.card_full', {
                        label: rewardLabel,
                    })
                }}
            </span>
            <span
                v-else
                class="font-mono text-sm tabular-nums text-on-surface-variant"
            >
                {{
                    t('dashboard.wallets.show.stamps_until_reward', {
                        remaining,
                        label: rewardLabel,
                    })
                }}
            </span>
        </footer>
    </article>
</template>

<style scoped>
.paper-card {
    background: #fdfbf6;
    border: 1px solid #e7e2d5;
    border-radius: 0.75rem;
    box-shadow:
        0 1px 2px rgba(15, 23, 42, 0.06),
        0 4px 14px rgba(15, 23, 42, 0.08),
        0 10px 24px -8px rgba(15, 23, 42, 0.06);
    color: #1e293b;
    overflow: hidden;
    position: relative;
}

/* Faint 'paper' grain so the surface doesn't look like a flat
   div. Two stacked SVG noise gradients at very low opacity. */
.paper-card::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.5;
    background-image:
        radial-gradient(
            circle at 25% 30%,
            rgba(15, 23, 42, 0.02) 0,
            transparent 60%
        ),
        radial-gradient(
            circle at 75% 70%,
            rgba(15, 23, 42, 0.025) 0,
            transparent 65%
        );
}

/* Customer card: business-card aspect ratio (85mm x 55mm = 1.545:1),
   centered with a max width so the page doesn't end up with a single
   card stretched edge-to-edge on a wide viewport. */
.paper-card-full {
    width: 100%;
    max-width: 26rem; /* 416px - close to a real card scaled up */
    aspect-ratio: 85 / 55;
    display: flex;
    flex-direction: column;
}

.paper-card-brand {
    background: #0f172a;
    color: #f8fafc;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    flex: 0 0 auto;
}

.brand-name {
    font-weight: 700;
}

.brand-sep {
    opacity: 0.5;
}

.brand-reward {
    color: #cbd5e1;
    text-transform: none;
    letter-spacing: 0;
    font-weight: 500;
}

.brand-icon {
    margin-left: auto;
    font-size: 1rem;
    line-height: 1;
}

.paper-card-slots {
    display: grid;
    gap: 0.5rem;
    padding: 0.875rem;
    flex: 1 1 auto;
    align-items: center;
    align-content: center;
    position: relative;
    z-index: 1;
}

.paper-slot-full {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;
}

.paper-slot-empty {
    border: 1.5px dashed #cbd5e1;
    background: rgba(255, 255, 255, 0.4);
}

.paper-slot-filled {
    background: #0f172a;
    color: #f8fafc;
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.08) inset,
        0 2px 6px rgba(15, 23, 42, 0.18);
    /* A tiny tilt so filled slots read as 'stamped' rather than
       'placed in a grid'. */
    transform: rotate(-2deg);
}

.paper-slot-filled:hover {
    transform: rotate(0deg) scale(1.04);
}

.slot-emoji {
    /* Emoji glyph rendering is browser-dependent; nudge the size up
       a hair on the larger customer tile so the matcha bowl fills
       the disc rather than floating in the middle. */
    font-size: 1.65rem;
    line-height: 1;
}

.paper-card-counter {
    text-align: center;
    padding: 0.5rem 0.75rem 0.75rem;
    position: relative;
    z-index: 1;
}
</style>

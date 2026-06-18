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

// Layout + slot sizing.
//
// For a loyalty card, the user-facing constraint is that the
// "X more stamps to Y" footer must always be visible. With a fixed
// aspect ratio that breaks down past ~10 stamps (the slots grid
// eats the available height and the footer overflows). So:
//
//   1. The card grows vertically with the number of rows
//      (`min-height` keeps it from collapsing for very small
//      counts).
//   2. Each tier shrinks the slot side, the slots padding, and
//      the emoji font-size so the card doesn't grow taller than
//      it has to. The numbers were tuned by hand to look good
//      across 1–36 stamps while keeping the stamp card "card-
//      sized" and the footer always rendered below the grid.
const layout = computed(() => {
    const n = Math.max(1, props.total);
    if (n <= 5) {
        return { cols: 5, rows: 1, slotSize: '3.5rem', emoji: 1.65, pad: 0.875 };
    }
    if (n <= 8) {
        return { cols: 4, rows: 2, slotSize: '3rem', emoji: 1.4, pad: 0.7 };
    }
    if (n <= 10) {
        return { cols: 5, rows: 2, slotSize: '2.75rem', emoji: 1.25, pad: 0.6 };
    }
    if (n <= 12) {
        return { cols: 4, rows: 3, slotSize: '2.5rem', emoji: 1.1, pad: 0.5 };
    }
    if (n <= 15) {
        return { cols: 5, rows: 3, slotSize: '2.2rem', emoji: 1.0, pad: 0.45 };
    }
    if (n <= 20) {
        return { cols: 5, rows: 4, slotSize: '1.9rem', emoji: 0.85, pad: 0.4 };
    }
    if (n <= 25) {
        return { cols: 5, rows: 5, slotSize: '1.65rem', emoji: 0.75, pad: 0.35 };
    }
    if (n <= 30) {
        return { cols: 6, rows: 5, slotSize: '1.5rem', emoji: 0.7, pad: 0.3 };
    }
    return { cols: 6, rows: 6, slotSize: '1.4rem', emoji: 0.65, pad: 0.3 };
});
</script>

<template>
    <!-- Paper loyalty card. Cream surface with a faint paper grain,
         max-w 26rem. The card grows vertically past its baseline
         aspect ratio for higher stamp counts so the "X more
         stamps to Y" footer stays visible. The same dimensions
         render on the public wallet page and the admin wallet
         detail page; the admin grid wraps it in `flex justify-center`
         so the card sits centered in its column. -->
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
            :class="`grid-cols-${layout.cols}`"
            :style="{
                gridTemplateRows: `repeat(${layout.rows}, minmax(0, 1fr))`,
                '--slot-pad': `${layout.pad}rem`,
                '--slot-emoji': `${layout.emoji}rem`,
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

/* Customer card: business-card-style aesthetic (close to 85mm x
   55mm for the common 5- or 10-stamp case), but the card grows
   vertically past that point so the footer is always visible.
   `min-height` keeps the card from collapsing for very small
   stamp counts; the per-tier `slotSize` in the script keeps the
   card from ballooning for high counts. */
.paper-card-full {
    width: 100%;
    max-width: 26rem; /* 416px - close to a real card scaled up */
    min-height: 14rem;
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
    gap: 0.4rem;
    padding: var(--slot-pad, 0.875rem);
    flex: 1 1 auto;
    align-items: center;
    align-content: center;
    position: relative;
    z-index: 1;
}

.paper-slot-full {
    width: var(--slot-size, 100%);
    aspect-ratio: 1;
    place-self: center;
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
    /* Emoji glyph rendering is browser-dependent; the per-tier
       `--slot-emoji` from the layout computed keeps the glyph
       proportional to the disc size (1.65rem for the 1-5 stamp
       card, ~0.65rem for the 30+ stamp case). */
    font-size: var(--slot-emoji, 1.65rem);
    line-height: 1;
}

.paper-card-counter {
    text-align: center;
    padding: 0.5rem 0.75rem 0.75rem;
    position: relative;
    z-index: 1;
}
</style>

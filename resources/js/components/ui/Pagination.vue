<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { cn } from '@/lib/utils';

const { t } = useI18n();

/**
 * Matches Laravel's `LengthAwarePaginator::toArray()` shape
 * (which is what Inertia's adapter serializes).
 */
type Paginator = {
    current_page: number;
    data: unknown[];
    first_page_url: string | null;
    from: number | null;
    last_page: number;
    last_page_url: string | null;
    links: Array<{
        url: string | null;
        label: string;
        page: number | null;
        active: boolean;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

const props = withDefaults(
    defineProps<{
        paginator: Paginator;
        class?: string;
    }>(),
    { class: '' },
);

const pageNumbers = computed<Array<number | 'ellipsis'>>(() => {
    const current = props.paginator.current_page;
    const last = props.paginator.last_page;
    if (last <= 1) {
        return [];
    }
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }
    if (current <= 4) {
        return [1, 2, 3, 4, 5, 'ellipsis', last];
    }
    if (current >= last - 3) {
        return [1, 'ellipsis', last - 4, last - 3, last - 2, last - 1, last];
    }
    return [1, 'ellipsis', current - 1, current, current + 1, 'ellipsis', last];
});

const from = computed<number>(() => props.paginator.from ?? 0);
const to = computed<number>(() => props.paginator.to ?? 0);
</script>

<template>
    <nav
        :class="
            cn(
                'flex flex-col items-start gap-3 border-t border-outline-glass pt-4 sm:flex-row sm:items-center sm:justify-between',
                props.class,
            )
        "
        :aria-label="t('common.pagination.label')"
    >
        <p class="text-xs text-on-surface-variant">
            {{
                t('common.pagination.showing', {
                    from,
                    to,
                    total: paginator.total,
                })
            }}
        </p>
        <ul
            v-if="paginator.last_page > 1"
            class="flex flex-wrap items-center gap-1 text-sm"
        >
            <li>
                <a
                    v-if="paginator.prev_page_url"
                    :href="paginator.prev_page_url"
                    rel="prev"
                    class="inline-flex h-9 items-center rounded-lg border border-outline-glass px-3 font-medium text-on-surface transition hover:border-primary hover:bg-primary/5"
                >
                    {{ t('common.pagination.previous') }}
                </a>
                <span
                    v-else
                    aria-disabled="true"
                    class="inline-flex h-9 items-center rounded-lg border border-outline-glass px-3 font-medium text-on-surface-variant opacity-40"
                >
                    {{ t('common.pagination.previous') }}
                </span>
            </li>
            <li v-for="(page, i) in pageNumbers" :key="i">
                <span
                    v-if="page === 'ellipsis'"
                    aria-hidden="true"
                    class="px-2 text-on-surface-variant/60"
                    >…</span
                >
                <span
                    v-else-if="page === paginator.current_page"
                    aria-current="page"
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-primary px-2 font-semibold text-on-primary"
                    >{{ page }}</span
                >
                <a
                    v-else
                    :href="`?page=${page}`"
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-outline-glass px-2 font-medium text-on-surface transition hover:border-primary hover:bg-primary/5"
                    >{{ page }}</a
                >
            </li>
            <li>
                <a
                    v-if="paginator.next_page_url"
                    :href="paginator.next_page_url"
                    rel="next"
                    class="inline-flex h-9 items-center rounded-lg border border-outline-glass px-3 font-medium text-on-surface transition hover:border-primary hover:bg-primary/5"
                >
                    {{ t('common.pagination.next') }}
                </a>
                <span
                    v-else
                    aria-disabled="true"
                    class="inline-flex h-9 items-center rounded-lg border border-outline-glass px-3 font-medium text-on-surface-variant opacity-40"
                >
                    {{ t('common.pagination.next') }}
                </span>
            </li>
        </ul>
    </nav>
</template>

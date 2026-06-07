<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, KeyRound, UserRound } from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useSharedProps } from '@/composables/useSharedProps';

const { auth } = useSharedProps();
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="grid gap-4 md:grid-cols-3">
            <section
                class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
            >
                <UserRound class="mb-4 size-5 text-blue-700" />
                <h2 class="text-base font-semibold text-gray-950">
                    Authenticated user
                </h2>
                <p class="mt-2 text-sm text-gray-600">{{ auth.user?.email }}</p>
            </section>

            <section
                class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
            >
                <CheckCircle2 class="mb-4 size-5 text-emerald-700" />
                <h2 class="text-base font-semibold text-gray-950">
                    Token cookie guard
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    The app is using the reference database-token cookie guard.
                </p>
            </section>

            <section
                class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
            >
                <KeyRound class="mb-4 size-5 text-gray-700" />
                <h2 class="text-base font-semibold text-gray-950">Settings</h2>
                <Link
                    href="/settings/profile"
                    class="mt-2 inline-block text-sm font-medium text-blue-700"
                >
                    Manage profile
                </Link>
            </section>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import NavBar from '@/components/welcome/NavBar.vue';
import Footer from '@/components/welcome/Footer.vue';
import CookieBanner from '@/components/welcome/CookieBanner.vue';
import { type BreadcrumbItem } from '@/types';

/**
 * Renders public pages inside the app shell (sidebar) when the user is
 * authenticated, and inside the public marketing shell otherwise.
 */
withDefaults(
    defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(),
    { breadcrumbs: () => [] },
);

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <AppLayout v-if="isAuthenticated" :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>

    <div v-else class="flex min-h-screen flex-col bg-background">
        <NavBar :can-register="true" />
        <main class="flex-1">
            <slot />
        </main>
        <Footer />
        <CookieBanner />
    </div>
</template>

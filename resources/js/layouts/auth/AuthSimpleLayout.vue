<script setup lang="ts">
import { dashboard, home } from '@/routes';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const backUrl = computed(() => isAuthenticated.value ? dashboard.url() : home.url());
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <!-- Bouton retour en haut à gauche -->
        <Link
            :href="backUrl"
            class="absolute left-6 top-6 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground md:left-10 md:top-10"
        >
            <ArrowLeft class="h-4 w-4" />
            Retour à l'accueil
        </Link>

        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                        <Link
                            :href="backUrl"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-16 w-16 items-center justify-center rounded-md"
                        >
                            <img
                                src="/assets/logo_fitnessclic.png"
                                alt="FitnessClic Logo"
                                class="h-full w-full object-contain"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

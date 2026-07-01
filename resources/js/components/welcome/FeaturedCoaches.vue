<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import CoachCard from '@/components/CoachCard.vue';
import type { Coach } from '@/types/coach';

defineProps<{ coaches: Coach[] }>();
</script>

<template>
    <section v-if="coaches.length" class="bg-white py-20 sm:py-24 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-10 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <span class="text-sm font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                        Nos coachs à la une
                    </span>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                        Des pros près de chez vous
                    </h2>
                    <p class="mt-2 max-w-xl text-gray-600 dark:text-gray-300">
                        Parcourez les profils, comparez les spécialités et les tarifs, et contactez
                        celui qui correspond vraiment à votre objectif.
                    </p>
                </div>
                <Link
                    href="/coachs"
                    class="inline-flex shrink-0 items-center gap-1.5 font-semibold text-blue-600 transition hover:gap-2.5 dark:text-blue-400"
                >
                    Voir tous les coachs
                    <ArrowRight class="size-4" />
                </Link>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <CoachCard v-for="coach in coaches" :key="coach.slug" :coach="coach" />
            </div>
        </div>
    </section>
</template>

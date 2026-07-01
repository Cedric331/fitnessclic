<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Dumbbell, Flame, Sparkles, Activity, Footprints, Zap, Target, HeartPulse } from 'lucide-vue-next';

const disciplines = [
    { name: 'Musculation', icon: Dumbbell },
    { name: 'Perte de poids', icon: Flame },
    { name: 'Yoga & Pilates', icon: Sparkles },
    { name: 'Cardio & HIIT', icon: Activity },
    { name: 'Course à pied', icon: Footprints },
    { name: 'Boxe', icon: Zap },
    { name: 'Préparation physique', icon: Target },
    { name: 'Remise en forme', icon: HeartPulse },
];
</script>

<template>
    <section class="bg-gray-50 py-20 sm:py-24 dark:bg-gray-950">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-10 text-center">
                <span class="text-sm font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                    Toutes les disciplines
                </span>
                <h2 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    Quel que soit votre objectif
                </h2>
                <p class="mx-auto mt-2 max-w-xl text-gray-600 dark:text-gray-300">
                    Du renforcement musculaire à la relaxation, trouvez un spécialiste de votre pratique.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <Link
                    v-for="d in disciplines"
                    :key="d.name"
                    :href="`/coachs?q=${encodeURIComponent(d.name)}`"
                    class="group flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition hover:-translate-y-1 hover:border-transparent hover:shadow-lg dark:border-gray-800 dark:bg-gray-900"
                >
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-100 to-purple-100 text-blue-600 dark:from-blue-900/40 dark:to-purple-900/40 dark:text-blue-400">
                        <component :is="d.icon" class="size-5" />
                    </span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ d.name }}</span>
                </Link>
            </div>
        </div>
    </section>
</template>

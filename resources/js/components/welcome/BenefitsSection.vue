<script setup lang="ts">
import { Check } from 'lucide-vue-next';

const benefits = [
    'Interface drag & drop ultra-intuitive',
    'Export et impression des séances',
    'Personnalisation complète',
    'Bibliothèque d\'exercices illimitée',
    'Catégorisation intelligente',
    'Accessible partout, tout le temps',
];
</script>

<template>
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 py-24 dark:from-blue-800 dark:via-purple-800 dark:to-pink-800">
        <!-- Éléments décoratifs -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute left-0 top-0 h-64 w-64 rounded-full bg-white blur-3xl"></div>
            <div class="absolute right-0 bottom-0 h-64 w-64 rounded-full bg-white blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-4xl font-bold tracking-tight text-white sm:text-5xl md:text-6xl">
                    Pourquoi choisir FitnessClic ?
                </h2>
                <p class="mt-6 text-xl leading-8 text-blue-100">
                    Une solution complète pour optimiser votre travail et gagner du temps
                </p>
            </div>

            <div class="mx-auto mt-20 grid max-w-4xl grid-cols-1 gap-6 sm:mt-24 lg:mx-0 lg:max-w-none lg:grid-cols-2">
                <div
                    v-for="(benefit, index) in benefits"
                    :key="index"
                    class="group flex items-center gap-4 rounded-2xl bg-white/10 px-8 py-6 backdrop-blur-md transition-all duration-300 hover:scale-105 hover:bg-white/20 hover:shadow-xl"
                    :style="{ animationDelay: `${index * 100}ms` }"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20 shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6">
                        <Check class="h-6 w-6 text-white" />
                    </div>
                    <span class="text-lg font-semibold text-white">{{ benefit }}</span>
                </div>
            </div>

            <!-- Section supplémentaire avec témoignage -->
            <div class="mx-auto mt-16 max-w-3xl">
                <div class="rounded-2xl bg-white/10 p-8 backdrop-blur-md">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-2xl font-bold text-white">
                            "
                        </div>
                        <div class="flex-1">
                            <p class="text-lg italic text-white">
                                FitnessClic a transformé ma façon de travailler. Je gagne plusieurs heures par semaine et mes clients apprécient la qualité professionnelle des séances.
                            </p>
                            <p class="mt-4 text-sm font-semibold text-blue-100">
                                — Coach professionnel
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>


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
    <section class="bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 py-20 dark:from-blue-800 dark:via-purple-800 dark:to-pink-800">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    Pourquoi choisir FitnessClic ?
                </h2>
                <p class="mt-4 text-lg leading-8 text-blue-100">
                    Une solution complète pour optimiser votre travail
                </p>
            </div>

            <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-4 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-2">
                <div
                    v-for="(benefit, index) in benefits"
                    :key="index"
                    class="flex items-center gap-3 rounded-full bg-white/10 px-6 py-4 backdrop-blur-sm transition-all hover:bg-white/20"
                >
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/20">
                        <Check class="h-4 w-4 text-white" />
                    </div>
                    <span class="text-base font-medium text-white">{{ benefit }}</span>
                </div>
            </div>
        </div>
    </section>
</template>


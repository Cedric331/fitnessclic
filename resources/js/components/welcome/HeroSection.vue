<script setup lang="ts">
import { register } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
</script>

<template>
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 py-20 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <!-- Badge -->
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/80 px-4 py-2 text-sm font-medium text-blue-700 backdrop-blur-sm dark:border-blue-800 dark:bg-gray-800/80 dark:text-blue-300">
                    <Star class="h-4 w-4 fill-blue-600 text-blue-600 dark:fill-blue-400 dark:text-blue-400" />
                    <span>Créateur de séances professionnel</span>
                </div>

                <!-- Titre principal -->
                <h1 class="mb-6 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl md:text-6xl dark:text-white">
                    Créez vos séances
                    <span class="text-purple-600 dark:text-purple-400">en quelques clics</span>
                </h1>

                <!-- Description -->
                <p class="mb-8 text-lg leading-8 text-gray-600 sm:text-xl dark:text-gray-300">
                    L'outil professionnel pour les coachs sportifs ou les particuliers qui souhaitent créer, organiser et partager leurs programmes d'entraînement de manière simple et efficace.
                </p>

                <!-- Boutons CTA -->
                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Link
                        :href="register()"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-8 py-3 text-base font-semibold text-white transition-all hover:bg-blue-700 hover:shadow-lg dark:bg-blue-500 dark:hover:bg-blue-600"
                    >
                        Commencer gratuitement
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border-2 border-gray-300 bg-white px-8 py-3 text-base font-semibold text-gray-700 transition-all hover:border-gray-400 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-500 dark:hover:bg-gray-700"
                    >
                        Voir une démo
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>


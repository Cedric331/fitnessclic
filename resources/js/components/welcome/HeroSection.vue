<script setup lang="ts">
import { register } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
</script>

<style scoped>
@keyframes gradient {
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 3s ease infinite;
}
</style>

<template>
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 py-24 sm:py-32 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <!-- Éléments décoratifs animés -->
        <div class="absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute -left-1/2 top-0 h-[500px] w-[500px] rounded-full bg-blue-400/20 blur-3xl animate-pulse"></div>
            <div class="absolute -right-1/2 bottom-0 h-[500px] w-[500px] rounded-full bg-purple-400/20 blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-4xl text-center">
                <!-- Badge avec animation -->
                <div class="mb-8 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 backdrop-blur-md shadow-sm transition-all hover:scale-105 hover:shadow-md dark:border-blue-800 dark:bg-gray-800/90 dark:text-blue-300">
                    <Star class="h-4 w-4 fill-blue-600 text-blue-600 dark:fill-blue-400 dark:text-blue-400 animate-pulse" />
                    <span>Créateur de séances professionnel</span>
                </div>

                <!-- Titre principal avec animation -->
                <h1 class="mb-8 text-5xl font-extrabold tracking-tight text-gray-900 sm:text-6xl md:text-7xl dark:text-white">
                    <span class="block">Créez vos séances</span>
                    <span class="block bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 bg-clip-text text-transparent animate-gradient dark:from-purple-400 dark:via-pink-400 dark:to-blue-400">
                        en quelques clics
                    </span>
                </h1>

                <!-- Description améliorée -->
                <p class="mb-12 text-xl leading-8 text-gray-600 sm:text-2xl dark:text-gray-300 max-w-3xl mx-auto">
                    L'outil professionnel pour les <span class="font-semibold text-gray-900 dark:text-white">coachs sportifs, kinésithérapeutes, éducateurs d'activités physique adaptés et salle de remise en forme</span> ou les <span class="font-semibold text-gray-900 dark:text-white">particuliers</span> qui souhaitent créer, organiser et partager leurs programmes d'entraînement de manière simple et efficace.
                </p>

                <!-- Statistiques -->
                <div class="mb-12 grid grid-cols-1 gap-8 sm:grid-cols-3">
                    <div class="rounded-xl bg-white/60 p-6 backdrop-blur-sm shadow-sm dark:bg-gray-800/60">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">0€</div>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">Compte gratuit</div>
                    </div>
                    <div class="rounded-xl bg-white/60 p-6 backdrop-blur-sm shadow-sm dark:bg-gray-800/60">
                        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">5€</div>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">Premium / mois</div>
                    </div>
                    <div class="rounded-xl bg-white/60 p-6 backdrop-blur-sm shadow-sm dark:bg-gray-800/60">
                        <div class="text-3xl font-bold text-pink-600 dark:text-pink-400">5 min</div>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">Pour créer une séance</div>
                    </div>
                </div>

                <!-- Boutons CTA améliorés -->
                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Link
                        :href="register.url()"
                        class="group inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-10 py-4 text-base font-semibold text-white shadow-lg transition-all hover:scale-105 hover:shadow-xl hover:from-blue-700 hover:to-purple-700 dark:from-blue-500 dark:to-purple-500 dark:hover:from-blue-600 dark:hover:to-purple-600"
                    >
                        Commencer gratuitement
                        <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <!-- <button
                        type="button"
                        class="inline-flex items-center rounded-xl border-2 border-gray-300 bg-white px-10 py-4 text-base font-semibold text-gray-700 transition-all hover:scale-105 hover:border-gray-400 hover:bg-gray-50 hover:shadow-lg dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-500 dark:hover:bg-gray-700"
                    >
                        Voir une démo
                    </button> -->
                </div>
            </div>
        </div>
    </section>
</template>


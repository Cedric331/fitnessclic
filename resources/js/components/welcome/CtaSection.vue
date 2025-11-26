<script setup lang="ts">
import { register } from '@/routes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <section class="relative overflow-hidden bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-24 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <!-- Éléments décoratifs -->
        <div class="absolute inset-0 -z-10">
            <div class="absolute left-1/4 top-0 h-96 w-96 rounded-full bg-blue-400/10 blur-3xl"></div>
            <div class="absolute right-1/4 bottom-0 h-96 w-96 rounded-full bg-purple-400/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-4xl">
                <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white/80 p-12 shadow-2xl backdrop-blur-sm dark:border-gray-700 dark:bg-gray-800/80 sm:p-16">
                    <div class="text-center">
                        <!-- Badge -->
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                            <span>✨</span>
                            <span>Rejoignez-nous dès aujourd'hui</span>
                        </div>

                        <!-- Titre -->
                        <h2 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl md:text-6xl dark:text-white">
                            Prêt à transformer votre façon de travailler ?
                        </h2>
                        
                        <!-- Description -->
                        <p class="mx-auto mt-6 max-w-2xl text-xl leading-8 text-gray-600 dark:text-gray-300">
                            Rejoignez les <span class="font-semibold text-gray-900 dark:text-white">centaines de coachs</span> qui ont déjà simplifié la création de leurs séances et font gagner du temps à leurs clients.
                        </p>

                        <!-- Points de vente -->
                        <div class="mx-auto mt-10 grid max-w-2xl grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="flex flex-col items-center rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                                <div class="text-2xl mb-2">🚀</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">Démarrage rapide</div>
                            </div>
                            <div class="flex flex-col items-center rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                                <div class="text-2xl mb-2">💎</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">Gratuit ou 5€/mois</div>
                            </div>
                            <div class="flex flex-col items-center rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                                <div class="text-2xl mb-2">⚡</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">Sans engagement</div>
                            </div>
                        </div>

                        <!-- Bouton CTA principal -->
                        <div class="mt-12">
                            <Link
                                :href="register.url()"
                                class="group inline-flex items-center gap-3 rounded-xl bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 px-12 py-5 text-lg font-bold text-white shadow-xl transition-all hover:scale-105 hover:shadow-2xl hover:from-blue-700 hover:via-purple-700 hover:to-pink-700 dark:from-blue-500 dark:via-purple-500 dark:to-pink-500 dark:hover:from-blue-600 dark:hover:via-purple-600 dark:hover:to-pink-600"
                            >
                                Créer mon compte gratuitement
                                <svg class="h-6 w-6 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>

                        <!-- Note de confiance -->
                        <p class="mt-8 text-sm text-gray-500 dark:text-gray-400">
                            Aucune carte bancaire requise • Annulation à tout moment
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>


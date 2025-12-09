<script setup lang="ts">
import { register } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { Check, X } from 'lucide-vue-next';

const freeFeatures = [
    'Création de séances illimitées (sans enregistrement)',
    'Accès à tous les exercices de la bibliothèque publique',
    'Impression des séances',
    'Support par email',
];

const freeLimitations = [
    'Pas de création de clients',
    'Pas d\'import ou création d\'exercices personnalisés',
    'Pas de création de catégories d\'exercices',
    'Pas d\'enregistrement des séances',
    'Pas d\'export PDF des séances',
];

const premiumFeatures = [
    'Toutes les fonctionnalités du compte gratuit',
    'Clients illimités',
    'Export des séances en PDF',
    'Sauvegardes illimitées de toutes vos séances',
    'Import d\'exercices illimités dans la bibliothèque',
    'Création de nouvelles catégories d\'exercices illimités',
    'Support email prioritaire',
];

const popular = true; // Mettre à true pour mettre en évidence l'offre premium
</script>

<template>
    <section class="bg-gradient-to-b from-gray-50 to-white py-24 dark:from-gray-900 dark:to-gray-800">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <!-- En-tête -->
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl dark:text-white">
                    Tarifs simples et transparents
                </h2>
                <p class="mt-6 text-xl leading-8 text-gray-600 dark:text-gray-300">
                    Choisissez l'offre qui correspond à vos besoins. Commencez gratuitement, passez au premium quand vous êtes prêt.
                </p>
            </div>

            <!-- Grille de tarifs -->
            <div class="mx-auto mt-20 grid max-w-5xl grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Offre Gratuite -->
                <div class="relative flex flex-col rounded-3xl border-2 border-gray-200 bg-white p-8 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex-1">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Gratuit
                        </h3>
                        <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Parfait pour commencer et tester la plateforme
                        </p>
                        <div class="mt-6 flex items-baseline gap-x-1">
                            <span class="text-5xl font-bold tracking-tight text-gray-900 dark:text-white">0€</span>
                            <span class="text-sm font-semibold leading-6 text-gray-600 dark:text-gray-300">/mois</span>
                        </div>

                        <!-- Liste des fonctionnalités -->
                        <ul role="list" class="mt-10 space-y-4 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            <li v-for="feature in freeFeatures" :key="feature" class="flex gap-x-3">
                                <Check class="h-6 w-6 shrink-0 text-green-500" />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>

                        <!-- Limitations -->
                        <div class="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                            <p class="text-xs font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Limitations :</p>
                            <ul class="space-y-2 text-xs text-yellow-700 dark:text-yellow-300">
                                <li v-for="limitation in freeLimitations" :key="limitation" class="flex gap-x-2">
                                    <X class="h-4 w-4 shrink-0 text-yellow-600 dark:text-yellow-400" />
                                    <span>{{ limitation }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Bouton CTA -->
                    <Link
                        :href="register.url()"
                        class="mt-10 block w-full rounded-xl border-2 border-gray-300 bg-white px-6 py-3 text-center text-sm font-semibold text-gray-900 transition-all hover:border-gray-400 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:border-gray-500 dark:hover:bg-gray-700"
                    >
                        Commencer gratuitement
                    </Link>
                </div>

                <!-- Offre Premium -->
                <div
                    class="relative flex flex-col rounded-3xl border-2 border-blue-600 bg-gradient-to-br from-blue-50 to-purple-50 p-8 shadow-2xl ring-4 ring-blue-600/20 dark:from-blue-900/20 dark:to-purple-900/20 dark:border-blue-500"
                >
                    <!-- Badge "Populaire" -->
                    <div v-if="popular" class="absolute -top-4 left-1/2 -translate-x-1/2">
                        <span class="inline-flex items-center rounded-full border-2 border-blue-600 bg-blue-600 px-4 py-1 text-xs font-semibold text-white dark:border-blue-500 dark:bg-blue-500">
                            Le plus populaire
                        </span>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Premium
                        </h3>
                        <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Pour les professionnels qui veulent maximiser leur productivité
                        </p>
                        <div class="mt-6 flex items-baseline gap-x-1">
                            <span class="text-5xl font-bold tracking-tight text-gray-900 dark:text-white">5€</span>
                            <span class="text-sm font-semibold leading-6 text-gray-600 dark:text-gray-300">/mois</span>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Facturé mensuellement, annulable à tout moment
                        </p>

                        <!-- Liste des fonctionnalités -->
                        <ul role="list" class="mt-10 space-y-4 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            <li v-for="feature in premiumFeatures" :key="feature" class="flex gap-x-3">
                                <Check class="h-6 w-6 shrink-0 text-green-500" />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Bouton CTA -->
                    <Link
                        :href="register.url()"
                        class="mt-10 block w-full rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-3 text-center text-sm font-semibold text-white shadow-lg transition-all hover:scale-105 hover:from-blue-700 hover:to-purple-700 hover:shadow-xl dark:from-blue-500 dark:to-purple-500 dark:hover:from-blue-600 dark:hover:to-purple-600"
                    >
                        Essayer Premium
                    </Link>
                </div>
            </div>

            <!-- FAQ rapide -->
            <div class="mx-auto mt-16 max-w-3xl">
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-8 dark:border-gray-700 dark:bg-gray-800/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Questions fréquentes
                    </h3>
                    <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Puis-je passer du gratuit au premium plus tard ?</p>
                            <p class="mt-1">Oui, vous pouvez mettre à niveau votre compte à tout moment depuis votre tableau de bord.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Que se passe-t-il si j'annule mon abonnement premium ?</p>
                            <p class="mt-1">Votre compte repasse automatiquement en gratuit. Vous conservez toutes vos données, mais les limitations du compte gratuit s'appliquent.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>


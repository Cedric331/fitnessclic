<script setup lang="ts">
import { Zap, BookOpen, Users } from 'lucide-vue-next';
</script>

<template>
    <section class="bg-gradient-to-b from-white to-gray-50 py-24 dark:from-gray-900 dark:to-gray-800">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl dark:text-white">
                    Tout ce dont vous avez besoin
                </h2>
                <p class="mt-6 text-xl leading-8 text-gray-600 dark:text-gray-300">
                    Des fonctionnalités pensées pour les professionnels du sport
                </p>
            </div>

            <div class="mx-auto mt-20 grid max-w-2xl grid-cols-1 gap-8 sm:mt-24 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                <!-- Carte 1: Création Rapide -->
                <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100 dark:from-blue-900/20"></div>
                    <div class="relative mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                        <Zap class="h-7 w-7 text-white" />
                    </div>
                    <h3 class="relative mb-4 text-2xl font-bold text-gray-900 dark:text-white">
                        Création Rapide
                    </h3>
                    <p class="relative text-gray-600 leading-relaxed dark:text-gray-300">
                        Composez vos séances en quelques clics grâce à notre interface intuitive par glisser-déposer. Aucune compétence technique requise.
                    </p>
                </div>

                <!-- Carte 2: Bibliothèque Personnalisée -->
                <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100 dark:from-purple-900/20"></div>
                    <div class="relative mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                        <BookOpen class="h-7 w-7 text-white" />
                    </div>
                    <h3 class="relative mb-4 text-2xl font-bold text-gray-900 dark:text-white">
                        Bibliothèque Personnalisée + de 1500 illustrations
                    </h3>
                    <p class="relative text-gray-600 leading-relaxed dark:text-gray-300">
                        Créez et gérez votre propre bibliothèque d'exercices avec images, descriptions détaillées et vidéos explicatives.
                    </p>
                </div>

                <!-- Carte 3: Gestion Clients -->
                <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-8 shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                    <div class="absolute inset-0 bg-gradient-to-br from-pink-50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100 dark:from-pink-900/20"></div>
                    <div class="relative mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-pink-600 shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                        <Users class="h-7 w-7 text-white" />
                    </div>
                    <h3 class="relative mb-4 text-2xl font-bold text-gray-900 dark:text-white">
                        Gestion Clients
                    </h3>
                    <p class="relative text-gray-600 leading-relaxed dark:text-gray-300">
                        Organisez les séances par client et suivez leur progression facilement avec des statistiques détaillées.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>


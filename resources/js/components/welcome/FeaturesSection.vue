<script setup lang="ts">
import { Zap, BookOpen, Users } from 'lucide-vue-next';
</script>

<template>
    <section class="bg-white py-20 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                    Tout ce dont vous avez besoin
                </h2>
                <p class="mt-4 text-lg leading-8 text-gray-600 dark:text-gray-300">
                    Des fonctionnalités pensées pour les professionnels du sport
                </p>
            </div>

            <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                <!-- Carte 1: Création Rapide -->
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-8 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <Zap class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        Création Rapide
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Composez vos séances en quelques clics grâce à notre interface intuitive par glisser-déposer
                    </p>
                </div>

                <!-- Carte 2: Bibliothèque Personnalisée -->
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-8 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <BookOpen class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        Bibliothèque Personnalisée
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Créez et gérez votre propre bibliothèque d'exercices avec images et descriptions
                    </p>
                </div>

                <!-- Carte 3: Gestion Clients -->
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-8 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        Gestion Clients
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Organisez les séances par client et suivez leur progression facilement
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>


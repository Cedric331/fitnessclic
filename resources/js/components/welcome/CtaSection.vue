<script setup lang="ts">
import { register } from '@/routes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <section class="bg-white py-20 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                    Prêt à transformer votre façon de travailler ?
                </h2>
                <p class="mt-4 text-lg leading-8 text-gray-600 dark:text-gray-300">
                    Rejoignez les coachs qui ont déjà simplifié la création de leurs séances
                </p>
                <div class="mt-10">
                    <Link
                        :href="register()"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-8 py-3 text-base font-semibold text-white transition-all hover:bg-blue-700 hover:shadow-lg dark:bg-blue-500 dark:hover:bg-blue-600"
                    >
                        Créer mon compte gratuitement
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>


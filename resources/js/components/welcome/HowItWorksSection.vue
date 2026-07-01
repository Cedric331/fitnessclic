<script setup lang="ts">
const steps = [
    {
        num: '01',
        title: 'Décrivez votre objectif',
        text: 'Indiquez votre discipline et votre ville (ou « en ligne »). On vous présente les coachs qui correspondent.',
    },
    {
        num: '02',
        title: 'Comparez les profils',
        text: 'Tarifs, spécialités et présentation : vous avez les infos pour choisir le coach qui vous convient.',
    },
    {
        num: '03',
        title: 'Contactez et démarrez',
        text: 'Échangez via la messagerie intégrée, planifiez votre première séance et suivez vos programmes.',
    },
];
</script>

<template>
    <section class="bg-white py-20 sm:py-24 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-10 text-center">
                <span class="text-sm font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                    Comment ça marche
                </span>
                <h2 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    Votre coach en 3 étapes
                </h2>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div
                    v-for="step in steps"
                    :key="step.num"
                    class="rounded-2xl border border-gray-200 bg-white p-7 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="mb-4 flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-purple-100 text-sm font-bold text-blue-600 dark:from-blue-900/40 dark:to-purple-900/40 dark:text-blue-400">
                        {{ step.num }}
                    </div>
                    <h3 class="mb-2 text-lg font-bold text-gray-900 dark:text-white">{{ step.title }}</h3>
                    <p class="text-gray-600 dark:text-gray-300">{{ step.text }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { register } from '@/routes';
import { Dumbbell, ClipboardList, FileDown, Users, MessageSquare, Sparkles, ArrowRight } from 'lucide-vue-next';

const tools = [
    {
        icon: Dumbbell,
        title: 'Bibliothèque d’exercices',
        text: 'Constituez votre catalogue d’exercices avec images et consignes, réutilisable dans toutes vos séances.',
    },
    {
        icon: ClipboardList,
        title: 'Création de séances',
        text: 'Composez des séances et programmes structurés en quelques minutes grâce à un éditeur pensé pour les coachs.',
    },
    {
        icon: FileDown,
        title: 'Export PDF',
        text: 'Générez des séances au format PDF, prêtes à être envoyées ou imprimées pour vos clients.',
    },
    {
        icon: Users,
        title: 'Gestion des clients',
        text: 'Centralisez vos clients, suivez leur progression et retrouvez leur historique en un coup d’œil.',
    },
    {
        icon: MessageSquare,
        title: 'Messagerie intégrée',
        text: 'Échangez directement avec vos clients et prospects depuis la plateforme, sans outil externe.',
    },
    {
        icon: Sparkles,
        title: 'Générateur d’images IA',
        text: 'Illustrez vos exercices avec des visuels générés par IA quand vous n’avez pas la photo sous la main.',
    },
];
</script>

<template>
    <section class="bg-gray-50 py-20 sm:py-24 dark:bg-gray-950">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mb-12 max-w-2xl">
                <span class="text-sm font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                    L’outil des coachs
                </span>
                <h2 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    Tout pour gérer votre activité de coaching
                </h2>
                <p class="mt-3 text-gray-600 dark:text-gray-300">
                    FitnessClic n’est pas qu’un annuaire : c’est aussi le logiciel qui vous fait gagner
                    du temps au quotidien, du premier contact au suivi de vos clients.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="tool in tools"
                    :key="tool.title"
                    class="rounded-2xl border border-gray-200 bg-white p-7 transition hover:-translate-y-1 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900"
                >
                    <span class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-purple-100 text-blue-600 dark:from-blue-900/40 dark:to-purple-900/40 dark:text-blue-400">
                        <component :is="tool.icon" class="size-5" />
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">{{ tool.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ tool.text }}</p>
                </div>
            </div>

            <div class="mt-10">
                <Link
                    :href="register.url()"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-7 py-4 font-semibold text-white shadow-md transition-all hover:from-blue-700 hover:to-purple-700"
                >
                    Essayer gratuitement
                    <ArrowRight class="size-5" />
                </Link>
            </div>
        </div>
    </section>
</template>

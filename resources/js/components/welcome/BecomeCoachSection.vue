<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { register } from '@/routes';
import { ArrowRight, MapPin, Users, LayoutGrid } from 'lucide-vue-next';

const benefits = [
    {
        icon: MapPin,
        title: 'Visibilité locale',
        text: 'Apparaissez dans l’annuaire et les recherches « près de chez moi » des clients de votre secteur.',
    },
    {
        icon: Users,
        title: 'Clients qualifiés',
        text: 'Recevez des demandes de personnes qui cherchent précisément votre discipline et votre zone.',
    },
    {
        icon: LayoutGrid,
        title: 'Outils tout-en-un',
        text: 'Programmes, création de séances, bibliothèque d’exercices et messagerie réunis au même endroit.',
    },
];
</script>

<template>
    <section class="bg-white py-20 sm:py-24 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div
                class="relative overflow-hidden rounded-3xl bg-gray-900 px-8 py-12 sm:px-14 sm:py-16"
            >
                <!-- Halos décoratifs -->
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-pink-500/30 blur-3xl"></div>
                    <div class="absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-purple-600/40 blur-3xl"></div>
                </div>

                <div class="relative">
                    <div class="max-w-2xl">
                        <span class="text-sm font-semibold uppercase tracking-wider text-pink-300">
                            Vous êtes coach ?
                        </span>
                        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            Développez votre activité avec FitnessClic
                        </h2>
                        <p class="mt-4 text-white/75">
                            Créez votre profil, gagnez en visibilité auprès de clients de votre région
                            et pilotez tout votre coaching depuis un seul outil.
                        </p>
                    </div>

                    <!-- 3 bénéfices -->
                    <div class="mt-10 grid gap-6 sm:grid-cols-3">
                        <div
                            v-for="benefit in benefits"
                            :key="benefit.title"
                            class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm"
                        >
                            <span class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500/30 to-purple-600/30 text-pink-200">
                                <component :is="benefit.icon" class="size-5" />
                            </span>
                            <h3 class="mt-4 text-lg font-bold text-white">{{ benefit.title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-white/70">{{ benefit.text }}</p>
                        </div>
                    </div>

                    <!-- Transparence tarifaire + CTA -->
                    <div class="mt-10 flex flex-col items-start gap-6 border-t border-white/10 pt-8 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-white/80">
                            <span class="font-semibold text-white">Profil gratuit, sans engagement.</span>
                            Outils premium (clients illimités, export PDF…) dès
                            <span class="font-semibold text-white">5 €/mois</span>.
                        </p>
                        <Link
                            :href="register.url()"
                            class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-white px-7 py-4 font-semibold text-gray-900 shadow-lg transition-all hover:scale-105"
                        >
                            Créer mon profil coach
                            <ArrowRight class="size-5" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

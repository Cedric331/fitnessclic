<script setup lang="ts">
import { ref, computed } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

// Les screenshots seront ajoutés ici
const screenshots = [
    {
        id: 1,
        title: 'Création de séance',
        description: 'Interface intuitive pour créer vos séances en quelques clics',
        image: '/assets/screenshots/screenshot-creation.png',
        alt: 'Capture d\'écran de la création de séance',
    },
    {
        id: 2,
        title: 'Bibliothèque d\'exercices',
        description: 'Gérez votre bibliothèque personnalisée avec images et descriptions',
        image: '/assets/screenshots/screenshot-library.png',
        alt: 'Capture d\'écran de la bibliothèque d\'exercices',
    },
    {
        id: 3,
        title: 'Gestion des clients',
        description: 'Organisez et suivez la progression de vos clients',
        image: '/assets/screenshots/screenshot-clients.png',
        alt: 'Capture d\'écran de la gestion des clients',
    },
    {
        id: 4,
        title: 'Vue d\'ensemble du dashboard',
        description: 'Tableau de bord complet pour suivre toutes vos activités',
        image: '/assets/screenshots/screenshot-dashboard.png',
        alt: 'Capture d\'écran du dashboard',
    },
];

const currentIndex = ref(0);
const currentScreenshot = computed(() => screenshots[currentIndex.value]);

const nextScreenshot = () => {
    currentIndex.value = (currentIndex.value + 1) % screenshots.length;
};

const prevScreenshot = () => {
    currentIndex.value = (currentIndex.value - 1 + screenshots.length) % screenshots.length;
};

const goToScreenshot = (index: number) => {
    currentIndex.value = index;
};
</script>

<template>
    <section class="bg-white py-24 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <!-- En-tête -->
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl dark:text-white">
                    Découvrez l'interface
                </h2>
                <p class="mt-6 text-xl leading-8 text-gray-600 dark:text-gray-300">
                    Une interface moderne et intuitive conçue pour simplifier votre travail
                </p>
            </div>

            <!-- Carrousel de screenshots -->
            <div class="mx-auto mt-20 max-w-6xl">
                <div class="relative">
                    <!-- Container principal avec image -->
                    <div class="relative overflow-hidden rounded-3xl border border-gray-200 bg-gray-100 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                        <!-- Image principale -->
                        <div class="aspect-video w-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-gray-700 dark:to-gray-800">
                            <Transition
                                name="fade"
                                mode="out-in"
                            >
                                <div :key="currentScreenshot.id" class="relative h-full w-full">
                                    <img
                                        :src="currentScreenshot.image"
                                        :alt="currentScreenshot.alt"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                        width="1200"
                                        height="675"
                                        @error="(e) => { e.target.style.display = 'none'; }"
                                    />
                                    <!-- Placeholder si l'image n'existe pas encore -->
                                    <div
                                        v-if="!currentScreenshot.image || currentScreenshot.image.includes('placeholder')"
                                        class="absolute inset-0 flex items-center justify-center"
                                    >
                                        <div class="text-center">
                                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-300 dark:bg-gray-600">
                                                <svg class="h-8 w-8 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                {{ currentScreenshot.title }}
                                            </p>
                                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                                Capture d'écran à ajouter
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Overlay avec informations -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent">
                            <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                                <h3 class="text-2xl font-bold">{{ currentScreenshot.title }}</h3>
                                <p class="mt-2 text-base text-gray-200">{{ currentScreenshot.description }}</p>
                            </div>
                        </div>

                        <!-- Bouton précédent -->
                        <button
                            @click="prevScreenshot"
                            class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-3 shadow-lg transition-all hover:bg-white hover:scale-110 dark:bg-gray-800/90 dark:hover:bg-gray-800"
                            aria-label="Image précédente"
                        >
                            <ChevronLeft class="h-6 w-6 text-gray-900 dark:text-white" />
                        </button>

                        <!-- Bouton suivant -->
                        <button
                            @click="nextScreenshot"
                            class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-3 shadow-lg transition-all hover:bg-white hover:scale-110 dark:bg-gray-800/90 dark:hover:bg-gray-800"
                            aria-label="Image suivante"
                        >
                            <ChevronRight class="h-6 w-6 text-gray-900 dark:text-white" />
                        </button>
                    </div>

                    <!-- Indicateurs de position (dots) -->
                    <div class="mt-8 flex items-center justify-center gap-2">
                        <button
                            v-for="(screenshot, index) in screenshots"
                            :key="screenshot.id"
                            @click="goToScreenshot(index)"
                            class="h-3 rounded-full transition-all"
                            :class="index === currentIndex 
                                ? 'w-8 bg-blue-600 dark:bg-blue-500' 
                                : 'w-3 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500'"
                            :aria-label="`Aller à ${screenshot.title}`"
                        />
                    </div>

                    <!-- Compteur -->
                    <div class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
                        {{ currentIndex + 1 }} / {{ screenshots.length }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>


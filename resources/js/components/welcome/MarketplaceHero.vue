<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Search, LocateFixed, Loader2, ShieldCheck, MessageSquare, BadgeEuro, ArrowRight } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import CityAutocomplete from '@/components/CityAutocomplete.vue';
import { register } from '@/routes';

const reassurances = [
 //
];

const q = ref('');
const city = ref('');
const geoLoading = ref(false);
const geoError = ref('');

const popular = ['Musculation', 'Perte de poids', 'Yoga', 'Cardio & HIIT', 'Préparation physique', 'Remise en forme'];

const search = () => {
    router.get('/coachs', { q: q.value, city: city.value });
};

const searchSpecialty = (specialty: string) => {
    router.get('/coachs', { q: specialty });
};

// Géolocalisation → annuaire centré sur la position de l'utilisateur.
const locateMe = () => {
    geoError.value = '';

    if (!('geolocation' in navigator)) {
        geoError.value = "La géolocalisation n'est pas supportée par votre navigateur.";
        return;
    }

    geoLoading.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            router.get('/coachs', {
                around: 1,
                lat: position.coords.latitude,
                lng: position.coords.longitude,
            });
        },
        (error) => {
            geoLoading.value = false;
            geoError.value =
                error.code === error.PERMISSION_DENIED
                    ? 'Autorisez la géolocalisation pour trouver des coachs autour de vous.'
                    : 'Impossible de récupérer votre position.';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
    );
};
</script>

<template>
    <section
        class="relative overflow-hidden bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 py-20 sm:py-28 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
    >
        <!-- Éléments décoratifs -->
        <div class="absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute -left-1/2 top-0 h-[500px] w-[500px] animate-pulse rounded-full bg-blue-400/20 blur-3xl"></div>
            <div class="absolute -right-1/2 bottom-0 h-[500px] w-[500px] animate-pulse rounded-full bg-purple-400/20 blur-3xl" style="animation-delay: 1s"></div>
        </div>

        <div class="mx-auto max-w-4xl px-6 text-center lg:px-8">
            <div
                class="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/90 px-5 py-2.5 text-sm font-medium text-blue-700 shadow-sm backdrop-blur-md dark:border-blue-800 dark:bg-gray-800/90 dark:text-blue-300"
            >
                La marketplace des coachs sportifs
            </div>

            <h1 class="mb-6 text-3xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-5xl dark:text-white">
                Trouvez le
                <span class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent dark:from-blue-400 dark:via-purple-400 dark:to-pink-400">
                    coach sportif
                </span>
                qui vous fait progresser
            </h1>

            <p class="mx-auto mb-10 max-w-2xl text-lg text-gray-600 sm:text-xl dark:text-gray-300">
                Près de chez vous ou en ligne. Comparez les profils, les spécialités et les tarifs,
                puis contactez votre coach en quelques clics.
            </p>

            <!-- Recherche -->
            <form
                class="mx-auto flex max-w-2xl flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-lg sm:flex-row dark:border-gray-700 dark:bg-gray-800"
                @submit.prevent="search"
            >
                <div class="relative flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="q" class="h-11 border-0 pl-9 shadow-none" placeholder="Objectif, discipline, mot-clé…" />
                </div>
                <div class="flex-1">
                    <CityAutocomplete v-model="city" placeholder="Ville" class="h-11 border-0 shadow-none" />
                </div>
                <button
                    type="submit"
                    class="inline-flex h-11 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-gradient-to-r from-blue-600 to-purple-600 px-6 font-semibold text-white shadow-md transition-all hover:from-blue-700 hover:to-purple-700"
                >
                    Rechercher
                </button>
            </form>

            <!-- Micro-réassurance -->
            <ul class="mx-auto mt-4 flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                <li
                    v-for="item in reassurances"
                    :key="item.label"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-300"
                >
                    <component :is="item.icon" class="size-4 text-blue-600 dark:text-blue-400" />
                    {{ item.label }}
                </li>
            </ul>

            <!-- Autour de moi -->
            <button
                type="button"
                class="mx-auto mt-4 inline-flex items-center gap-2 text-sm font-medium text-blue-700 transition hover:underline disabled:opacity-60 dark:text-blue-300"
                :disabled="geoLoading"
                @click="locateMe"
            >
                <Loader2 v-if="geoLoading" class="size-4 animate-spin" />
                <LocateFixed v-else class="size-4" />
                Trouver des coachs autour de moi
            </button>
            <p v-if="geoError" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ geoError }}</p>

            <!-- Suggestions populaires -->
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <span class="text-sm font-medium text-muted-foreground">Populaire :</span>
                <button
                    v-for="tag in popular"
                    :key="tag"
                    type="button"
                    class="rounded-full border border-gray-200 bg-white px-3.5 py-1.5 text-sm font-medium text-gray-600 transition hover:border-blue-400 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-blue-500 dark:hover:text-blue-400"
                    @click="searchSpecialty(tag)"
                >
                    {{ tag }}
                </button>
            </div>

            <!-- Parcours coach (double entrée) -->
            <p class="mt-8 text-sm text-gray-600 dark:text-gray-300">
                Vous êtes coach sportif ?
                <Link
                    :href="register.url()"
                    class="inline-flex items-center gap-1 font-semibold text-blue-700 underline-offset-4 transition hover:gap-1.5 hover:underline dark:text-blue-300"
                >
                    Créez votre profil gratuit
                    <ArrowRight class="size-4" />
                </Link>
            </p>
        </div>
    </section>
</template>

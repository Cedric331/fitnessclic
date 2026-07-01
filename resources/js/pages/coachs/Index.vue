<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import SiteLayout from '@/components/SiteLayout.vue';
import { Input } from '@/components/ui/input';
import CoachCard from '@/components/CoachCard.vue';
import type { Coach } from '@/types/coach';
import CityAutocomplete from '@/components/CityAutocomplete.vue';
import type { Commune } from '@/composables/useCommuneSearch';
import { Search, Dumbbell, MapPin, RotateCcw, LocateFixed, Loader2 } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Trouver un coach', href: '/coachs' }];

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    coaches: {
        data: Coach[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        q: string;
        city: string;
        specialty: string;
        min_rate: string;
        max_rate: string;
        around: boolean;
        radius: number;
        lat: number | null;
        lng: number | null;
    };
}>();

const RADIUS_OPTIONS = [10, 25, 50, 100];

const q = ref(props.filters.q ?? '');
const city = ref(props.filters.city ?? '');
const specialty = ref(props.filters.specialty ?? '');
const minRate = ref(props.filters.min_rate ?? '');
const maxRate = ref(props.filters.max_rate ?? '');
const around = ref(props.filters.around ?? false);
const radius = ref(props.filters.radius ?? 25);
const lat = ref<number | null>(props.filters.lat ?? null);
const lng = ref<number | null>(props.filters.lng ?? null);

// Recherche centrée sur la position GPS de l'utilisateur (sans ville).
const usingGeo = ref(
    props.filters.around && !props.filters.city && props.filters.lat !== null && props.filters.lng !== null,
);
const geoLoading = ref(false);
const geoError = ref('');

// Coordonnées issues d'une sélection dans la liste ; une saisie manuelle les
// invalide (le serveur géocodera alors la ville pour la recherche alentours).
let lastSelectedCity = props.filters.city ?? '';

const onCitySelect = (commune: Commune) => {
    lastSelectedCity = commune.city;
    lat.value = commune.lat;
    lng.value = commune.lng;
    usingGeo.value = false;
};

watch(city, (value) => {
    if (value !== lastSelectedCity) {
        lat.value = null;
        lng.value = null;
        usingGeo.value = false;
    }
});

// Géolocalisation : « trouver des coachs autour de moi » sans saisir de ville.
const locateMe = () => {
    geoError.value = '';

    // Déjà actif → on désactive la recherche par position.
    if (usingGeo.value) {
        usingGeo.value = false;
        around.value = false;
        lat.value = null;
        lng.value = null;
        submit();
        return;
    }

    if (!('geolocation' in navigator)) {
        geoError.value = "La géolocalisation n'est pas supportée par votre navigateur.";
        return;
    }

    geoLoading.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            // On vide la ville sans déclencher l'invalidation des coordonnées.
            lastSelectedCity = '';
            city.value = '';
            lat.value = position.coords.latitude;
            lng.value = position.coords.longitude;
            around.value = true;
            usingGeo.value = true;
            geoLoading.value = false;
            submit();
        },
        (error) => {
            geoLoading.value = false;
            geoError.value =
                error.code === error.PERMISSION_DENIED
                    ? 'Autorisez la géolocalisation pour trouver des coachs autour de vous.'
                    : 'Impossible de récupérer votre position. Réessayez ou saisissez une ville.';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
    );
};

const submit = () => {
    router.get(
        '/coachs',
        {
            q: q.value,
            city: city.value,
            specialty: specialty.value,
            min_rate: minRate.value,
            max_rate: maxRate.value,
            around: around.value ? 1 : 0,
            radius: radius.value,
            lat: lat.value ?? '',
            lng: lng.value ?? '',
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const reset = () => {
    q.value = '';
    city.value = '';
    specialty.value = '';
    minRate.value = '';
    maxRate.value = '';
    around.value = false;
    radius.value = 25;
    lat.value = null;
    lng.value = null;
    usingGeo.value = false;
    geoError.value = '';
    submit();
};
</script>

<template>
    <Head title="Trouver un coach sportif">
        <meta
            name="description"
            content="Trouvez votre coach sportif sur FitnessClic : parcourez les profils, comparez les tarifs et contactez le coach qui vous correspond."
        />
    </Head>

    <SiteLayout :breadcrumbs="breadcrumbItems">
            <!-- Hero / recherche -->
            <section
                class="relative overflow-hidden border-b bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
            >
                <!-- Halos décoratifs -->
                <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                    <div class="absolute -left-32 -top-24 size-96 animate-pulse rounded-full bg-blue-400/20 blur-3xl"></div>
                    <div class="absolute -bottom-32 -right-24 size-96 animate-pulse rounded-full bg-purple-400/20 blur-3xl" style="animation-delay: 1s"></div>
                </div>

                <div class="mx-auto max-w-7xl px-4 py-12 sm:py-16">
                    <div class="max-w-2xl">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/80 px-4 py-1.5 text-sm font-medium text-blue-700 shadow-sm backdrop-blur-md dark:border-blue-800 dark:bg-gray-800/80 dark:text-blue-300"
                        >
                            <Dumbbell class="size-4" />
                            Annuaire des coachs
                        </span>
                        <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                            Trouvez le
                            <span class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent dark:from-blue-400 dark:via-purple-400 dark:to-pink-400">
                                coach sportif
                            </span>
                            qu'il vous faut
                        </h1>
                        <p class="mt-3 text-base text-gray-600 sm:text-lg dark:text-gray-300">
                            Parcourez les profils, comparez les tarifs et contactez celui qui vous
                            correspond.
                        </p>
                    </div>

                    <!-- Carte de recherche -->
                    <form
                        class="mt-8 rounded-2xl border border-gray-200 bg-white p-3 shadow-xl dark:border-gray-700 dark:bg-gray-800"
                        @submit.prevent="submit"
                    >
                        <!-- Ligne principale -->
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div
                                class="flex flex-1 flex-col gap-2 rounded-xl transition focus-within:ring-2 focus-within:ring-blue-500/30 sm:flex-row sm:items-center"
                            >
                                <div class="relative flex-1">
                                    <Search class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        v-model="q"
                                        class="h-12 border-0 pl-11 text-base shadow-none focus-visible:ring-0"
                                        placeholder="Nom, discipline, mot-clé…"
                                    />
                                </div>
                                <div class="mx-1 hidden h-7 w-px bg-border sm:block"></div>
                                <div class="flex-1">
                                    <CityAutocomplete
                                        v-model="city"
                                        placeholder="Ville"
                                        class="h-12 border-0 text-base shadow-none focus-visible:ring-0"
                                        @select="onCitySelect"
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-7 font-semibold text-white shadow-md transition-all hover:from-blue-700 hover:to-purple-700 hover:shadow-lg"
                            >
                                <Search class="size-4" />
                                Rechercher
                            </button>
                        </div>

                        <!-- Filtres secondaires -->
                        <div
                            class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-3 border-t border-gray-100 px-1 pt-3 dark:border-gray-700"
                        >
                            <!-- Tarif -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-muted-foreground">Tarif</span>
                                <Input
                                    v-model="minRate"
                                    type="number"
                                    min="0"
                                    class="h-9 w-24"
                                    placeholder="Min €"
                                />
                                <span class="text-muted-foreground">–</span>
                                <Input
                                    v-model="maxRate"
                                    type="number"
                                    min="0"
                                    class="h-9 w-24"
                                    placeholder="Max €"
                                />
                            </div>

                            <!-- Autour de moi (géolocalisation) -->
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition disabled:opacity-60"
                                :class="
                                    usingGeo
                                        ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-300'
                                        : 'border-input hover:bg-accent'
                                "
                                :disabled="geoLoading"
                                @click="locateMe"
                            >
                                <Loader2 v-if="geoLoading" class="size-3.5 animate-spin" />
                                <LocateFixed v-else class="size-3.5" />
                                {{ usingGeo ? 'Autour de moi · activé' : 'Autour de moi' }}
                            </button>

                            <!-- Aux alentours d'une ville -->
                            <label
                                class="inline-flex cursor-pointer select-none items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition"
                                :class="
                                    around && city
                                        ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-300'
                                        : city
                                          ? 'border-input hover:bg-accent'
                                          : 'cursor-not-allowed border-input text-muted-foreground opacity-60'
                                "
                            >
                                <input
                                    v-model="around"
                                    type="checkbox"
                                    class="size-4 accent-blue-600"
                                    :disabled="!city"
                                />
                                <MapPin class="size-3.5" />
                                Autour de cette ville
                            </label>

                            <!-- Rayon (commun aux deux modes de proximité) -->
                            <label
                                v-if="around && (city || usingGeo)"
                                class="flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                Rayon
                                <select
                                    v-model.number="radius"
                                    class="border-input h-9 rounded-md border bg-transparent px-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    @change="submit"
                                >
                                    <option v-for="r in RADIUS_OPTIONS" :key="r" :value="r">
                                        {{ r }} km
                                    </option>
                                </select>
                            </label>

                            <!-- Réinitialiser -->
                            <button
                                v-if="
                                    filters.q ||
                                    filters.city ||
                                    filters.specialty ||
                                    filters.min_rate ||
                                    filters.max_rate ||
                                    filters.around
                                "
                                type="button"
                                class="ml-auto inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground underline-offset-4 transition hover:text-foreground hover:underline"
                                @click="reset"
                            >
                                <RotateCcw class="size-3.5" />
                                Réinitialiser
                            </button>
                        </div>

                        <p
                            v-if="geoError"
                            class="mt-2 px-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ geoError }}
                        </p>
                    </form>
                </div>
            </section>

            <!-- Résultats -->
            <section class="mx-auto max-w-7xl px-4 py-8">
                <p class="mb-4 text-sm text-muted-foreground">
                    {{ coaches.total }} coach{{ coaches.total > 1 ? 's' : '' }} trouvé{{
                        coaches.total > 1 ? 's' : ''
                    }}
                    <span v-if="filters.around && filters.lat !== null" class="font-medium text-foreground">
                        {{ filters.city ? `autour de ${filters.city}` : 'autour de votre position' }}
                        ({{ filters.radius }} km)
                    </span>
                </p>

                <div
                    v-if="coaches.data.length"
                    class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <CoachCard
                        v-for="coach in coaches.data"
                        :key="coach.slug"
                        :coach="coach"
                    />
                </div>

                <div
                    v-else
                    class="rounded-xl border border-dashed py-16 text-center text-muted-foreground"
                >
                    Aucun coach ne correspond à votre recherche.
                </div>

                <!-- Pagination -->
                <div
                    v-if="coaches.last_page > 1"
                    class="mt-8 flex flex-wrap justify-center gap-1"
                >
                    <Link
                        v-for="(link, i) in coaches.links"
                        :key="i"
                        :href="link.url ?? ''"
                        :class="[
                            'rounded-md border px-3 py-1.5 text-sm',
                            link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </section>
    </SiteLayout>
</template>

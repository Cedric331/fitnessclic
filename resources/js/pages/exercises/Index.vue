<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus, Upload } from 'lucide-vue-next';
import { useNotifications } from '@/composables/useNotifications';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import type { BreadcrumbItemType } from '@/types';
import ExerciseFilters from './ExerciseFilters.vue';
import ExerciseListItem from './ExerciseListItem.vue';
import ExerciseFormDialog from './ExerciseFormDialog.vue';
import ExerciseImportDialog from './ExerciseImportDialog.vue';
import ExerciseDeleteDialog from './ExerciseDeleteDialog.vue';
import UpgradeModal from '@/components/UpgradeModal.vue';
import type { Exercise, ExercisesProps } from './types';

const props = defineProps<ExercisesProps>();

// Clés pour les caches
const FILTERS_CACHE_KEY = 'exercises-filters-cache';
const VIEW_MODE_CACHE_KEY = 'exercises-view-mode-cache';
const FILTERS_CACHE_DURATION_MS = 10 * 60 * 1000; // 10 minutes
const VIEW_MODE_CACHE_DURATION_MS = 7 * 24 * 60 * 60 * 1000; // 1 semaine

// Interface pour les filtres en cache (sans le mode d'affichage)
interface CachedFilters {
    search: string;
    category_id: number | null;
    sort: 'newest' | 'oldest' | 'alphabetical' | 'alphabetical-desc';
    timestamp: number;
}

// Interface pour le cache du mode d'affichage
interface CachedViewMode {
    view: 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8';
    timestamp: number;
}

// Fonctions utilitaires pour le cache des filtres
const getCachedFilters = (): CachedFilters | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const cached = localStorage.getItem(FILTERS_CACHE_KEY);
        if (!cached) {
            return null;
        }

        const parsed: CachedFilters = JSON.parse(cached);
        const now = Date.now();

        // Vérifier si le cache est encore valide (10 minutes)
        if (now - parsed.timestamp > FILTERS_CACHE_DURATION_MS) {
            localStorage.removeItem(FILTERS_CACHE_KEY);
            return null;
        }

        return parsed;
    } catch {
        return null;
    }
};

const saveFiltersToCache = (filters: {
    search: string;
    category_id: number | null;
    sort: 'newest' | 'oldest' | 'alphabetical' | 'alphabetical-desc';
}) => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        const cached: CachedFilters = {
            ...filters,
            timestamp: Date.now(),
        };
        localStorage.setItem(FILTERS_CACHE_KEY, JSON.stringify(cached));
    } catch {
        // Ignorer les erreurs de localStorage (quota, etc.)
    }
};

// Fonctions utilitaires pour le cache du mode d'affichage
const getCachedViewMode = (): CachedViewMode | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const cached = localStorage.getItem(VIEW_MODE_CACHE_KEY);
        if (!cached) {
            return null;
        }

        const parsed: CachedViewMode = JSON.parse(cached);
        const now = Date.now();

        // Vérifier si le cache est encore valide (1 semaine)
        if (now - parsed.timestamp > VIEW_MODE_CACHE_DURATION_MS) {
            localStorage.removeItem(VIEW_MODE_CACHE_KEY);
            return null;
        }

        return parsed;
    } catch {
        return null;
    }
};

const saveViewModeToCache = (view: 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8') => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        const cached: CachedViewMode = {
            view,
            timestamp: Date.now(),
        };
        localStorage.setItem(VIEW_MODE_CACHE_KEY, JSON.stringify(cached));
    } catch {
        // Ignorer les erreurs de localStorage (quota, etc.)
    }
};

const breadcrumbs: BreadcrumbItemType[] = [
    {
        title: "Bibliothèque d'Exercices",
        href: '/exercises',
    },
];

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();

// Vérifier si l'utilisateur est Pro
const isPro = computed(() => (page.props.auth as any)?.user?.isPro ?? false);
const isUpgradeModalOpen = ref(false);

// Écouter les messages flash et les convertir en notifications
const shownFlashMessages = ref(new Set<string>());

watch(() => (page.props as any).flash, (flash) => {
    if (!flash) return;
    
    const successKey = flash.success ? `success-${flash.success}` : null;
    const errorKey = flash.error ? `error-${flash.error}` : null;
    
    if (successKey && !shownFlashMessages.value.has(successKey)) {
        shownFlashMessages.value.add(successKey);
        nextTick(() => {
            setTimeout(() => {
                notifySuccess(flash.success);
            }, 100);
        });
        setTimeout(() => {
            shownFlashMessages.value.delete(successKey);
        }, 4500);
    }
    
    if (errorKey && !shownFlashMessages.value.has(errorKey)) {
        shownFlashMessages.value.add(errorKey);
        nextTick(() => {
            setTimeout(() => {
                notifyError(flash.error);
            }, 100);
        });
        setTimeout(() => {
            shownFlashMessages.value.delete(errorKey);
        }, 6500);
    }
}, { immediate: true });

// Initialiser les filtres depuis le cache ou les props
const cachedFilters = getCachedFilters();
const cachedViewMode = getCachedViewMode();
const initialSearch = cachedFilters?.search ?? props.filters?.search ?? '';
const initialCategory = cachedFilters?.category_id ?? props.filters?.category_id ?? null;
const initialSort = cachedFilters?.sort ?? props.filters?.sort ?? 'newest';
// Le mode d'affichage utilise un cache séparé avec une durée plus longue (1 semaine)
const initialView = cachedViewMode?.view ?? props.filters?.view ?? 'grid-6';

const searchTerm = ref(initialSearch);
const categoryFilter = ref<number | null>(initialCategory);
const sortOrder = ref<'newest' | 'oldest' | 'alphabetical' | 'alphabetical-desc'>(initialSort as 'newest' | 'oldest' | 'alphabetical' | 'alphabetical-desc');
const viewMode = ref<'grid-2' | 'grid-4' | 'grid-6' | 'grid-8'>(initialView as 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8');
const currentPage = ref(props.exercises?.current_page ?? 1);
const isLoading = ref(false);

watch(
    () => props.filters.search,
    (value) => {
        searchTerm.value = value ?? '';
    },
);

watch(
    () => props.filters.category_id,
    (value) => {
        const newValue = value ?? null;
        // Forcer la mise à jour même si la valeur semble identique
        // Cela garantit que le composant enfant reçoit toujours la mise à jour
        categoryFilter.value = newValue;
    },
    { immediate: true },
);

watch(
    () => props.filters.sort,
    (value) => {
        sortOrder.value = value ?? 'newest';
    },
);

// Ne synchroniser le mode d'affichage depuis les props qu'à l'initialisation
// Après cela, le mode d'affichage est géré localement et ne doit pas être réinitialisé
let isInitialViewModeSync = true;

watch(
    () => props.filters.view,
    (value) => {
        // Ne synchroniser que lors de l'initialisation
        if (isInitialViewModeSync && value && value !== viewMode.value) {
            viewMode.value = value;
        }
    },
    { immediate: true },
);

// Sauvegarder automatiquement le mode d'affichage dans le cache quand l'utilisateur le change
watch(
    viewMode,
    (newView) => {
        // Ne pas sauvegarder lors de l'initialisation pour éviter de mettre à jour le timestamp inutilement
        // Le cache est déjà valide si on vient de le restaurer
        if (!isInitialViewModeSync) {
            saveViewModeToCache(newView);
        }
    },
);

const allExercises = ref<Exercise[]>([]);
const lastPageLoaded = ref<number>(0);
const loadingPage = ref<number | null>(null);

// Initialiser avec les données de la première page
watch(
    () => [props.exercises?.data, props.exercises?.current_page],
    (values) => {
        const newData = values[0] as Exercise[] | undefined;
        const pageNumber = values[1] as number | undefined;
        const currentPageNum = pageNumber ?? 1;
        
        // Ignorer si on a déjà traité cette page
        if (currentPageNum <= lastPageLoaded.value && currentPageNum !== 1) {
            return;
        }
        
        if (newData && newData.length > 0) {
            if (currentPageNum === 1) {
                // Nouveau filtre ou première page, on remplace tout
                allExercises.value = [...newData];
                lastPageLoaded.value = 1;
            } else if (currentPageNum > lastPageLoaded.value) {
                // Chargement d'une nouvelle page, on ajoute uniquement les nouveaux exercices
                // Éviter les doublons en vérifiant les IDs
                const existingIds = new Set(allExercises.value.map((ex: { id: number }) => ex.id));
                const newExercises = newData.filter((ex: { id: number }) => !existingIds.has(ex.id));
                if (newExercises.length > 0) {
                    allExercises.value = [...allExercises.value, ...newExercises];
                }
                lastPageLoaded.value = currentPageNum;
            }
        } else if (currentPageNum === 1) {
            // Aucun résultat
            allExercises.value = [];
            lastPageLoaded.value = 0;
        }
        
        // Mettre à jour currentPage et réinitialiser loadingPage
        currentPage.value = currentPageNum;
        loadingPage.value = null;
    },
    { immediate: true },
);

const applyFilters = (resetPage = true) => {
    if (isLoading.value) {
        return;
    }

    const trimmedSearch = searchTerm.value.trim();
    const query: Record<string, string | number> = {};

    if (trimmedSearch.length) {
        query.search = trimmedSearch;
    }

    if (categoryFilter.value !== null) {
        query.category_id = categoryFilter.value;
    }

    if (sortOrder.value) {
        query.sort = sortOrder.value;
    }

    if (viewMode.value) {
        query.view = viewMode.value;
    }

    if (resetPage) {
        query.page = 1;
        currentPage.value = 1;
        lastPageLoaded.value = 0;
        allExercises.value = [];
        loadingPage.value = null;
    } else {
        // Vérifier qu'on n'a pas déjà chargé toutes les pages
        if (!hasMore.value) {
            return;
        }
        
        const nextPage = currentPage.value + 1;
        
        // Vérifier qu'on n'est pas déjà en train de charger cette page
        if (loadingPage.value === nextPage) {
            return;
        }
        
        // Vérifier qu'on n'a pas déjà chargé cette page
        if (nextPage <= lastPageLoaded.value) {
            return;
        }
        
        query.page = nextPage;
        loadingPage.value = nextPage;
    }

    isLoading.value = true;

    router.get('/exercises', query, {
        preserveScroll: !resetPage,
        // Toujours true : on ne reset plus le composant pour garder l'état local des filtres
        preserveState: true,
        only: ['exercises'],
        onSuccess: () => {
            // Sauvegarder les filtres dans le cache après une application réussie (sans le mode d'affichage)
            saveFiltersToCache({
                search: trimmedSearch,
                category_id: categoryFilter.value,
                sort: sortOrder.value,
            });
            // La mise à jour se fait via le watch sur props.exercises?.data
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const exercises = computed(() => allExercises.value);
const hasMore = computed(() => props.exercises?.has_more ?? false);

let scrollTimeout: ReturnType<typeof setTimeout> | null = null;
let isScrolling = false;

const handleScroll = () => {
    // Debounce pour éviter trop d'appels
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
    }
    
    scrollTimeout = setTimeout(() => {
        if (isLoading.value || !hasMore.value || isScrolling) {
            return;
        }

        // Calculer la position du scroll
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;
        const documentHeight = document.documentElement.scrollHeight || document.body.scrollHeight;

        // Charger plus quand on est à 500px du bas
        const threshold = 500;
        const distanceFromBottom = documentHeight - (scrollTop + windowHeight);
        
        if (distanceFromBottom <= threshold) {
            isScrolling = true;
            applyFilters(false);
            // Réinitialiser le flag après un délai
            setTimeout(() => {
                isScrolling = false;
            }, 1000);
        }
    }, 150);
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    // Également écouter sur le body au cas où
    document.body.addEventListener('scroll', handleScroll, { passive: true });
    
    // Si on a restauré des filtres depuis le cache et qu'ils diffèrent des props actuels, appliquer les filtres
    // Note: le mode d'affichage est géré séparément avec un cache de 1 semaine
    if (cachedFilters) {
        const propsFilters = props.filters;
        const filtersDiffer = 
            cachedFilters.search !== (propsFilters?.search ?? '') ||
            cachedFilters.category_id !== (propsFilters?.category_id ?? null) ||
            cachedFilters.sort !== (propsFilters?.sort ?? 'newest');
        
        if (filtersDiffer) {
            // Attendre un peu pour que le composant soit complètement monté
            nextTick(() => {
                applyFilters(true);
            });
        }
    }
    
    // Désactiver la synchronisation du mode d'affichage depuis les props après l'initialisation
    setTimeout(() => {
        isInitialViewModeSync = false;
    }, 100);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    document.body.removeEventListener('scroll', handleScroll);
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
    }
});

const gridColsClass = computed(() => {
    switch (viewMode.value) {
        case 'grid-2':
            // 1 colonne sur mobile, 2 sur sm+
            return 'grid-cols-1 sm:grid-cols-2';
        case 'grid-4':
            // 2 colonnes sur mobile, 2 sur sm, 4 sur lg+
            return 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-4';
        case 'grid-6':
            // 3 colonnes sur mobile, 3 sur md, 6 sur lg+
            return 'grid-cols-3 md:grid-cols-3 lg:grid-cols-6';
        case 'grid-8':
            // 4 colonnes sur mobile, 4 sur sm, 6 sur md, 8 sur lg+
            return 'grid-cols-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8';
        default:
            return 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-4';
    }
});

const isExerciseDialogOpen = ref(false);
const isImportDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const editingExercise = ref<{
    id: number;
    name: string;
    title?: string;
    description?: string | null;
    suggested_duration?: string | null;
    image_url: string;
    category_ids?: number[];
    created_at: string;
} | null>(null);
const exerciseToDelete = ref<{
    id: number;
    name: string;
    image_url: string;
    category_name: string;
    created_at: string;
} | null>(null);

const handleEditExercise = async (exercise: { id: number; name: string; image_url: string; category_name: string; created_at: string }) => {
    // Charger les données complètes de l'exercice depuis le serveur sans changer de page
    try {
        const response = await fetch(`/exercises/${exercise.id}?json=true`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        
        if (response.ok) {
            const data = await response.json();
            const exerciseData = data.exercise;
            const categories = data.categories || [];
            
            // Mettre à jour l'exercice en cours d'édition AVANT d'ouvrir la modal
            editingExercise.value = {
                id: exerciseData.id,
                name: exerciseData.title,
                title: exerciseData.title,
                description: exerciseData.description || null,
                suggested_duration: exerciseData.suggested_duration || null,
                image_url: exerciseData.image_url || '',
                category_ids: categories.map((cat: { id: number }) => cat.id),
                created_at: exerciseData.created_at,
            };
            
            // Attendre que Vue ait mis à jour le DOM avec les nouvelles données
            await nextTick();
            
            // Ouvrir la modal après avoir défini les données
            isExerciseDialogOpen.value = true;
        } else {
            const errorData = await response.json().catch(() => ({ error: response.statusText }));
            console.error('Erreur lors du chargement de l\'exercice:', errorData);
        }
    } catch (error) {
        console.error('Erreur lors du chargement de l\'exercice:', error);
    }
};

const handleDialogClose = (value: boolean) => {
    if (!value) {
        // Quand la modal se ferme, réinitialiser l'exercice en cours d'édition
        editingExercise.value = null;
    }
};

const handleDeleteExercise = (exercise: { id: number; name: string; image_url: string; category_name: string; created_at: string }) => {
    exerciseToDelete.value = exercise;
    isDeleteDialogOpen.value = true;
};
</script>

<template>
    <Head title="Bibliothèque d'Exercices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-full w-full flex-1 flex-col gap-6 rounded-xl px-6 py-5">
            <div class="flex flex-col gap-2">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex-1">
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">Bibliothèque d'Exercices</h1>
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Gérez vos exercices personnalisés en un clin d'œil
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:flex-shrink-0">
                        <Button
                            v-if="isPro"
                            variant="outline"
                            size="sm"
                            class="gap-2 w-full sm:w-auto"
                            @click="isImportDialogOpen = true"
                        >
                            <Upload class="size-4" />
                            <span>Importer</span>
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            class="gap-2 w-full sm:w-auto"
                            @click="isUpgradeModalOpen = true"
                        >
                            <Upload class="size-4" />
                            <span>Importer</span>
                        </Button>
                        <Button
                            v-if="isPro"
                            size="sm"
                            class="gap-2 bg-blue-600 hover:bg-blue-700 text-white w-full sm:w-auto"
                            @click="isExerciseDialogOpen = true"
                        >
                            <Plus class="size-4" />
                            <span class="hidden sm:inline">Ajouter un exercice</span>
                            <span class="sm:hidden">Ajouter</span>
                        </Button>
                        <Button
                            v-else
                            size="sm"
                            class="gap-2 bg-blue-600 hover:bg-blue-700 text-white w-full sm:w-auto"
                            @click="isUpgradeModalOpen = true"
                        >
                            <Plus class="size-4" />
                            <span class="hidden sm:inline">Ajouter un exercice</span>
                            <span class="sm:hidden">Ajouter</span>
                        </Button>
                    </div>
                </div>
            </div>

            <ExerciseFilters
                :categories="props.categories"
                :search="searchTerm"
                :category-id="categoryFilter"
                :sort-order="sortOrder"
                :view-mode="viewMode"
                @update:search="(value: string) => { searchTerm = value; }"
                @update:categoryId="(value: number | null) => { categoryFilter = value; }"
                @update:sortOrder="(value: 'newest' | 'oldest' | 'alphabetical' | 'alphabetical-desc') => { sortOrder = value; }"
                @update:viewMode="(value: 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8') => { viewMode = value; }"
                @apply="async () => {
                    // Attendre que les valeurs soient synchronisées avant d'appliquer
                    await nextTick();
                    applyFilters(true);
                }"
            />

            <div
                v-if="exercises.length"
                class="grid auto-rows-min gap-4"
                :class="gridColsClass"
            >
                <ExerciseListItem 
                    v-for="exercise in exercises" 
                    :key="exercise.id" 
                    :exercise="exercise"
                    :view-mode="viewMode"
                    @edit="handleEditExercise"
                    @delete="handleDeleteExercise"
                />
            </div>

            <p
                v-else-if="!isLoading"
                class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-6 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400"
            >
                Aucun exercice trouvé
            </p>

            <div
                v-if="isLoading"
                class="flex items-center justify-center py-8"
            >
                <div class="flex flex-col items-center gap-2">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-slate-300 border-t-blue-600 dark:border-slate-700 dark:border-t-blue-400"></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Chargement des exercices...</div>
                </div>
            </div>
            
            <div
                v-if="!hasMore && exercises.length > 0 && !isLoading"
                class="flex items-center justify-center py-4"
            >
                <div class="text-sm text-slate-500 dark:text-slate-400">Tous les exercices ont été chargés</div>
            </div>
        </div>

        <ExerciseFormDialog
            v-model:open="isExerciseDialogOpen"
            :exercise="editingExercise"
            :categories="props.categories"
        />

        <ExerciseImportDialog
            v-model:open="isImportDialogOpen"
            :categories="props.categories"
            @imported="() => {
                isImportDialogOpen = false;
                applyFilters(true);
            }"
        />

        <ExerciseDeleteDialog
            v-model:open="isDeleteDialogOpen"
            :exercise="exerciseToDelete"
            @update:open="(value: boolean) => {
                if (!value) {
                    exerciseToDelete.value = null;
                }
            }"
        />
        <UpgradeModal
            v-model:open="isUpgradeModalOpen"
            feature="L'import et la création d'exercices sont réservés aux abonnés Pro. Passez à Pro pour créer et importer des exercices illimités."
        />
    </AppLayout>
</template>


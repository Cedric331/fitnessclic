<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Search, 
    Grid3x3,
    LayoutGrid,
    Grid2x2,
    List, 
    Plus,
    Filter
} from 'lucide-vue-next';
import type { Exercise, Category } from './types';

const draggingExerciseId = ref<number | null>(null);

const page = usePage();
const isPro = computed(() => (page.props.auth as any)?.user?.isPro ?? false);

const props = defineProps<{
    exercises: Exercise[];
    categories: Category[];
    searchTerm: string;
    selectedCategoryId: number | null;
    viewMode: 'grid-2' | 'grid-4' | 'grid-6' | 'list';
    showOnlyMine?: boolean;
    currentUserId?: number;
}>();

const emit = defineEmits<{
    search: [term: string];
    categoryChange: [categoryId: number | null];
    viewModeChange: [mode: 'grid-2' | 'grid-4' | 'grid-6' | 'list'];
    filterChange: [showOnlyMine: boolean];
    addExercise: [exercise: Exercise];
}>();

// Valeur locale de recherche pour une mise à jour immédiate de l'input
const localSearchValue = ref(props.searchTerm);

watch(() => props.searchTerm, (newValue) => {
    localSearchValue.value = newValue;
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(localSearchValue, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        emit('search', newValue);
    }, 300);
});

const gridColsClass = computed(() => {
    switch (props.viewMode) {
        case 'grid-2':
            return 'grid-cols-1 sm:grid-cols-2';
        case 'grid-4':
            return 'grid-cols-2 sm:grid-cols-3';
        case 'grid-6':
            return 'grid-cols-6';
        case 'list':
            return '';
        default:
            return 'grid-cols-2 sm:grid-cols-3';
    }
});

const handleCategoryChange = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;
    emit('categoryChange', value === '' ? null : parseInt(value));
};

const handleAddExercise = (exercise: Exercise) => {
    emit('addExercise', exercise);
};

// Gestion du drag depuis la bibliothèque
const handleDragStart = (event: DragEvent, exercise: Exercise) => {
    if (!event.dataTransfer) return;
    
    event.dataTransfer.effectAllowed = 'copy';
    event.dataTransfer.setData('application/json', JSON.stringify(exercise));
    event.dataTransfer.setData('text/plain', exercise.id.toString());
    
    // Créer une image personnalisée pour le drag depuis la bibliothèque
    const dragElement = (event.target as HTMLElement).closest('[data-exercise-card]') as HTMLElement;
    if (dragElement) {
        const rect = dragElement.getBoundingClientRect();
        const dragImage = dragElement.cloneNode(true) as HTMLElement;
        
        dragImage.style.width = `${rect.width}px`;
        dragImage.style.opacity = '1';
        dragImage.style.transform = 'rotate(-3deg) scale(1.08)';
        dragImage.style.boxShadow = '0 25px 50px rgba(16, 185, 129, 0.4), 0 0 0 3px rgba(16, 185, 129, 0.2), 0 10px 30px rgba(0, 0, 0, 0.3)';
        dragImage.style.border = '3px solid #10b981';
        dragImage.style.borderRadius = '12px';
        dragImage.style.backgroundColor = 'white';
        dragImage.style.filter = 'brightness(1.05) saturate(1.1)';
        dragImage.style.outline = 'none';
        
        // Forcer l'opacité sur tous les éléments enfants
        const allChildren = dragImage.querySelectorAll('*');
        allChildren.forEach((child: Element) => {
            (child as HTMLElement).style.opacity = '1';
        });
        
        document.body.appendChild(dragImage);
        dragImage.style.position = 'absolute';
        dragImage.style.top = '-1000px';
        dragImage.style.pointerEvents = 'none';
        dragImage.style.zIndex = '10000';
        
        event.dataTransfer.setDragImage(dragImage, event.offsetX, event.offsetY);
        
        setTimeout(() => {
            if (document.body.contains(dragImage)) {
                document.body.removeChild(dragImage);
            }
        }, 0);
    }
    
    draggingExerciseId.value = exercise.id;
};


// Réinitialiser l'état de drag quand le drag se termine
const handleDragEnd = () => {
    draggingExerciseId.value = null;
};
</script>

<template>
    <div class="flex flex-col h-full">
        <Card class="flex flex-col h-full flex-1 overflow-hidden shadow-md py-2">
            <CardHeader class="sticky top-0 z-10 bg-white dark:bg-neutral-900 border-b pb-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <CardTitle class="text-xl font-semibold">Bibliothèque</CardTitle>
                    </div>
                    <!-- Mode d'affichage - visible sur tous les écrans, aligné à droite -->
                    <div class="flex items-center gap-1 border rounded-md p-0.5 relative z-20">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :class="viewMode === 'list' ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                            @click.stop="emit('viewModeChange', 'list')"
                            title="Liste"
                        >
                            <List class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :class="viewMode === 'grid-2' ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                            @click.stop="emit('viewModeChange', 'grid-2')"
                            title="2 par ligne"
                        >
                            <Grid2x2 class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :class="viewMode === 'grid-4' ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                            @click.stop="emit('viewModeChange', 'grid-4')"
                            title="3 par ligne"
                        >
                            <LayoutGrid class="h-4 w-4" />
                        </Button>
                        <!-- Grid-6 visible sur tous les écrans -->
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :class="viewMode === 'grid-6' ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                            @click.stop="emit('viewModeChange', 'grid-6')"
                            title="6 par ligne"
                        >
                            <Grid3x3 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Barre de recherche -->
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-neutral-400" />
                    <Input
                        v-model="localSearchValue"
                        placeholder="Rechercher un exercice..."
                        class="pl-9"
                    />
                </div>

                <!-- Filtres -->
                <div class="flex items-center gap-3">
                    <!-- Filtre par catégorie -->
                    <div class="flex-1">
                        <select
                            :value="selectedCategoryId || ''"
                            @change="handleCategoryChange"
                            class="w-full h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="">Toutes les catégories</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filtre utilisateur -->
                    <div class="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            :class="!showOnlyMine ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                            @click="emit('filterChange', false)"
                            title="Tous les exercices"
                        >
                            <Filter class="h-4 w-4 mr-1" />
                            <span class="text-xs">Tous</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            :class="[
                                showOnlyMine ? 'bg-neutral-100 dark:bg-neutral-800' : '',
                                !isPro ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            :disabled="!isPro"
                            @click="isPro && emit('filterChange', true)"
                            title="Mes exercices uniquement"
                        >
                            <Filter class="h-4 w-4 mr-1" />
                            <span class="text-xs">Mes exercices</span>
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <!-- Liste des exercices -->
            <CardContent class="flex-1 overflow-y-auto p-6">
            <div v-if="exercises.length === 0" class="text-center py-12 text-neutral-500">
                <p>Aucun exercice trouvé</p>
                <p class="text-sm mt-1">Essayez de modifier vos filtres de recherche</p>
            </div>

            <!-- Vue en grille -->
            <div
                v-else-if="viewMode === 'grid-2' || viewMode === 'grid-4' || viewMode === 'grid-6'"
                :class="[
                    'grid',
                    viewMode === 'grid-6' ? 'gap-1' : 'gap-4',
                    gridColsClass
                ]"
            >
                <Card
                    v-for="exercise in exercises"
                    :key="exercise.id"
                    data-exercise-card
                    :class="{
                        'group cursor-pointer hover:shadow-lg transition-all hover:scale-[1.02] p-0': true,
                        'opacity-70 scale-95 blur-[1px]': draggingExerciseId === exercise.id,
                        'ring-2 ring-emerald-400 ring-offset-2': draggingExerciseId === exercise.id
                    }"
                    :draggable="true"
                    @dragstart="handleDragStart($event, exercise)"
                    @dragend="handleDragEnd"
                    @click="handleAddExercise(exercise)"
                >
                    <CardContent class="p-0">
                        <!-- Image qui remplit tout le cadre -->
                        <div class="relative aspect-square w-full overflow-hidden rounded-lg bg-neutral-100 dark:bg-neutral-800">
                            <img
                                v-if="exercise.image_url"
                                :src="exercise.image_url"
                                :alt="exercise.title"
                                :class="[
                                    'h-full w-full',
                                    viewMode === 'grid-6' ? 'object-cover' : 'object-contain object-top'
                                ]"
                                loading="lazy"
                                width="400"
                                height="400"
                                draggable="false"
                                @error="($event.target as HTMLImageElement).style.display = 'none'"
                            />
                            <div
                                v-if="!exercise.image_url"
                                class="h-full w-full flex items-center justify-center text-neutral-400"
                            >
                                <span class="text-xs">Aucune image</span>
                            </div>
                            <!-- Overlay au survol avec titre et icône -->
                            <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-3 gap-2">
                                <div class="flex flex-col items-center gap-2">
                                    <h3 
                                        :class="{
                                            'font-semibold text-white text-center line-clamp-2': true,
                                            'text-xs': viewMode === 'grid-6',
                                            'text-sm': viewMode === 'grid-4',
                                            'text-base': viewMode === 'grid-2'
                                        }"
                                    >
                                        {{ exercise.title && exercise.title.length > 10 ? exercise.title.substring(0, 10) + '...' : exercise.title }}
                                    </h3>
                                </div>
                                <!-- Icône + dans un cercle -->
                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 border-2 border-white/50 hover:border-white transition-all">
                                    <Plus class="h-4 w-4 text-white" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Vue en liste -->
            <div v-else class="space-y-2">
                <Card
                    v-for="exercise in exercises"
                    :key="exercise.id"
                    data-exercise-card
                    :class="{
                        'group cursor-pointer hover:shadow-md transition-all py-0': true,
                        'opacity-70 scale-95 blur-[1px]': draggingExerciseId === exercise.id,
                        'ring-2 ring-emerald-400 ring-offset-2': draggingExerciseId === exercise.id
                    }"
                    :draggable="true"
                    @dragstart="handleDragStart($event, exercise)"
                    @dragend="handleDragEnd"
                    @click="handleAddExercise(exercise)"
                >
                    <CardContent class="p-0">
                        <div class="flex items-stretch gap-0">
                            <!-- Image qui prend toute la hauteur du cadre -->
                            <div class="flex-shrink-0 w-24 overflow-hidden bg-neutral-100 dark:bg-neutral-800 self-stretch">
                                <img
                                    v-if="exercise.image_url"
                                    :src="exercise.image_url"
                                    :alt="exercise.title"
                                    class="h-full w-full object-contain"
                                    loading="lazy"
                                    width="96"
                                    height="96"
                                    draggable="false"
                                    @error="($event.target as HTMLImageElement).style.display = 'none'"
                                />
                                <div
                                    class="h-full w-full flex items-center justify-center text-neutral-400 text-xs"
                                    :class="{ 'hidden': exercise.image_url }"
                                >
                                    <span>N/A</span>
                                </div>
                            </div>

                            <!-- Informations avec padding -->
                            <div class="flex-1 min-w-0 flex items-center px-3 py-2">
                                <h3 class="font-semibold text-sm line-clamp-1">
                                    {{ exercise.title }}
                                </h3>
                            </div>

                            <!-- Bouton ajouter -->
                            <div class="flex items-center px-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                                >
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
            </CardContent>
        </Card>
    </div>
</template>


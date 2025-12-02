<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
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

// Classes CSS pour les différents modes d'affichage
const gridColsClass = computed(() => {
    switch (props.viewMode) {
        case 'grid-2':
            return 'grid-cols-2';
        case 'grid-4':
            return 'grid-cols-2 md:grid-cols-4';
        case 'grid-6':
            return 'grid-cols-2 md:grid-cols-3 lg:grid-cols-6';
        case 'list':
            return '';
        default:
            return 'grid-cols-2 md:grid-cols-4';
    }
});

const handleSearch = (event: Event) => {
    const value = (event.target as HTMLInputElement).value;
    emit('search', value);
};

const handleCategoryChange = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;
    emit('categoryChange', value === '' ? null : parseInt(value));
};

const handleAddExercise = (exercise: Exercise) => {
    emit('addExercise', exercise);
};

// Gestion du drag depuis la bibliothèque
const handleDragStart = (event: DragEvent, exercise: Exercise) => {
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'copy';
        event.dataTransfer.setData('application/json', JSON.stringify(exercise));
        event.dataTransfer.setData('text/plain', exercise.id.toString());
    }
};
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- En-tête de la bibliothèque -->
        <div class="sticky top-0 z-10 bg-white dark:bg-neutral-900 border-b p-4 space-y-4">
            <div>
                <h2 class="text-xl font-semibold mb-1">Bibliothèque</h2>
                <p class="text-sm text-neutral-500">Glissez ou cliquez pour ajouter des exercices</p>
            </div>

            <!-- Barre de recherche -->
            <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-neutral-400" />
                <Input
                    :value="searchTerm"
                    @input="handleSearch"
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
                        :class="showOnlyMine ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                        @click="emit('filterChange', true)"
                        title="Mes exercices uniquement"
                    >
                        <Filter class="h-4 w-4 mr-1" />
                        <span class="text-xs">Mes exercices</span>
                    </Button>
                </div>

                <!-- Mode d'affichage -->
                <div class="flex items-center gap-1 border rounded-md p-0.5">
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="viewMode === 'grid-2' ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                        @click="emit('viewModeChange', 'grid-2')"
                        title="2 par ligne"
                    >
                        <Grid2x2 class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="viewMode === 'grid-4' ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                        @click="emit('viewModeChange', 'grid-4')"
                        title="4 par ligne"
                    >
                        <Grid3x3 class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="viewMode === 'grid-6' ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                        @click="emit('viewModeChange', 'grid-6')"
                        title="6 par ligne"
                    >
                        <LayoutGrid class="h-4 w-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        :class="viewMode === 'list' ? 'bg-neutral-100 dark:bg-neutral-800' : ''"
                        @click="emit('viewModeChange', 'list')"
                        title="Liste"
                    >
                        <List class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Liste des exercices -->
        <div class="flex-1 overflow-y-auto p-4">
            <div v-if="exercises.length === 0" class="text-center py-12 text-neutral-500">
                <p>Aucun exercice trouvé</p>
                <p class="text-sm mt-1">Essayez de modifier vos filtres de recherche</p>
            </div>

            <!-- Vue en grille -->
            <div
                v-else-if="viewMode === 'grid-2' || viewMode === 'grid-4' || viewMode === 'grid-6'"
                :class="['grid gap-4', gridColsClass]"
            >
                <Card
                    v-for="exercise in exercises"
                    :key="exercise.id"
                    class="group cursor-pointer hover:shadow-lg transition-all hover:scale-[1.02]"
                    :draggable="true"
                    @dragstart="handleDragStart($event, exercise)"
                    @click="handleAddExercise(exercise)"
                >
                    <CardContent class="p-0">
                        <!-- Image -->
                        <div class="relative aspect-video w-full overflow-hidden rounded-t-lg bg-neutral-100 dark:bg-neutral-800">
                            <img
                                v-if="exercise.image_url"
                                :src="exercise.image_url"
                                :alt="exercise.title"
                                class="h-full w-full object-cover"
                                draggable="false"
                                @error="($event.target as HTMLImageElement).style.display = 'none'"
                            />
                            <div
                                v-if="!exercise.image_url"
                                class="h-full w-full flex items-center justify-center text-neutral-400"
                            >
                                <span class="text-xs">Aucune image</span>
                            </div>
                            <div
                                v-else
                                class="h-full w-full flex items-center justify-center text-neutral-400 hidden"
                                :class="{ '!flex': !exercise.image_url }"
                            >
                                <span class="text-xs">Aucune image</span>
                            </div>
                            <!-- Overlay au survol -->
                            <div class="absolute inset-0 bg-primary/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <Button size="sm" variant="secondary">
                                    <Plus class="h-4 w-4 mr-2" />
                                    Ajouter
                                </Button>
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="p-3">
                            <h3 class="font-semibold text-sm mb-2 line-clamp-2">
                                {{ exercise.title }}
                            </h3>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="category in (exercise.categories || []).slice(0, 2)"
                                    :key="category.id"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    {{ category.name }}
                                </Badge>
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
                    class="group cursor-pointer hover:shadow-md transition-all"
                    :draggable="true"
                    @dragstart="handleDragStart($event, exercise)"
                    @click="handleAddExercise(exercise)"
                >
                    <CardContent class="p-3">
                        <div class="flex items-center gap-3">
                            <!-- Image miniature -->
                            <div class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                <img
                                    v-if="exercise.image_url"
                                    :src="exercise.image_url"
                                    :alt="exercise.title"
                                    class="h-full w-full object-cover"
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

                            <!-- Informations -->
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-sm line-clamp-1">
                                    {{ exercise.title }}
                                </h3>
                            </div>

                            <!-- Bouton ajouter -->
                            <Button
                                variant="ghost"
                                size="sm"
                                class="opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                <Plus class="h-4 w-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>


<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import { Search, Download } from 'lucide-vue-next';

interface Props {
    open?: boolean;
    userExerciseIds: number[];
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    userExerciseIds: () => [],
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    imported: [];
}>();

const isOpen = ref(props.open);
const searchTerm = ref('');
const availableExercises = ref<Array<{
    id: number;
    title: string;
    description: string | null;
    image_url: string | null;
    category_name: string;
    user_name: string | null;
    created_at: string;
}>>([]);
const isLoading = ref(false);
const isImporting = ref<number | null>(null);

watch(() => props.open, (newValue) => {
    isOpen.value = newValue;
    if (newValue) {
        loadAvailableExercises();
    }
});

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
    if (!newValue) {
        searchTerm.value = '';
    }
});

const loadAvailableExercises = async () => {
    isLoading.value = true;
    try {
        const response = await fetch('/exercises/available?json=true', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        
        if (response.ok) {
            const data = await response.json();
            availableExercises.value = data.exercises || [];
        }
    } catch (error) {
        console.error('Erreur lors du chargement des exercices disponibles:', error);
    } finally {
        isLoading.value = false;
    }
};

const filteredExercises = computed(() => {
    const term = searchTerm.value.toLowerCase().trim();
    return availableExercises.value.filter(exercise => {
        // Filtrer les exercices que l'utilisateur a déjà importés
        // (la liste userExerciseIds contient les IDs des exercices publics déjà importés)
        if (props.userExerciseIds.includes(exercise.id)) {
            return false;
        }
        // Filtrer par terme de recherche
        if (term) {
            return exercise.title.toLowerCase().includes(term) ||
                   (exercise.description && exercise.description.toLowerCase().includes(term));
        }
        return true;
    });
});

const handleImport = async (exerciseId: number) => {
    isImporting.value = exerciseId;
    try {
        router.post(`/exercises/${exerciseId}/import`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Retirer l'exercice de la liste
                availableExercises.value = availableExercises.value.filter(e => e.id !== exerciseId);
                emit('imported');
            },
            onFinish: () => {
                isImporting.value = null;
            },
        });
    } catch (error) {
        console.error('Erreur lors de l\'import:', error);
        isImporting.value = null;
    }
};

const formatDate = (value: string) => {
    return new Date(value).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent 
            class="sm:max-w-[700px] !z-[60] p-0 overflow-hidden max-h-[90vh] overflow-y-auto"
        >
            <DialogHeader class="px-6 pt-6 pb-4">
                <DialogTitle class="text-xl font-semibold">Importer un exercice public</DialogTitle>
                <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Parcourez et importez des exercices publics créés par d'autres utilisateurs.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            
            <!-- Barre de recherche -->
            <div class="px-6 pt-4">
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-400 dark:text-slate-300" />
                    <Input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Rechercher un exercice..."
                        class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 text-slate-900 shadow-sm transition focus:border-blue-500 focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                    />
                </div>
            </div>

            <!-- Liste des exercices -->
            <div class="px-6 py-4">
                <div v-if="isLoading" class="flex items-center justify-center py-12">
                    <div class="flex flex-col items-center gap-2">
                        <div class="h-8 w-8 animate-spin rounded-full border-4 border-slate-300 border-t-blue-600 dark:border-slate-700 dark:border-t-blue-400"></div>
                        <div class="text-sm text-slate-500 dark:text-slate-400">Chargement des exercices...</div>
                    </div>
                </div>

                <div v-else-if="filteredExercises.length === 0" class="text-center py-12 text-slate-500 dark:text-slate-400">
                    <p v-if="searchTerm">
                        Aucun exercice trouvé pour "{{ searchTerm }}"
                    </p>
                    <p v-else>
                        Aucun exercice public disponible à importer.
                    </p>
                </div>

                <div v-else class="space-y-3 max-h-[400px] overflow-y-auto">
                    <div
                        v-for="exercise in filteredExercises"
                        :key="exercise.id"
                        class="flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900/70 dark:hover:border-slate-700"
                    >
                        <!-- Image -->
                        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-lg">
                            <img
                                v-if="exercise.image_url"
                                :src="exercise.image_url"
                                :alt="exercise.title"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="h-full w-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center">
                                <span class="text-xs text-slate-400">Pas d'image</span>
                            </div>
                        </div>

                        <!-- Contenu -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-slate-900 dark:text-white truncate">
                                        {{ exercise.title }}
                                    </h4>
                                    <p v-if="exercise.description" class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2 mt-1">
                                        {{ exercise.description }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <Badge variant="outline" class="text-xs">
                                            {{ exercise.category_name }}
                                        </Badge>
                                        <span v-if="exercise.user_name" class="text-xs text-slate-500 dark:text-slate-400">
                                            Par {{ exercise.user_name }}
                                        </span>
                                    </div>
                                </div>
                                <Button
                                    size="sm"
                                    class="shrink-0"
                                    :disabled="isImporting === exercise.id"
                                    @click="handleImport(exercise.id)"
                                >
                                    <Download v-if="isImporting !== exercise.id" class="size-4 mr-1" />
                                    <span v-if="isImporting === exercise.id">Importation...</span>
                                    <span v-else>Importer</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Separator />
            <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                <Button
                    type="button"
                    variant="outline"
                    class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 dark:hover:border-slate-600 transition-all duration-200"
                    @click="isOpen = false"
                >
                    Fermer
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


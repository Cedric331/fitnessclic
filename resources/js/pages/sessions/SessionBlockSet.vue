<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { X, ArrowLeftRight, AlertTriangle, GripVertical, RotateCw, Pencil } from 'lucide-vue-next';
import type { SessionBlock, SessionExercise } from './types';

const props = defineProps<{
    block: SessionBlock;
    blockIndex: number;
    displayIndex?: number;
    draggable?: boolean;
    totalCount?: number;
}>();

const emit = defineEmits<{
    drop: [event: DragEvent, blockId: number];
    'remove-exercise': [index: number];
    'update-exercise': [index: number, updates: Partial<SessionExercise>];
    'update-block-description': [description: string];
    'convert-to-standard': [];
    'remove-block': [];
}>();

// Valeur locale pour la description du bloc - permet la réactivité immédiate
const localBlockDescription = ref<string>(props.block.block_description || '');
const isEditingBlockDescription = ref(false);

// Synchroniser avec props.block.block_description quand il change (seulement si l'utilisateur n'est pas en train de modifier)
watch(() => props.block.block_description, (newDescription) => {
    // Ne mettre à jour que si l'utilisateur n'est pas en train de modifier
    if (!isEditingBlockDescription.value) {
        localBlockDescription.value = newDescription || '';
    }
}, { immediate: true });

// Computed pour les exercices du bloc pour assurer la réactivité
// Utiliser directement props.block.exercises pour que Vue détecte les changements
const blockExercises = computed(() => {
    return props.block.exercises;
});

watch(() => props.block.exercises, (newExercises, oldExercises) => {
    
    
    // Préserver l'état d'édition pour les exercices qui existent toujours
    if (oldExercises && editingExerciseNameIds.value) {
        const preservedState: Record<number, boolean> = {};
        newExercises.forEach((newEx: SessionExercise) => {
            const oldEx = oldExercises.find((old: SessionExercise) => old.id === newEx.id);
            if (oldEx && editingExerciseNameIds.value[oldEx.id || -1]) {
                preservedState[newEx.id || -1] = true;
            }
        });
        // Mettre à jour l'état préservé
        Object.keys(preservedState).forEach(key => {
            editingExerciseNameIds.value[Number(key)] = true;
        });
    }
}, { deep: true });
const isDraggingOver = ref(false);
const showRemoveExerciseDialog = ref(false);
const showRemoveBlockDialog = ref(false);
const exerciseToRemoveIndex = ref<number | null>(null);
// Utiliser un objet réactif pour stocker les IDs des exercices en cours d'édition
// Cela persiste même après les re-renders et Vue détecte les changements
const editingExerciseNameIds = ref<Record<number, boolean>>({});

// Computed pour exposer la valeur du ref dans le template
const editingExerciseNameIdsValue = computed({
    get: () => {
        if (!editingExerciseNameIds.value) {
            editingExerciseNameIds.value = {};
        }
        return editingExerciseNameIds.value;
    },
    set: (value) => {
        editingExerciseNameIds.value = value;
    }
});

// Valeurs locales pour l'édition de nom (par ID d'exercice) - évite les re-renders à chaque frappe
const editingNameValues = ref<Record<number, string>>({});

// Valeurs locales pour les champs de saisie (par ID d'exercice) - évite les re-renders à chaque frappe
const editingRepetitionsValues = ref<Record<number, string>>({});
const editingDurationValues = ref<Record<number, string>>({});
const editingWeightValues = ref<Record<number, string>>({});
const editingRestTimeValues = ref<Record<number, string>>({});

const handleDrop = (event: DragEvent) => {
    event.stopPropagation(); // Empêcher la propagation vers le parent
    isDraggingOver.value = false;
    emit('drop', event, props.block.id);
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation(); // Empêcher la propagation vers le parent
    if (event.dataTransfer && event.dataTransfer.types.includes('application/json')) {
        event.dataTransfer.dropEffect = 'copy';
        isDraggingOver.value = true;
    }
};

const handleDragLeave = (event: DragEvent) => {
    event.stopPropagation();
    // Vérifier qu'on quitte vraiment la zone
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const x = event.clientX;
    const y = event.clientY;
    if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
        isDraggingOver.value = false;
    }
};

// Mettre à jour uniquement la valeur locale (pas d'émission d'événement pour éviter les re-renders)
const updateBlockDescription = (value: string) => {
    // Mettre à jour la valeur locale immédiatement pour la réactivité (sans re-render du parent)
    localBlockDescription.value = value;
    isEditingBlockDescription.value = true;
    // Ne pas émettre l'événement ici pour éviter les re-renders qui font perdre le focus
};

// Fonction pour sauvegarder la description (appelée sur blur)
const saveBlockDescription = (value: string) => {
    // Mettre à jour la valeur locale
    localBlockDescription.value = value;
    isEditingBlockDescription.value = true;
    
    // Émettre l'événement pour mettre à jour le parent
    emit('update-block-description', value);
    
    // Réinitialiser le flag après un court délai
    setTimeout(() => {
        isEditingBlockDescription.value = false;
    }, 100);
};

// Trouver l'index de l'exercice dans sessionExercises
const findExerciseIndex = (exercise: SessionExercise): number => {
    return props.block.exercises.indexOf(exercise);
};

// Mettre à jour un exercice du bloc
const updateExercise = (exerciseIndex: number, updates: Partial<SessionExercise>) => {
    const exercise = props.block.exercises[exerciseIndex];
    
    // Les IDs négatifs sont des IDs temporaires pour les nouveaux exercices et ne sont pas uniques
    if (exercise?.id && exercise.id > 0) {
        emit('update-exercise', exercise.id, updates);
    } else {
        // Fallback sur l'index si pas d'ID valide (ou ID temporaire négatif)
        emit('update-exercise', exerciseIndex, updates);
    }
};

// Gérer le clic sur le bouton de suppression d'un exercice
const handleRemoveExerciseClick = (exerciseIndex: number) => {
    exerciseToRemoveIndex.value = exerciseIndex;
    showRemoveExerciseDialog.value = true;
};

// Confirmer la suppression d'un exercice
const confirmRemoveExercise = () => {
    if (exerciseToRemoveIndex.value !== null) {
        emit('remove-exercise', exerciseToRemoveIndex.value);
        showRemoveExerciseDialog.value = false;
        exerciseToRemoveIndex.value = null;
    }
};

// Gérer le clic sur le bouton de suppression du bloc
const handleRemoveBlockClick = () => {
    showRemoveBlockDialog.value = true;
};

// Confirmer la suppression du bloc
const confirmRemoveBlock = () => {
    emit('remove-block');
    showRemoveBlockDialog.value = false;
};

// Obtenir le nom de l'exercice à supprimer
const exerciseToRemoveName = computed(() => {
    if (exerciseToRemoveIndex.value === null) return '';
    const exercise = props.block.exercises[exerciseToRemoveIndex.value];
    return exercise?.exercise?.title || 'Cet exercice';
});
</script>

<template>
    <Card 
        class="relative superset-block transform transition-all duration-200 hover:shadow-lg hover:bg-neutral-50/50 dark:hover:bg-neutral-800/50"
    >
        <!-- Numéro de bloc en haut à gauche -->
        <div v-if="displayIndex !== undefined" class="absolute -top-2 -left-2 z-10 flex items-center justify-center w-8 h-8 rounded-full bg-white text-black text-sm font-bold shadow-md border border-neutral-400">
            {{ displayIndex + 1 }}
        </div>
        <CardContent class="p-1.5">
            <!-- Consignes pour l'ensemble du bloc avec icônes de drag and drop alignées -->
            <div class="mb-2 flex items-start gap-2">
                <!-- Poignée de drag (si draggable) -->
                <div v-if="draggable" class="flex flex-col items-center gap-0.5 flex-shrink-0 pt-0.5">
                    <div class="handle flex items-center justify-center cursor-move text-neutral-400 hover:text-blue-600 transition-all duration-200 p-1.5 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20" title="Glisser pour réorganiser">
                        <GripVertical class="h-5 w-5" />
                    </div>
                </div>
                <!-- Consignes avec boutons Standard et Supprimer -->
                <div class="flex-1 min-w-0">
                    <!-- Sur mobile: boutons en haut à gauche -->
                    <div class="flex flex-row sm:hidden items-center gap-2 mb-1.5">
                        <!-- Toggle Standard -->
                        <Button
                            variant="outline"
                            size="sm"
                            class="text-xs h-7"
                            @click.stop="emit('convert-to-standard')"
                            title="Convertir en exercices standard"
                        >
                            <ArrowLeftRight class="h-3 w-3 mr-1" />
                            Standard
                        </Button>
                        <!-- Bouton supprimer le bloc entier -->
                        <Button
                            v-if="draggable"
                            variant="ghost"
                            size="sm"
                            class="h-7 w-7 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                            @click.stop="handleRemoveBlockClick"
                            @mousedown.stop
                            title="Supprimer le bloc entier"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                    <!-- Label et boutons sur desktop -->
                    <div class="hidden sm:flex items-center justify-between mb-1.5">
                        <Label class="text-sm font-medium">Consignes d'exécution</Label>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <!-- Toggle Standard -->
                            <Button
                                variant="outline"
                                size="sm"
                                class="text-xs h-7"
                                @click.stop="emit('convert-to-standard')"
                                title="Convertir en exercices standard"
                            >
                                <ArrowLeftRight class="h-3 w-3 mr-1" />
                                Standard
                            </Button>
                            <!-- Bouton supprimer le bloc entier -->
                            <Button
                                v-if="draggable"
                                variant="ghost"
                                size="sm"
                                class="h-7 w-7 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                                @click.stop="handleRemoveBlockClick"
                                @mousedown.stop
                                title="Supprimer le bloc entier"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <!-- Label sur mobile -->
                    <Label class="text-sm font-medium mb-1.5 block sm:hidden">Consignes d'exécution</Label>
                    <!-- Textarea prend toute la largeur -->
                    <Textarea
                        :model-value="localBlockDescription"
                        @update:model-value="updateBlockDescription"
                        @blur="(event: FocusEvent) => saveBlockDescription((event.target as HTMLTextAreaElement).value)"
                        placeholder="Ajouter des consignes..."
                        :rows="2"
                        class="text-sm w-full"
                    />
                </div>
            </div>
            
            <!-- Zone de drop pour ajouter des exercices -->
            <div
                class="space-y-1.5 p-1.5 border-2 border-dashed rounded-lg transition-colors"
                :class="{ 
                    'border-blue-500 bg-blue-50': isDraggingOver, 
                    'border-neutral-300': !isDraggingOver,
                    'min-h-[80px]': block.exercises.length === 0 || isDraggingOver
                }"
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop.prevent="handleDrop"
            >
                <!-- Exercices du bloc -->
                <div
                    v-for="(exercise, index) in props.block.exercises"
                    :key="`exercise-${exercise.id}-${exercise.use_duration}-${exercise.use_bodyweight}-${exercise.custom_exercise_name}`"
                    class="relative flex items-center gap-2 p-2 bg-white border border-neutral-200 rounded-lg hover:shadow-md transition-shadow"
                    @mousedown.stop
                    @click.stop
                >
                    <!-- Image de l'exercice -->
                    <div class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-neutral-100">
                        <img
                            v-if="exercise.exercise?.image_url"
                            :src="exercise.exercise.image_url"
                            :alt="exercise.exercise?.title"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-neutral-400 text-xs">
                            Pas d'image
                        </div>
                    </div>
                    
                    <!-- Bouton supprimer - en haut à droite -->
                    <Button
                        variant="ghost"
                        size="sm"
                        class="absolute top-2 right-2 h-6 w-6 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 z-10"
                        @click.stop="handleRemoveExerciseClick(index)"
                        @mousedown.stop
                    >
                        <X class="h-3.5 w-3.5" />
                    </Button>

                    <!-- Informations de l'exercice et paramètres -->
                    <div class="flex-1 min-w-0 pr-6">
                        <!-- Nom personnalisé de l'exercice -->
                        <div class="mb-1.5">
                            <div v-if="!editingExerciseNameIdsValue[exercise.id || index]" class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click.stop="() => {
                                        const exerciseId = exercise.id || index;
                                        if (!editingExerciseNameIds.value) {
                                            editingExerciseNameIds.value = {};
                                        }
                                        editingNameValues[exerciseId] = exercise.custom_exercise_name || exercise.exercise?.title || 'Exercice';
                                        editingExerciseNameIdsValue[exerciseId] = true;
                                    }"
                                    @mousedown.stop
                                    class="p-1 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors flex-shrink-0"
                                    title="Modifier le nom"
                                >
                                    <Pencil class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                </button>
                                <h4 class="text-sm font-semibold flex-1 line-clamp-1">
                                    {{ exercise.custom_exercise_name || exercise.exercise?.title || 'Exercice' }}
                                </h4>
                            </div>
                            <div v-else class="flex items-center gap-2">
                                <Input
                                    :model-value="editingNameValues[exercise.id || index] ?? (exercise.custom_exercise_name || exercise.exercise?.title || 'Exercice')"
                                    @update:model-value="(value: string) => {
                                        const exerciseId = exercise.id || index;
                                        // Mettre à jour la valeur locale uniquement - pas de re-render du parent
                                        editingNameValues[exerciseId] = value;
                                    }"
                                    @blur="() => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingNameValues[exerciseId];
                                        // Mettre à jour le parent uniquement sur blur - évite les re-renders à chaque frappe
                                        if (value !== undefined) {
                                            updateExercise(index, { custom_exercise_name: value || null });
                                            // Nettoyer la valeur locale après la mise à jour
                                            delete editingNameValues[exerciseId];
                                        }
                                        // Fermer le mode édition
                                        if (editingExerciseNameIds.value) {
                                            editingExerciseNameIdsValue[exerciseId] = false;
                                        }
                                    }"
                                    @keydown.enter="(event: KeyboardEvent) => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingNameValues[exerciseId];
                                        // Mettre à jour le parent sur Enter
                                        if (value !== undefined) {
                                            updateExercise(index, { custom_exercise_name: value || null });
                                            // Nettoyer la valeur locale après la mise à jour
                                            delete editingNameValues[exerciseId];
                                        }
                                        // Fermer le mode édition
                                        if (editingExerciseNameIds.value) {
                                            editingExerciseNameIdsValue[exerciseId] = false;
                                        }
                                        // Empêcher le comportement par défaut
                                        event.preventDefault();
                                    }"
                                    @mousedown.stop
                                    @click.stop
                                    placeholder="Nom de l'exercice"
                                    class="text-sm font-semibold h-8 flex-1"
                                    autofocus
                                />
                                <button
                                    type="button"
                                    @click.stop="() => {
                                        const exerciseId = exercise.id || index;
                                        if (editingExerciseNameIds.value) {
                                            editingExerciseNameIdsValue[exerciseId] = false;
                                        }
                                    }"
                                    @mousedown.stop
                                    class="p-1 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors flex-shrink-0"
                                >
                                    <X class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                </button>
                            </div>
                        </div>
                        
                        <!-- Ligne avec Serie, Rep, Charge, Repos - 1 colonne sur mobile, 4 sur desktop -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-1.5">
                            <!-- Série -->
                            <div>
                                <Label class="text-xs text-neutral-500 mb-0.5 block">Série</Label>
                                <Input
                                    type="number"
                                    :model-value="exercise.sets_count"
                                    @update:model-value="(value: string | number) => updateExercise(index, { sets_count: value ? parseInt(value as string) : null })"
                                    @mousedown.stop
                                    @click.stop
                                    placeholder="Nombre"
                                    class="h-8 text-sm"
                                    min="1"
                                />
                            </div>
                            
                            <!-- Répétitions ou Durée (selon le switch) -->
                            <div>
                                <div class="flex items-center justify-between mb-0.5">
                                    <Label class="text-xs text-neutral-500">
                                        {{ (exercise.use_duration ?? false) ? 'Durée (seconde)' : 'Rep' }}
                                    </Label>
                                    <button
                                        type="button"
                                        @click.stop.prevent="updateExercise(index, { use_duration: !(exercise.use_duration ?? false) })"
                                        @mousedown.stop
                                        class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors"
                                        title="Basculer entre Rep et Durée"
                                    >
                                        <RotateCw class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                    </button>
                                </div>
                                <Input
                                    v-if="!(exercise.use_duration ?? false)"
                                    type="number"
                                    :model-value="editingRepetitionsValues[exercise.id || index] ?? (exercise.sets?.[0]?.repetitions ?? exercise.repetitions ?? '')"
                                    @update:model-value="(value: string | number) => {
                                        const exerciseId = exercise.id || index;
                                        editingRepetitionsValues[exerciseId] = value ? String(value) : '';
                                    }"
                                    @blur="() => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingRepetitionsValues[exerciseId];
                                        if (value !== undefined) {
                                            const updates: Partial<SessionExercise> = {};
                                            if (exercise.sets && exercise.sets.length > 0) {
                                                const updatedSets = [...exercise.sets];
                                                updatedSets[0] = { ...updatedSets[0], repetitions: value ? parseInt(value) : null };
                                                updates.sets = updatedSets;
                                            } else {
                                                const defaultSet = {
                                                    set_number: 1,
                                                    repetitions: value ? parseInt(value) : null,
                                                    weight: exercise.weight ?? null,
                                                    rest_time: exercise.rest_time ?? null,
                                                    duration: exercise.duration ?? null,
                                                    order: 0
                                                };
                                                updates.sets = [defaultSet];
                                            }
                                            updateExercise(index, updates);
                                            delete editingRepetitionsValues[exerciseId];
                                        }
                                    }"
                                    @keydown.enter="(event: KeyboardEvent) => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingRepetitionsValues[exerciseId];
                                        if (value !== undefined) {
                                            const updates: Partial<SessionExercise> = {};
                                            if (exercise.sets && exercise.sets.length > 0) {
                                                const updatedSets = [...exercise.sets];
                                                updatedSets[0] = { ...updatedSets[0], repetitions: value ? parseInt(value) : null };
                                                updates.sets = updatedSets;
                                            } else {
                                                const defaultSet = {
                                                    set_number: 1,
                                                    repetitions: value ? parseInt(value) : null,
                                                    weight: exercise.weight ?? null,
                                                    rest_time: exercise.rest_time ?? null,
                                                    duration: exercise.duration ?? null,
                                                    order: 0
                                                };
                                                updates.sets = [defaultSet];
                                            }
                                            updateExercise(index, updates);
                                            delete editingRepetitionsValues[exerciseId];
                                        }
                                        (event.target as HTMLInputElement).blur();
                                    }"
                                    @mousedown.stop
                                    @click.stop
                                    placeholder="10"
                                    class="h-8 text-sm"
                                    min="0"
                                />
                                <Input
                                    v-else
                                    type="text"
                                    :model-value="editingDurationValues[exercise.id || index] ?? (exercise.sets?.[0]?.duration ?? exercise.duration ?? '')"
                                    @update:model-value="(value: string) => {
                                        const exerciseId = exercise.id || index;
                                        editingDurationValues[exerciseId] = value;
                                    }"
                                    @blur="() => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingDurationValues[exerciseId];
                                        if (value !== undefined) {
                                            const updates: Partial<SessionExercise> = {};
                                            if (exercise.sets && exercise.sets.length > 0) {
                                                const updatedSets = [...exercise.sets];
                                                updatedSets[0] = { ...updatedSets[0], duration: value || null };
                                                updates.sets = updatedSets;
                                            } else {
                                                const defaultSet = {
                                                    set_number: 1,
                                                    repetitions: exercise.repetitions ?? null,
                                                    weight: exercise.weight ?? null,
                                                    rest_time: exercise.rest_time ?? null,
                                                    duration: value || null,
                                                    order: 0
                                                };
                                                updates.sets = [defaultSet];
                                            }
                                            updateExercise(index, updates);
                                            delete editingDurationValues[exerciseId];
                                        }
                                    }"
                                    @keydown.enter="(event: KeyboardEvent) => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingDurationValues[exerciseId];
                                        if (value !== undefined) {
                                            const updates: Partial<SessionExercise> = {};
                                            if (exercise.sets && exercise.sets.length > 0) {
                                                const updatedSets = [...exercise.sets];
                                                updatedSets[0] = { ...updatedSets[0], duration: value || null };
                                                updates.sets = updatedSets;
                                            } else {
                                                const defaultSet = {
                                                    set_number: 1,
                                                    repetitions: exercise.repetitions ?? null,
                                                    weight: exercise.weight ?? null,
                                                    rest_time: exercise.rest_time ?? null,
                                                    duration: value || null,
                                                    order: 0
                                                };
                                                updates.sets = [defaultSet];
                                            }
                                            updateExercise(index, updates);
                                            delete editingDurationValues[exerciseId];
                                        }
                                        (event.target as HTMLInputElement).blur();
                                    }"
                                    @mousedown.stop
                                    @click.stop
                                    placeholder="30s"
                                    class="h-8 text-sm"
                                />
                            </div>
                            
                            <!-- Charge (poids) ou Poids de corps (selon le switch) -->
                            <div>
                                <div class="flex items-center justify-between mb-0.5">
                                    <Label class="text-xs text-neutral-500">
                                        {{ (exercise.use_bodyweight ?? false) ? 'Poids de corps' : 'Charge (kg)' }}
                                    </Label>
                                    <button
                                        type="button"
                                        @click.stop.prevent="updateExercise(index, { use_bodyweight: !(exercise.use_bodyweight ?? false) })"
                                        @mousedown.stop
                                        class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors"
                                        title="Basculer entre Charge et Poids de corps"
                                    >
                                        <RotateCw class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                    </button>
                                </div>
                                <Input
                                    v-if="!(exercise.use_bodyweight ?? false)"
                                    type="number"
                                    step="0.5"
                                    :model-value="editingWeightValues[exercise.id || index] ?? (exercise.sets?.[0]?.weight ?? exercise.weight ?? '')"
                                    @update:model-value="(value: string | number) => {
                                        const exerciseId = exercise.id || index;
                                        editingWeightValues[exerciseId] = value ? String(value) : '';
                                    }"
                                    @blur="() => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingWeightValues[exerciseId];
                                        if (value !== undefined) {
                                            const updates: Partial<SessionExercise> = {};
                                            if (exercise.sets && exercise.sets.length > 0) {
                                                const updatedSets = [...exercise.sets];
                                                updatedSets[0] = { ...updatedSets[0], weight: value ? parseFloat(value) : null };
                                                updates.sets = updatedSets;
                                            } else {
                                                const defaultSet = {
                                                    set_number: 1,
                                                    repetitions: exercise.repetitions ?? null,
                                                    weight: value ? parseFloat(value) : null,
                                                    rest_time: exercise.rest_time ?? null,
                                                    duration: exercise.duration ?? null,
                                                    order: 0
                                                };
                                                updates.sets = [defaultSet];
                                            }
                                            updateExercise(index, updates);
                                            delete editingWeightValues[exerciseId];
                                        }
                                    }"
                                    @keydown.enter="(event: KeyboardEvent) => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingWeightValues[exerciseId];
                                        if (value !== undefined) {
                                            const updates: Partial<SessionExercise> = {};
                                            if (exercise.sets && exercise.sets.length > 0) {
                                                const updatedSets = [...exercise.sets];
                                                updatedSets[0] = { ...updatedSets[0], weight: value ? parseFloat(value) : null };
                                                updates.sets = updatedSets;
                                            } else {
                                                const defaultSet = {
                                                    set_number: 1,
                                                    repetitions: exercise.repetitions ?? null,
                                                    weight: value ? parseFloat(value) : null,
                                                    rest_time: exercise.rest_time ?? null,
                                                    duration: exercise.duration ?? null,
                                                    order: 0
                                                };
                                                updates.sets = [defaultSet];
                                            }
                                            updateExercise(index, updates);
                                            delete editingWeightValues[exerciseId];
                                        }
                                        (event.target as HTMLInputElement).blur();
                                    }"
                                    @mousedown.stop
                                    @click.stop
                                    placeholder="20"
                                    class="h-8 text-sm"
                                    min="0"
                                />
                                <div
                                    v-else
                                    class="h-8 flex items-center justify-center text-sm text-neutral-500 bg-neutral-100 dark:bg-neutral-800 rounded-md border border-input"
                                >
                                    Poids de corps
                                </div>
                            </div>
                            
                            <!-- Repos -->
                            <div>
                                <Label class="text-xs text-neutral-500 mb-0.5 block">Repos</Label>
                                <Input
                                    type="text"
                                    :model-value="editingRestTimeValues[exercise.id || index] ?? (exercise.sets?.[0]?.rest_time ?? exercise.rest_time ?? '')"
                                    @update:model-value="(value: string) => {
                                        const exerciseId = exercise.id || index;
                                        editingRestTimeValues[exerciseId] = value;
                                    }"
                                    @blur="() => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingRestTimeValues[exerciseId];
                                        if (value !== undefined) {
                                            const updates: Partial<SessionExercise> = {};
                                            if (exercise.sets && exercise.sets.length > 0) {
                                                const updatedSets = [...exercise.sets];
                                                updatedSets[0] = { ...updatedSets[0], rest_time: value };
                                                updates.sets = updatedSets;
                                            } else {
                                                // Créer un set par défaut si les sets n'existent pas
                                                const defaultSet = {
                                                    set_number: 1,
                                                    repetitions: exercise.repetitions ?? null,
                                                    weight: exercise.weight ?? null,
                                                    rest_time: value,
                                                    duration: exercise.duration ?? null,
                                                    order: 0
                                                };
                                                updates.sets = [defaultSet];
                                            }
                                            updateExercise(index, updates);
                                            delete editingRestTimeValues[exerciseId];
                                        }
                                    }"
                                    @keydown.enter="(event: KeyboardEvent) => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingRestTimeValues[exerciseId];
                                        if (value !== undefined) {
                                            const updates: Partial<SessionExercise> = {};
                                            if (exercise.sets && exercise.sets.length > 0) {
                                                const updatedSets = [...exercise.sets];
                                                updatedSets[0] = { ...updatedSets[0], rest_time: value };
                                                updates.sets = updatedSets;
                                            } else {
                                                // Créer un set par défaut si les sets n'existent pas
                                                const defaultSet = {
                                                    set_number: 1,
                                                    repetitions: exercise.repetitions ?? null,
                                                    weight: exercise.weight ?? null,
                                                    rest_time: value,
                                                    duration: exercise.duration ?? null,
                                                    order: 0
                                                };
                                                updates.sets = [defaultSet];
                                            }
                                            updateExercise(index, updates);
                                            delete editingRestTimeValues[exerciseId];
                                        }
                                        (event.target as HTMLInputElement).blur();
                                    }"
                                    @mousedown.stop
                                    @click.stop
                                    placeholder="30s"
                                    class="h-8 text-sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Placeholder pour indiquer qu'on peut ajouter des exercices (affiché seulement lors du drag over) -->
                <div
                    v-if="isDraggingOver && block.exercises.length === 0"
                    class="border-2 border-dashed border-blue-300 rounded-lg flex items-center justify-center text-blue-400 text-xs min-h-[80px] bg-blue-50"
                >
                    Glissez un exercice ici
                </div>
            </div>
        </CardContent>
    </Card>
    
    <!-- Modal de confirmation pour supprimer un exercice -->
    <Dialog v-model:open="showRemoveExerciseDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <AlertTriangle class="h-5 w-5 text-red-600" />
                    Retirer l'exercice
                </DialogTitle>
                <DialogDescription>
                    Êtes-vous sûr de vouloir retirer <strong>{{ exerciseToRemoveName }}</strong> du bloc Super Set ? Cette action peut être annulée en ajoutant à nouveau l'exercice.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showRemoveExerciseDialog = false"
                >
                    Annuler
                </Button>
                <Button
                    variant="destructive"
                    @click="confirmRemoveExercise"
                >
                    Retirer
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
    
    <!-- Modal de confirmation pour supprimer le bloc entier -->
    <Dialog v-model:open="showRemoveBlockDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <AlertTriangle class="h-5 w-5 text-red-600" />
                    Supprimer le bloc Super Set
                </DialogTitle>
                <DialogDescription>
                    Êtes-vous sûr de vouloir supprimer ce bloc Super Set ? Tous les exercices qu'il contient ({{ block.exercises.length }}) seront également supprimés. Cette action peut être annulée en ajoutant à nouveau les exercices.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showRemoveBlockDialog = false"
                >
                    Annuler
                </Button>
                <Button
                    variant="destructive"
                    @click="confirmRemoveBlock"
                >
                    Supprimer
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

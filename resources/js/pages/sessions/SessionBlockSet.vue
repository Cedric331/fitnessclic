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
    'remove-exercise': [exerciseId: number];
    'update-exercise': [index: number, updates: Partial<SessionExercise>];
    'update-block-description': [description: string];
    'convert-to-standard': [];
    'remove-block': [];
}>();

// Valeur locale pour la description du bloc - permet la réactivité immédiate
const localBlockDescription = ref<string>(props.block.block_description || '');
const isEditingBlockDescription = ref(false);

watch(() => props.block.block_description, (newDescription) => {
    if (!isEditingBlockDescription.value) {
        localBlockDescription.value = newDescription || '';
    }
}, { immediate: true });

const blockExercises = computed(() => {
    return props.block.exercises;
});

watch(() => props.block.exercises, (newExercises, oldExercises) => {
    if (oldExercises && editingExerciseNameIds.value) {
        const preservedState: Record<number, boolean> = {};
        newExercises.forEach((newEx: SessionExercise) => {
            const oldEx = oldExercises.find((old: SessionExercise) => old.id === newEx.id);
            if (oldEx && editingExerciseNameIds.value[oldEx.id || -1]) {
                preservedState[newEx.id || -1] = true;
            }
        });
        Object.keys(preservedState).forEach(key => {
            editingExerciseNameIds.value[Number(key)] = true;
        });
    }
}, { deep: true });
const isDraggingOver = ref(false);
const showRemoveExerciseDialog = ref(false);
const showRemoveBlockDialog = ref(false);
const exerciseToRemoveIndex = ref<number | null>(null);
const editingExerciseNameIds = ref<Record<number, boolean>>({});

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

const editingNameValues = ref<Record<number, string>>({});

const editingRepetitionsValues = ref<Record<number, string>>({});
const editingDurationValues = ref<Record<number, string>>({});
const editingWeightValues = ref<Record<number, string>>({});
const editingRestTimeValues = ref<Record<number, string>>({});

const handleDrop = (event: DragEvent) => {
    event.stopPropagation();
    isDraggingOver.value = false;
    emit('drop', event, props.block.id);
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    if (event.dataTransfer && event.dataTransfer.types.includes('application/json')) {
        event.dataTransfer.dropEffect = 'copy';
        isDraggingOver.value = true;
    }
};

const handleDragLeave = (event: DragEvent) => {
    event.stopPropagation();
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const x = event.clientX;
    const y = event.clientY;
    if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
        isDraggingOver.value = false;
    }
};

const updateBlockDescription = (value: string) => {
    localBlockDescription.value = value;
    isEditingBlockDescription.value = true;
};

const saveBlockDescription = (value: string) => {
    localBlockDescription.value = value;
    isEditingBlockDescription.value = true;
    
    emit('update-block-description', value);
    
    setTimeout(() => {
        isEditingBlockDescription.value = false;
    }, 100);
};

const findExerciseIndex = (exercise: SessionExercise): number => {
    return props.block.exercises.indexOf(exercise);
};

const updateExercise = (exerciseIndex: number, updates: Partial<SessionExercise>) => {
    const exercise = props.block.exercises[exerciseIndex];
    
    if (exercise?.id && exercise.id > 0) {
        emit('update-exercise', exercise.id, updates);
    } else {
        emit('update-exercise', exerciseIndex, updates);
    }
};

const handleRemoveExerciseClick = (exerciseIndex: number) => {
    exerciseToRemoveIndex.value = exerciseIndex;
    showRemoveExerciseDialog.value = true;
};

const confirmRemoveExercise = () => {
    if (exerciseToRemoveIndex.value !== null) {
        const exercise = props.block.exercises[exerciseToRemoveIndex.value];
        if (exercise && exercise.id) {
            emit('remove-exercise', exercise.id);
        }
        showRemoveExerciseDialog.value = false;
        exerciseToRemoveIndex.value = null;
    }
};

const handleRemoveBlockClick = () => {
    showRemoveBlockDialog.value = true;
};

const confirmRemoveBlock = () => {
    emit('remove-block');
    showRemoveBlockDialog.value = false;
};

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
        <div v-if="displayIndex !== undefined" class="absolute -top-2 -left-2 z-10 flex items-center justify-center w-8 h-8 rounded-full bg-white text-black text-sm font-bold shadow-md border border-neutral-400">
            {{ displayIndex + 1 }}
        </div>
        <CardContent class="p-1.5">
            <div class="mb-2 flex items-start gap-2">
                <div v-if="draggable" class="flex flex-col items-center gap-0.5 flex-shrink-0 pt-0.5">
                    <div class="handle flex items-center justify-center cursor-move text-neutral-400 hover:text-blue-600 transition-all duration-200 p-1.5 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20" title="Glisser pour réorganiser">
                        <GripVertical class="h-5 w-5" />
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-row sm:hidden items-center gap-2 mb-1.5">
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
                    <div class="hidden sm:flex items-center justify-between mb-1.5">
                        <Label class="text-sm font-medium">Consignes d'exécution</Label>
                        <div class="flex items-center gap-2 flex-shrink-0">
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
                    <Label class="text-sm font-medium mb-1.5 block sm:hidden">Consignes d'exécution</Label>
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
                <div
                    v-for="(exercise, index) in props.block.exercises"
                    :key="`exercise-${exercise.id}-${exercise.use_duration}-${exercise.use_bodyweight}-${exercise.custom_exercise_name}`"
                    class="relative flex items-center gap-2 p-2 bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg hover:shadow-md transition-shadow"
                    @mousedown.stop
                    @click.stop
                >
                    <div class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-neutral-100 dark:bg-neutral-700">
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
                    
                    <Button
                        variant="ghost"
                        size="sm"
                        class="absolute top-2 right-2 h-6 w-6 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 z-10"
                        @click.stop="handleRemoveExerciseClick(index)"
                        @mousedown.stop
                    >
                        <X class="h-3.5 w-3.5" />
                    </Button>

                    <div class="flex-1 min-w-0 pr-6">
                        <div class="mb-1.5">
                            <div v-if="!editingExerciseNameIdsValue[exercise.id || index]" class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click.stop="() => {
                                        const exerciseId = exercise.id || index;
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
                                    @update:model-value="(value: string | number) => {
                                        const exerciseId = exercise.id || index;
                                        editingNameValues[exerciseId] = String(value);
                                    }"
                                    @blur="() => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingNameValues[exerciseId];
                                        if (value !== undefined) {
                                            updateExercise(index, { custom_exercise_name: value || null });
                                            delete editingNameValues[exerciseId];
                                        }
                                        editingExerciseNameIdsValue[exerciseId] = false;
                                    }"
                                    @keydown.enter="(event: KeyboardEvent) => {
                                        const exerciseId = exercise.id || index;
                                        const value = editingNameValues[exerciseId];
                                        if (value !== undefined) {
                                            updateExercise(index, { custom_exercise_name: value || null });
                                            delete editingNameValues[exerciseId];
                                        }
                                        editingExerciseNameIdsValue[exerciseId] = false;
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
                                        editingExerciseNameIdsValue[exerciseId] = false;
                                    }"
                                    @mousedown.stop
                                    class="p-1 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors flex-shrink-0"
                                >
                                    <X class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                </button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-1.5">
                            <div>
                                <Label class="text-xs text-neutral-500 mb-0.5 block">Série</Label>
                                <Input
                                    type="number"
                                    :model-value="exercise.sets_count ?? undefined"
                                    @update:model-value="(value: string | number) => updateExercise(index, { sets_count: value ? parseInt(value as string) : null })"
                                    @mousedown.stop
                                    @click.stop
                                    placeholder="Nombre"
                                    class="h-8 text-sm dark:text-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md"
                                    min="1"
                                />
                            </div>
                            
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
                                    class="h-8 text-sm dark:text-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md"
                                    min="0"
                                />
                                <Input
                                    v-else
                                    type="text"
                                    :model-value="editingDurationValues[exercise.id || index] ?? (exercise.sets?.[0]?.duration ?? exercise.duration ?? '')"
                                    @update:model-value="(value: string | number) => {
                                        const exerciseId = exercise.id || index;
                                        editingDurationValues[exerciseId] = String(value);
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
                                                    repetitions: exercise.repetitions ?? 10,
                                                    weight: exercise.weight ?? 20,
                                                    rest_time: exercise.rest_time ?? '30',
                                                    duration: value || '30s',
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
                                                    repetitions: exercise.repetitions ?? 10,
                                                    weight: exercise.weight ?? 20,
                                                    rest_time: exercise.rest_time ?? '30',
                                                    duration: value || '30s',
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
                                    class="h-8 text-sm dark:text-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md"
                                />
                            </div>
                            
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
                                    class="h-8 text-sm dark:text-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md"
                                    min="0"
                                />
                                <div
                                    v-else
                                    class="h-8 flex items-center justify-center text-sm text-neutral-500 bg-neutral-100 dark:bg-neutral-800 rounded-md border border-input"
                                >
                                    Poids de corps
                                </div>
                            </div>
                            
                            <div>
                                <Label class="text-xs text-neutral-500 mb-0.5 block">Repos</Label>
                                <Input
                                    type="text"
                                    :model-value="editingRestTimeValues[exercise.id || index] ?? (exercise.sets?.[0]?.rest_time ?? exercise.rest_time ?? '')"
                                    @update:model-value="(value: string | number) => {
                                        const exerciseId = exercise.id || index;
                                        editingRestTimeValues[exerciseId] = String(value);
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
                                    placeholder="30"
                                    class="h-8 text-sm dark:text-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-md"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div
                    v-if="isDraggingOver && block.exercises.length === 0"
                    class="border-2 border-dashed border-blue-300 rounded-lg flex items-center justify-center text-blue-400 text-xs min-h-[80px] bg-blue-50"
                >
                    Glissez un exercice ici
                </div>
            </div>
        </CardContent>
    </Card>
    
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

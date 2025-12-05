<script setup lang="ts">
import { computed, ref } from 'vue';
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
import { X, List, AlertTriangle, GripVertical, ChevronUp, ChevronDown } from 'lucide-vue-next';
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
    'move-up': [];
    'move-down': [];
}>();

const blockDescription = computed(() => props.block.block_description || '');
const isDraggingOver = ref(false);
const showRemoveExerciseDialog = ref(false);
const showRemoveBlockDialog = ref(false);
const exerciseToRemoveIndex = ref<number | null>(null);

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

const updateBlockDescription = (value: string) => {
    emit('update-block-description', value);
};

// Trouver l'index de l'exercice dans sessionExercises
const findExerciseIndex = (exercise: SessionExercise): number => {
    return props.block.exercises.indexOf(exercise);
};

// Mettre à jour un exercice du bloc
const updateExercise = (exerciseIndex: number, updates: Partial<SessionExercise>) => {
    emit('update-exercise', exerciseIndex, updates);
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
        class="relative superset-block"
    >
        <!-- Numéro de bloc en haut à gauche -->
        <div v-if="displayIndex !== undefined" class="absolute -top-2 -left-2 z-10 flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold shadow-md">
            {{ displayIndex + 1 }}
        </div>
        <CardContent class="p-4">
            <!-- En-tête du bloc avec boutons en haut à droite -->
            <div class="flex items-center justify-end gap-2 mb-3">
                <!-- Toggle Standard -->
                <Button
                    variant="outline"
                    size="sm"
                    class="text-xs"
                    @click.stop="emit('convert-to-standard')"
                    title="Convertir en exercices standard"
                >
                    <List class="h-3 w-3 mr-1" />
                    Standard
                </Button>
                <!-- Bouton supprimer le bloc entier -->
                <Button
                    v-if="draggable"
                    variant="ghost"
                    size="sm"
                    class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                    @click.stop="handleRemoveBlockClick"
                    @mousedown.stop
                    title="Supprimer le bloc entier"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
            
            <!-- Consignes pour l'ensemble du bloc avec icônes de drag and drop alignées -->
            <div class="mb-4 flex items-start gap-3">
                <!-- Poignée de drag et boutons de déplacement (si draggable) -->
                <div v-if="draggable" class="flex flex-col items-center gap-1 flex-shrink-0 pt-6">
                    <button
                        type="button"
                        @click.stop.prevent="emit('move-up')"
                        class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded transition-colors text-neutral-400 hover:text-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="displayIndex === 0"
                        title="Déplacer vers le haut"
                    >
                        <ChevronUp class="h-3 w-3" />
                    </button>
                    <div class="handle flex items-center justify-center cursor-move text-neutral-400 hover:text-blue-600 transition-all duration-200 p-1.5 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20" title="Glisser pour réorganiser">
                        <GripVertical class="h-5 w-5" />
                    </div>
                    <button
                        type="button"
                        @click.stop.prevent="emit('move-down')"
                        class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded transition-colors text-neutral-400 hover:text-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="displayIndex === undefined || (totalCount !== undefined && displayIndex >= totalCount - 1)"
                        title="Déplacer vers le bas"
                    >
                        <ChevronDown class="h-3 w-3" />
                    </button>
                </div>
                <!-- Consignes -->
                <div class="flex-1">
                    <Label class="text-sm font-medium mb-2 block">Consignes pour l'ensemble du bloc</Label>
                    <Textarea
                        :model-value="blockDescription"
                        @update:model-value="updateBlockDescription"
                        placeholder="Ajouter des consignes pour l'ensemble du bloc Super Set..."
                        :rows="2"
                        class="text-sm"
                    />
                </div>
            </div>
            
            <!-- Zone de drop pour ajouter des exercices -->
            <div
                class="space-y-2 p-2 border-2 border-dashed rounded-lg transition-colors"
                :class="{ 
                    'border-blue-500 bg-blue-50': isDraggingOver, 
                    'border-neutral-300': !isDraggingOver,
                    'min-h-[100px]': block.exercises.length === 0 || isDraggingOver
                }"
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop.prevent="handleDrop"
            >
                <!-- Exercices du bloc -->
                <div
                    v-for="(exercise, index) in block.exercises"
                    :key="exercise.id || `ex-${exercise.exercise_id}-${index}`"
                    class="flex items-center gap-3 p-3 bg-white border border-neutral-200 rounded-lg hover:shadow-md transition-shadow"
                >
                    <!-- Image de l'exercice -->
                    <div class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-neutral-100">
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
                    
                    <!-- Informations de l'exercice et paramètres -->
                    <div class="flex-1 min-w-0">
                        <!-- Titre de l'exercice -->
                        <h4 class="text-sm font-semibold mb-2 line-clamp-1">
                            {{ exercise.exercise?.title || 'Exercice' }}
                        </h4>
                        
                        <!-- Ligne avec Serie, Rep, Charge, Repos -->
                        <div class="grid grid-cols-4 gap-2">
                            <!-- Série -->
                            <div>
                                <Label class="text-xs text-neutral-500 mb-1 block">Série</Label>
                                <Input
                                    type="number"
                                    :model-value="exercise.sets_count"
                                    @update:model-value="(value: string | number) => updateExercise(index, { sets_count: value ? parseInt(value as string) : null })"
                                    placeholder="Nombre"
                                    class="h-8 text-sm"
                                    min="1"
                                />
                            </div>
                            
                            <!-- Répétitions -->
                            <div>
                                <Label class="text-xs text-neutral-500 mb-1 block">Rep</Label>
                                <Input
                                    type="number"
                                    :model-value="exercise.sets?.[0]?.repetitions ?? exercise.repetitions"
                                    @update:model-value="(value: string | number) => {
                                        const updates: Partial<SessionExercise> = {};
                                        if (exercise.sets && exercise.sets.length > 0) {
                                            const updatedSets = [...exercise.sets];
                                            updatedSets[0] = { ...updatedSets[0], repetitions: value ? parseInt(value as string) : null };
                                            updates.sets = updatedSets;
                                        } else {
                                            updates.repetitions = value ? parseInt(value as string) : null;
                                        }
                                        updateExercise(index, updates);
                                    }"
                                    placeholder="10"
                                    class="h-8 text-sm"
                                    min="0"
                                />
                            </div>
                            
                            <!-- Charge (poids) -->
                            <div>
                                <Label class="text-xs text-neutral-500 mb-1 block">Charge (kg)</Label>
                                <Input
                                    type="number"
                                    step="0.5"
                                    :model-value="exercise.sets?.[0]?.weight ?? exercise.weight"
                                    @update:model-value="(value: string | number) => {
                                        const updates: Partial<SessionExercise> = {};
                                        if (exercise.sets && exercise.sets.length > 0) {
                                            const updatedSets = [...exercise.sets];
                                            updatedSets[0] = { ...updatedSets[0], weight: value ? parseFloat(value as string) : null };
                                            updates.sets = updatedSets;
                                        } else {
                                            updates.weight = value ? parseFloat(value as string) : null;
                                        }
                                        updateExercise(index, updates);
                                    }"
                                    placeholder="20"
                                    class="h-8 text-sm"
                                    min="0"
                                />
                            </div>
                            
                            <!-- Repos -->
                            <div>
                                <Label class="text-xs text-neutral-500 mb-1 block">Repos</Label>
                                <Input
                                    type="text"
                                    :model-value="exercise.sets?.[0]?.rest_time ?? exercise.rest_time"
                                    @update:model-value="(value: string) => {
                                        const updates: Partial<SessionExercise> = {};
                                        if (exercise.sets && exercise.sets.length > 0) {
                                            const updatedSets = [...exercise.sets];
                                            updatedSets[0] = { ...updatedSets[0], rest_time: value };
                                            updates.sets = updatedSets;
                                        } else {
                                            updates.rest_time = value;
                                        }
                                        updateExercise(index, updates);
                                    }"
                                    placeholder="30s"
                                    class="h-8 text-sm"
                                />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bouton supprimer -->
                    <Button
                        variant="ghost"
                        size="sm"
                        class="flex-shrink-0 text-red-600 hover:text-red-700 hover:bg-red-50"
                        @click.stop="handleRemoveExerciseClick(index)"
                        @mousedown.stop
                    >
                        <X class="h-4 w-4" />
                    </Button>
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

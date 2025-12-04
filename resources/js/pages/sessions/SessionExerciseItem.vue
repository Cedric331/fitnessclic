<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { 
    GripVertical, 
    X, 
    ChevronUp,
    ChevronDown,
    AlertTriangle,
    Plus,
    Trash2
} from 'lucide-vue-next';
import type { SessionExercise, ExerciseSet } from './types';

const props = defineProps<{
    sessionExercise: SessionExercise;
    index: number;
    draggable?: boolean;
    totalCount?: number;
}>();

const emit = defineEmits<{
    update: [updates: Partial<SessionExercise>];
    remove: [];
    moveUp: [];
    moveDown: [];
}>();

const showRemoveDialog = ref(false);
const exercise = computed(() => props.sessionExercise.exercise);

// Initialiser les séries si elles n'existent pas
const sets = computed(() => {
    if (props.sessionExercise.sets && props.sessionExercise.sets.length > 0) {
        return props.sessionExercise.sets;
    }
    // Si pas de séries, créer une série par défaut
    return [{
        set_number: 1,
        repetitions: props.sessionExercise.repetitions || null,
        weight: props.sessionExercise.weight || null,
        rest_time: props.sessionExercise.rest_time || null,
        duration: props.sessionExercise.duration || null,
        order: 0
    }];
});

const updateField = (field: keyof SessionExercise, value: any) => {
    emit('update', { [field]: value });
};

const updateSet = (setIndex: number, field: keyof ExerciseSet, value: any) => {
    const updatedSets = [...sets.value];
    if (updatedSets[setIndex]) {
        updatedSets[setIndex] = { ...updatedSets[setIndex], [field]: value };
        emit('update', { sets: updatedSets });
    }
};

const addSet = () => {
    const newSet: ExerciseSet = {
        set_number: sets.value.length + 1,
        repetitions: null,
        weight: null,
        rest_time: null,
        duration: null,
        order: sets.value.length
    };
    const updatedSets = [...sets.value, newSet];
    emit('update', { sets: updatedSets });
};

const removeSet = (setIndex: number) => {
    if (sets.value.length <= 1) return; // Garder au moins une série
    const updatedSets = sets.value.filter((_, index) => index !== setIndex);
    // Renuméroter les séries
    updatedSets.forEach((set, index) => {
        set.set_number = index + 1;
        set.order = index;
    });
    emit('update', { sets: updatedSets });
};

const handleRemoveClick = () => {
    showRemoveDialog.value = true;
};

const confirmRemove = () => {
    emit('remove');
    showRemoveDialog.value = false;
};

// Format pour le numéro de série (1er, 2e, 3e, etc.)
const getSetLabel = (setNumber: number) => {
    if (setNumber === 1) return '1er';
    return `${setNumber}e`;
};
</script>

<template>
    <div class="relative group">
        <Card class="transform transition-all duration-200">
            <!-- Numéro d'exercice en haut à gauche -->
            <div class="absolute -top-2 -left-2 z-10 flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold shadow-md">
                {{ index + 1 }}
            </div>
            <CardContent class="p-2">
                <!-- En-tête : Image, nom, commentaires, bouton supprimer -->
                <div class="flex items-start gap-3 mb-3">
                    <!-- Poignée de drag et boutons de déplacement -->
                    <div 
                        class="flex flex-col items-center gap-1 pt-1"
                    >
                        <button
                            type="button"
                            @click.stop.prevent="emit('moveUp')"
                            class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded transition-colors text-neutral-400 hover:text-blue-600 disabled:opacity-30 disabled:cursor-not-allowed"
                            :disabled="index === 0"
                            title="Déplacer vers le haut"
                        >
                            <ChevronUp class="h-3 w-3" />
                        </button>
                        <div 
                            v-if="draggable"
                            class="handle flex items-center justify-center cursor-move text-neutral-400 hover:text-blue-600 transition-all duration-200 p-1.5 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20"
                            title="Glisser pour réorganiser"
                        >
                            <GripVertical class="h-5 w-5" />
                        </div>
                        <div 
                            v-else
                            class="select-none text-neutral-300"
                        >
                            <GripVertical class="h-5 w-5" />
                        </div>
                        <button
                            type="button"
                            @click.stop.prevent="emit('moveDown')"
                            class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded transition-colors text-neutral-400 hover:text-blue-600 disabled:opacity-30 disabled:cursor-not-allowed"
                            :disabled="totalCount !== undefined && index === totalCount - 1"
                            title="Déplacer vers le bas"
                        >
                            <ChevronDown class="h-3 w-3" />
                        </button>
                    </div>

                    <!-- Image de l'exercice -->
                    <div 
                        class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-neutral-100 dark:bg-neutral-800"
                    >
                        <img
                            v-if="exercise?.image_url"
                            :src="exercise.image_url"
                            :alt="exercise?.title"
                            class="w-full h-full object-contain"
                            draggable="false"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center text-neutral-400"
                        >
                            <span class="text-xs">Aucune image</span>
                        </div>
                    </div>

                    <!-- Nom de l'exercice -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-base mb-2">
                            {{ exercise?.title || 'Exercice' }}
                        </h3>
                        
                        <!-- Commentaires/Consignes d'exécution -->
                        <div class="mb-2">
                            <Textarea
                                :model-value="sessionExercise.description || ''"
                                @update:model-value="(value: string) => updateField('description', value)"
                                @mousedown.stop
                                @dragstart.stop
                                placeholder="Consignes d'exécution..."
                                :rows="2"
                                class="text-sm"
                            />
                        </div>
                    </div>

                    <!-- Bouton supprimer -->
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20 flex-shrink-0"
                        @click.stop="handleRemoveClick"
                        @mousedown.stop
                        @dragstart.stop
                    >
                        <X class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Séries multiples -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between mb-2">
                        <Label class="text-sm font-medium">Séries</Label>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="addSet"
                            class="h-7 text-xs"
                        >
                            <Plus class="h-3 w-3 mr-1" />
                            Ajouter une série
                        </Button>
                    </div>

                    <div class="space-y-2">
                        <div
                            v-for="(set, setIndex) in sets"
                            :key="setIndex"
                            class="flex items-center gap-2 p-2 bg-neutral-50 dark:bg-neutral-800/50 rounded-md"
                        >
                            <!-- Numéro de série -->
                            <div class="flex-shrink-0 w-12 text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                {{ getSetLabel(set.set_number) }}
                            </div>

                            <!-- Répétitions -->
                            <div class="flex-1">
                                <Label class="text-xs text-neutral-500 mb-1 block">Rep</Label>
                                <Input
                                    type="number"
                                    :model-value="set.repetitions"
                                    @update:model-value="(value: string | number) => updateSet(setIndex, 'repetitions', value ? parseInt(value as string) : null)"
                                    @mousedown.stop
                                    @dragstart.stop
                                    placeholder="10"
                                    class="h-8 text-sm"
                                    min="0"
                                />
                            </div>

                            <!-- Charge (poids) -->
                            <div class="flex-1">
                                <Label class="text-xs text-neutral-500 mb-1 block">Charge (kg)</Label>
                                <Input
                                    type="number"
                                    step="0.5"
                                    :model-value="set.weight"
                                    @update:model-value="(value: string | number) => updateSet(setIndex, 'weight', value ? parseFloat(value as string) : null)"
                                    @mousedown.stop
                                    @dragstart.stop
                                    placeholder="20"
                                    class="h-8 text-sm"
                                    min="0"
                                />
                            </div>

                            <!-- Repos -->
                            <div class="flex-1">
                                <Label class="text-xs text-neutral-500 mb-1 block">Repos</Label>
                                <Input
                                    type="text"
                                    :model-value="set.rest_time"
                                    @update:model-value="(value: string) => updateSet(setIndex, 'rest_time', value)"
                                    @mousedown.stop
                                    @dragstart.stop
                                    placeholder="30s"
                                    class="h-8 text-sm"
                                />
                            </div>

                            <!-- Bouton supprimer la série -->
                            <Button
                                v-if="sets.length > 1"
                                variant="ghost"
                                size="sm"
                                @click="removeSet(setIndex)"
                                class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300"
                            >
                                <Trash2 class="h-3 w-3" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Modal de confirmation de suppression -->
    <Dialog v-model:open="showRemoveDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <AlertTriangle class="h-5 w-5 text-red-600" />
                    Retirer l'exercice
                </DialogTitle>
                <DialogDescription>
                    Êtes-vous sûr de vouloir retirer <strong>{{ exercise?.title || 'cet exercice' }}</strong> de la séance ?
                    Cette action peut être annulée en ajoutant à nouveau l'exercice.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showRemoveDialog = false"
                >
                    Annuler
                </Button>
                <Button
                    variant="destructive"
                    @click="confirmRemove"
                >
                    Retirer
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

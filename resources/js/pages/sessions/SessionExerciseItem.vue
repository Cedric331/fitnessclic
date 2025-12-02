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
    RotateCcw, 
    Clock, 
    Pause,
    ChevronUp,
    ChevronDown,
    AlertTriangle
} from 'lucide-vue-next';
import type { SessionExercise } from './types';

const props = defineProps<{
    sessionExercise: SessionExercise;
    index: number;
    draggable?: boolean;
    isDragging?: boolean;
    isDragOver?: boolean;
}>();

const emit = defineEmits<{
    update: [updates: Partial<SessionExercise>];
    remove: [];
    moveUp: [];
    moveDown: [];
    dragstart: [event: DragEvent, index: number];
    dragover: [event: DragEvent, index: number];
    dragleave: [];
    drop: [event: DragEvent, index: number];
    dragend: [];
}>();

const isExpanded = ref(true);
const showRemoveDialog = ref(false);

const exercise = computed(() => props.sessionExercise.exercise);

const updateField = (field: keyof SessionExercise, value: any) => {
    emit('update', { [field]: value });
};

const handleRemoveClick = () => {
    showRemoveDialog.value = true;
};

const confirmRemove = () => {
    emit('remove');
    showRemoveDialog.value = false;
};

// Log pour déboguer
watch(() => props.sessionExercise, () => {
    console.log('SessionExerciseItem rendu pour:', props.sessionExercise?.exercise?.title, 'index:', props.index);
}, { immediate: true });
</script>

<template>
    <div
        :class="{
            'relative group hover:shadow-md transition-shadow': true,
            'opacity-50': isDragging,
            'ring-2 ring-blue-400': isDragOver
        }"
        @dragover.prevent="emit('dragover', $event, index)"
        @dragleave="emit('dragleave')"
        @drop.prevent="emit('drop', $event, index)"
    >
        <Card>
            <CardContent class="p-4">
            <!-- En-tête de l'exercice -->
            <div class="flex items-start gap-4 mb-4">
                <!-- Poignée de drag -->
                <div 
                    class="flex flex-col items-center gap-1 pt-1 cursor-move text-neutral-400 hover:text-neutral-600"
                >
                    <button
                        type="button"
                        @click.stop.prevent="emit('moveUp')"
                        class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded"
                        :disabled="index === 0"
                        title="Déplacer vers le haut"
                    >
                        <ChevronUp class="h-3 w-3" />
                    </button>
                    <div 
                        v-if="draggable"
                        :draggable="true"
                        class="drag-handle select-none cursor-move text-neutral-400 hover:text-neutral-600"
                        @dragstart="emit('dragstart', $event, index)"
                        @dragend="emit('dragend')"
                    >
                        <GripVertical class="h-5 w-5" title="Glisser pour réorganiser" />
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
                        class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded"
                        title="Déplacer vers le bas"
                    >
                        <ChevronDown class="h-3 w-3" />
                    </button>
                </div>

                <!-- Image de l'exercice -->
                <div 
                    class="flex-shrink-0 w-24 h-24 rounded-lg overflow-hidden bg-neutral-100 dark:bg-neutral-800"
                >
                    <img
                        v-if="exercise?.image_url"
                        :src="exercise.image_url"
                        :alt="exercise?.title"
                        class="w-full h-full object-cover"
                        draggable="false"
                    />
                    <div
                        v-else
                        class="w-full h-full flex items-center justify-center text-neutral-400"
                    >
                        <span class="text-xs">Aucune image</span>
                    </div>
                </div>

                <!-- Titre et actions -->
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-base mb-1 line-clamp-2">
                        {{ exercise?.title || 'Exercice' }}
                    </h3>
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Nombre de séries -->
                        <div class="flex items-center gap-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 px-2.5 py-1 rounded-full text-xs">
                            <RotateCcw class="h-3.5 w-3.5" />
                            <input
                                type="number"
                                :value="sessionExercise.sets || 1"
                                @input="updateField('sets', parseInt(($event.target as HTMLInputElement).value) || 1)"
                                @mousedown.stop
                                @dragstart.stop
                                min="1"
                                class="w-8 bg-transparent border-none p-0 text-center focus:outline-none"
                            />
                            <span>série(s)</span>
                        </div>

                        <!-- Répétitions -->
                        <div class="flex items-center gap-1.5 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-2.5 py-1 rounded-full text-xs">
                            <Clock class="h-3.5 w-3.5" />
                            <input
                                type="text"
                                :value="sessionExercise.repetitions || ''"
                                @input="updateField('repetitions', ($event.target as HTMLInputElement).value)"
                                @mousedown.stop
                                @dragstart.stop
                                placeholder="10"
                                class="w-12 bg-transparent border-none p-0 text-center focus:outline-none placeholder:text-green-500"
                            />
                            <span>reps</span>
                        </div>

                        <!-- Temps de repos -->
                        <div class="flex items-center gap-1.5 bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 px-2.5 py-1 rounded-full text-xs">
                            <Pause class="h-3.5 w-3.5" />
                            <input
                                type="text"
                                :value="sessionExercise.rest_time || ''"
                                @input="updateField('rest_time', ($event.target as HTMLInputElement).value)"
                                @mousedown.stop
                                @dragstart.stop
                                placeholder="30s"
                                class="w-12 bg-transparent border-none p-0 text-center focus:outline-none placeholder:text-orange-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Bouton supprimer -->
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                    @click.stop="handleRemoveClick"
                    @mousedown.stop
                    @dragstart.stop
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>

            <!-- Description -->
            <div class="space-y-2">
                <Label class="text-sm">Description (optionnel)</Label>
                <Textarea
                    :model-value="sessionExercise.description || ''"
                    @update:model-value="(value) => updateField('description', value)"
                    @mousedown.stop
                    @dragstart.stop
                    placeholder="Ajouter une description pour cet exercice..."
                    :rows="2"
                    class="text-sm"
                />
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


<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
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
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { 
    GripVertical, 
    X, 
    AlertTriangle,
    Plus,
    Trash2,
    ArrowLeftRight,
    RotateCw,
    Pencil
} from 'lucide-vue-next';
import type { SessionExercise, ExerciseSet } from './types';

const props = defineProps<{
    sessionExercise: SessionExercise;
    index: number;
    displayIndex?: number;
    draggable?: boolean;
    totalCount?: number;
}>();

const emit = defineEmits<{
    update: [updates: Partial<SessionExercise>];
    remove: [];
    convertToSet: [];
}>();

const showRemoveDialog = ref(false);
const isEditingName = ref(false);
const exercise = computed(() => props.sessionExercise.exercise);

const sets = computed(() => {
    if (props.sessionExercise.sets && Array.isArray(props.sessionExercise.sets) && props.sessionExercise.sets.length > 0) {
        return props.sessionExercise.sets;
    }

    return [];
});

onMounted(() => {
    if (!props.sessionExercise.sets || !Array.isArray(props.sessionExercise.sets) || props.sessionExercise.sets.length === 0) {
        const defaultSet = [{
            set_number: 1,
            repetitions: props.sessionExercise.repetitions ?? null,
            weight: props.sessionExercise.weight ?? null,
            rest_time: props.sessionExercise.rest_time ?? null,
            duration: props.sessionExercise.duration ?? null,
            use_duration: props.sessionExercise.use_duration ?? false,
            use_bodyweight: props.sessionExercise.use_bodyweight ?? false,
            order: 0
        }];
        emit('update', { sets: defaultSet });
    }
});

const updateField = (field: keyof SessionExercise, value: any) => {
    emit('update', { [field]: value });
};

const updateSet = (setIndex: number, field: keyof ExerciseSet, value: any) => {
    let currentSets: ExerciseSet[];
    
    const hasSets = props.sessionExercise.sets && Array.isArray(props.sessionExercise.sets) && props.sessionExercise.sets.length > 0;
    
    if (hasSets) {
        currentSets = props.sessionExercise.sets!.map((set: any) => ({ 
            set_number: set.set_number ?? 1,
            repetitions: set.repetitions ?? null,
            weight: set.weight ?? null,
            rest_time: set.rest_time ?? null,
            duration: set.duration ?? null,
            use_duration: set.use_duration !== undefined ? set.use_duration : (props.sessionExercise.use_duration ?? false),
            use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (props.sessionExercise.use_bodyweight ?? false),
            order: set.order ?? 0
        } as ExerciseSet));
    } else {
        currentSets = [{
            set_number: 1,
            repetitions: props.sessionExercise.repetitions ?? null,
            weight: props.sessionExercise.weight ?? null,
            rest_time: props.sessionExercise.rest_time ?? null,
            duration: props.sessionExercise.duration ?? null,
            use_duration: props.sessionExercise.use_duration ?? false,
            use_bodyweight: props.sessionExercise.use_bodyweight ?? false,
            order: 0
        } as ExerciseSet];
    }
    
    if (!currentSets[setIndex]) {
        while (currentSets.length <= setIndex) {
            currentSets.push({
                set_number: currentSets.length + 1,
                repetitions: null,
                weight: null,
                rest_time: null,
                duration: null,
                use_duration: props.sessionExercise.use_duration ?? false,
                use_bodyweight: props.sessionExercise.use_bodyweight ?? false,
                order: currentSets.length
            } as ExerciseSet);
        }
    }
    
    currentSets[setIndex] = { ...currentSets[setIndex], [field]: value };
    
    emit('update', { sets: currentSets });
};

const addSet = () => {
    let currentSets: ExerciseSet[];
    if (props.sessionExercise.sets && props.sessionExercise.sets.length > 0) {
        currentSets = [...props.sessionExercise.sets];
        const newSetNumber = 1;
        const newSet = {
            set_number: newSetNumber,
            repetitions: null,
            weight: null,
            rest_time: null,
            duration: null,
            use_duration: props.sessionExercise.use_duration ?? false,
            use_bodyweight: props.sessionExercise.use_bodyweight ?? false,
            order: currentSets.length
        } as ExerciseSet;
        const updatedSets = [...currentSets, newSet];
        emit('update', { sets: updatedSets });
    } else {
        const firstSet = {
            set_number: 1,
            repetitions: props.sessionExercise.repetitions ?? null,
            weight: props.sessionExercise.weight ?? null,
            rest_time: props.sessionExercise.rest_time ?? null,
            duration: props.sessionExercise.duration ?? null,
            use_duration: props.sessionExercise.use_duration ?? false,
            use_bodyweight: props.sessionExercise.use_bodyweight ?? false,
            order: 0
        } as ExerciseSet;
        emit('update', { sets: [firstSet] });
    }
};

const removeSet = (setIndex: number) => {
    if (!props.sessionExercise.sets || props.sessionExercise.sets.length === 0) {
        return;
    }
    
    const currentSets = [...props.sessionExercise.sets];
    const updatedSets = currentSets.filter((_, index) => index !== setIndex);
    if (updatedSets.length === 0) {
        emit('update', { sets: [] });
        return;
    }
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
</script>

<template>
    <div class="relative group">
        <Card class="transform transition-all duration-200 hover:shadow-lg hover:bg-neutral-50/50 dark:hover:bg-neutral-800/50 py-3">
            <!-- Numéro d'exercice en haut à gauche -->
            <div class="absolute -top-2 -left-2 z-10 flex items-center justify-center w-8 h-8 rounded-full bg-white text-black text-sm font-bold shadow-md border border-neutral-400">
                {{ (displayIndex !== undefined ? displayIndex : index) + 1 }}
            </div>
            <CardContent class="p-1.5">
                <!-- Contenu principal : Image, nom, commentaires avec icônes de drag and drop alignées -->
                <div class="flex items-end gap-2 mb-0">
                    <!-- Poignée de drag -->
                    <div 
                        class="flex flex-col items-center gap-0.5 flex-shrink-0 pt-0.5"
                    >
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
                    </div>

                    <!-- Image de l'exercice -->
                    <div 
                        class="flex-shrink-0 w-24 h-24 rounded-lg overflow-hidden bg-neutral-100 dark:bg-neutral-800"
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
                        <!-- Boutons au-dessus sur petit écran -->
                        <div class="flex flex-row sm:hidden items-center gap-2 mb-1.5">
                            <!-- Toggle Super Set -->
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="text-xs h-7"
                                        @click.stop="emit('convertToSet')"
                                        @mousedown.stop
                                        @dragstart.stop
                                    >
                                        <ArrowLeftRight class="h-3 w-3 mr-1" />
                                        Super Set
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p class="max-w-xs">
                                        Convertir en bloc Super Set pour regrouper plusieurs exercices et insérer plusieurs images dans le cadre
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                            <!-- Bouton supprimer -->
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-7 w-7 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                                @click.stop="handleRemoveClick"
                                @mousedown.stop
                                @dragstart.stop
                                title="Retirer l'exercice"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                        
                        <!-- Nom personnalisé de l'exercice avec boutons Super Set et suppression -->
                        <div class="mb-1.5">
                            <div v-if="!isEditingName" class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click.stop="isEditingName = true"
                                    class="p-1 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors flex-shrink-0"
                                    title="Modifier le nom"
                                >
                                    <Pencil class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                </button>
                                <h3 class="text-sm font-semibold flex-1 min-w-0 truncate">
                                    {{ sessionExercise.custom_exercise_name || exercise?.title || 'Exercice' }}
                                </h3>
                                <!-- Toggle Super Set - Desktop uniquement -->
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="hidden sm:flex text-xs h-7 flex-shrink-0"
                                            @click.stop="emit('convertToSet')"
                                            @mousedown.stop
                                            @dragstart.stop
                                        >
                                            <ArrowLeftRight class="h-3 w-3 mr-1" />
                                            Super Set
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p class="max-w-xs">
                                            Convertir en bloc Super Set pour regrouper plusieurs exercices dans le cadre
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                                <!-- Bouton supprimer - Desktop uniquement -->
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="hidden sm:flex h-7 w-7 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                                    @click.stop="handleRemoveClick"
                                    @mousedown.stop
                                    @dragstart.stop
                                    title="Retirer l'exercice"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                            <div v-else class="flex items-center gap-2">
                                <Input
                                    :model-value="sessionExercise.custom_exercise_name || exercise?.title || 'Exercice'"
                                    @update:model-value="(value: string) => {
                                        updateField('custom_exercise_name', value || null);
                                        if (!value) isEditingName = false;
                                    }"
                                    @blur="isEditingName = false"
                                    @keydown.enter="isEditingName = false"
                                    @mousedown.stop
                                    @dragstart.stop
                                    placeholder="Nom de l'exercice"
                                    class="text-sm font-semibold flex-1"
                                    autofocus
                                />
                                <button
                                    type="button"
                                    @click.stop="isEditingName = false"
                                    class="p-1 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors flex-shrink-0"
                                >
                                    <X class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                </button>
                            </div>
                        </div>
                        
                        <!-- Commentaires/Consignes de réalisationn -->
                        <div class="mb-1.5">
                            <Textarea
                                :model-value="sessionExercise.description || ''"
                                @update:model-value="(value: string) => updateField('description', value)"
                                @mousedown.stop
                                @dragstart.stop
                                placeholder="Consignes de réalisation..."
                                :rows="2"
                                class="text-sm w-full"
                            />
                        </div>
                    </div>
                </div>

                <!-- Séries multiples -->
                <div class="space-y-1.5">
                    <!-- Cas où il n'y a pas de ligne : afficher uniquement le bouton ajouter -->
                    <div v-if="!props.sessionExercise.sets || props.sessionExercise.sets.length === 0" class="p-2 bg-neutral-50 dark:bg-neutral-800/50 rounded-md">
                        <div class="grid grid-cols-[auto_1fr_1fr] sm:grid-cols-[auto_1fr_1fr_1fr_1fr_auto] gap-2 items-end">
                            <!-- Bouton ajouter une ligne -->
                            <div class="flex items-end">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="addSet"
                                    class="h-8 w-8 p-0 rounded-full bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 flex-shrink-0 flex items-center justify-center"
                                    title="Ajouter une ligne"
                                >
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                            <!-- Espaces vides pour aligner avec les autres lignes -->
                            <div></div>
                            <div></div>
                            <div class="hidden sm:block"></div>
                            <div class="hidden sm:block"></div>
                            <div class="hidden sm:block"></div>
                        </div>
                    </div>
                    
                    <div class="space-y-1.5 pt-2">
                        <!-- Afficher les sets réels de l'exercice -->
                        <div
                            v-for="(set, setIndex) in (props.sessionExercise.sets || [])"
                            :key="setIndex"
                            class="relative p-2 bg-neutral-50 dark:bg-neutral-800/50 rounded-md"
                        >
                            <!-- Bouton supprimer la série - fixe en haut à droite sur mobile -->
                            <Button
                                v-if="setIndex > 0"
                                variant="ghost"
                                size="sm"
                                @click="removeSet(setIndex)"
                                class="absolute -top-2 -right-1 sm:hidden h-6 w-6 p-0 rounded-full bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 z-10 flex items-center justify-center"
                                title="Supprimer cette ligne"
                            >
                                <X class="h-3.5 w-3.5" />
                            </Button>
                            
                            <!-- Champs organisés en grille responsive : 2 lignes x 3 colonnes sur mobile, 1 ligne x 6 colonnes sur desktop -->
                            <div class="grid grid-cols-[2rem_1fr_1fr] grid-rows-2 sm:grid-cols-[2rem_1fr_1fr_1fr_1fr_2rem] sm:grid-rows-1 gap-2 sm:gap-2 sm:items-end mt-2">
                                <!-- Bouton ajouter une ligne - visible uniquement sur la première ligne -->
                                <div v-if="setIndex === 0" class="hidden sm:flex items-end">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="addSet"
                                        class="h-8 w-8 p-0 rounded-full bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 flex-shrink-0 flex items-center justify-center"
                                        title="Ajouter une ligne"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </Button>
                                </div>
                                <!-- Espace vide pour les autres lignes sur desktop -->
                                <div v-if="setIndex !== 0" class="hidden sm:block"></div>
                                <!-- Bouton ajouter une ligne - mobile visible uniquement sur la première ligne, span 2 lignes -->
                                <div v-if="setIndex === 0" class="sm:hidden flex items-center row-start-1 row-span-2">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="addSet"
                                        class="h-8 w-8 p-0 rounded-full bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 flex-shrink-0 flex items-center justify-center"
                                        title="Ajouter une ligne"
                                    >
                                        <Plus class="h-4 w-4" />
                                    </Button>
                                </div>
                                <!-- Espace vide pour les autres lignes sur mobile, span 2 lignes -->
                                <div v-if="setIndex !== 0" class="sm:hidden block row-start-1 row-span-2"></div>
                                <!-- Numéro de série (éditable pour chaque ligne) - Mobile: ligne 1, colonne 2 -->
                                <div class="row-start-1 col-start-2 sm:row-start-1 sm:col-start-2">
                                    <Label class="text-xs text-neutral-500 mb-1 block">Série</Label>
                                    <Input
                                        type="number"
                                        :model-value="set.set_number"
                                        @update:model-value="(value: string | number) => {
                                            const numValue = value === '' || value === null || value === undefined ? 1 : parseInt(value as string);
                                            updateSet(setIndex, 'set_number', (numValue !== null && !isNaN(numValue) && numValue > 0) ? numValue : 1);
                                        }"
                                        @mousedown.stop
                                        @dragstart.stop
                                        placeholder="1"
                                        class="h-8 text-sm"
                                        min="1"
                                    />
                                </div>

                                <!-- Répétitions ou Durée (selon le switch) - Mobile: ligne 1, colonne 3 -->
                                <div class="row-start-1 col-start-3 sm:row-start-1 sm:col-start-3">
                                    <div class="flex items-center justify-between mb-1">
                                        <Label class="text-xs text-neutral-500">
                                            {{ (set.use_duration !== undefined ? set.use_duration : (props.sessionExercise.use_duration ?? false)) ? 'Durée (seconde)' : 'Rep' }}
                                        </Label>
                                        <button
                                            type="button"
                                            @click.stop="() => {
                                                const currentValue = set.use_duration !== undefined ? set.use_duration : (props.sessionExercise.use_duration ?? false);
                                                updateSet(setIndex, 'use_duration', !currentValue);
                                            }"
                                            class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors"
                                            title="Basculer entre Rep et Durée"
                                        >
                                            <RotateCw class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                        </button>
                                    </div>
                                    <Input
                                        v-if="!(set.use_duration !== undefined ? set.use_duration : (props.sessionExercise.use_duration ?? false))"
                                        type="number"
                                        :model-value="set.repetitions"
                                        @update:model-value="(value: string | number) => {
                                            const numValue = value === '' || value === null || value === undefined ? null : parseInt(value as string);
                                            updateSet(setIndex, 'repetitions', (numValue !== null && !isNaN(numValue)) ? numValue : null);
                                        }"
                                        @mousedown.stop
                                        @dragstart.stop
                                        placeholder="10"
                                        class="h-8 text-sm"
                                        min="0"
                                    />
                                    <Input
                                        v-else
                                        type="text"
                                        :model-value="set.duration"
                                        @update:model-value="(value: string) => updateSet(setIndex, 'duration', value || null)"
                                        @mousedown.stop
                                        @dragstart.stop
                                        placeholder="30s"
                                        class="h-8 text-sm"
                                    />
                                </div>

                                <!-- Charge (poids) ou Poids de corps (selon le switch) - Mobile: ligne 2, colonne 2 (sous Série) -->
                                <div class="row-start-2 col-start-2 sm:row-start-1 sm:col-start-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <Label class="text-xs text-neutral-500">
                                            {{ (set.use_bodyweight !== undefined ? set.use_bodyweight : (props.sessionExercise.use_bodyweight ?? false)) ? 'Poids de corps' : 'Charge (kg)' }}
                                        </Label>
                                        <button
                                            type="button"
                                            @click.stop="() => {
                                                const currentValue = set.use_bodyweight !== undefined ? set.use_bodyweight : (props.sessionExercise.use_bodyweight ?? false);
                                                updateSet(setIndex, 'use_bodyweight', !currentValue);
                                            }"
                                            class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors"
                                            title="Basculer entre Charge et Poids de corps"
                                        >
                                            <RotateCw class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                        </button>
                                    </div>
                                    <Input
                                        v-if="!(set.use_bodyweight !== undefined ? set.use_bodyweight : (props.sessionExercise.use_bodyweight ?? false))"
                                        type="number"
                                        step="0.5"
                                        :model-value="set.weight"
                                        @update:model-value="(value: string | number) => {
                                            const numValue = value === '' || value === null || value === undefined ? null : parseFloat(value as string);
                                            updateSet(setIndex, 'weight', (numValue !== null && !isNaN(numValue)) ? numValue : null);
                                        }"
                                        @mousedown.stop
                                        @dragstart.stop
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

                                <!-- Repos - Mobile: ligne 2, colonne 3 (sous Rep) -->
                                <div class="row-start-2 col-start-3 sm:row-start-1 sm:col-start-5">
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
                                
                                <!-- Bouton supprimer la série - tout à droite sur desktop (toutes les lignes) -->
                                <div v-if="setIndex > 0" class="hidden sm:flex items-end">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="removeSet(setIndex)"
                                        class="h-8 w-8 p-0 rounded-full bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 flex-shrink-0 flex items-center justify-center"
                                        title="Supprimer cette ligne"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
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

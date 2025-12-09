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
    ChevronUp,
    ChevronDown,
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
    moveUp: [];
    moveDown: [];
    convertToSet: [];
}>();

const showRemoveDialog = ref(false);
const isEditingName = ref(false);
const exercise = computed(() => props.sessionExercise.exercise);

// Initialiser les séries si elles n'existent pas
const sets = computed(() => {
    // TOUJOURS retourner les sets réels de l'exercice, même s'ils sont vides
    // Ne pas créer de tableau par défaut ici, car cela empêche la sauvegarde
    if (props.sessionExercise.sets && Array.isArray(props.sessionExercise.sets) && props.sessionExercise.sets.length > 0) {
        return props.sessionExercise.sets;
    }
    // Si pas de séries, retourner un tableau vide
    // Les sets seront initialisés lors de la première modification
    return [];
});

// S'assurer que les sets sont initialisés lors du montage si nécessaire
onMounted(() => {
    // Si les sets n'existent pas dans l'exercice, les initialiser avec un set par défaut
    if (!props.sessionExercise.sets || !Array.isArray(props.sessionExercise.sets) || props.sessionExercise.sets.length === 0) {
        // Initialiser les sets dans l'exercice avec un set par défaut
        const defaultSet = [{
            set_number: 1,
            repetitions: props.sessionExercise.repetitions ?? null,
            weight: props.sessionExercise.weight ?? null,
            rest_time: props.sessionExercise.rest_time ?? null,
            duration: props.sessionExercise.duration ?? null,
            order: 0
        }];
        emit('update', { sets: defaultSet });
    }
});

const updateField = (field: keyof SessionExercise, value: any) => {
    // Debug
    console.log('updateField:', { field, value, sessionExercise: props.sessionExercise });
    emit('update', { [field]: value });
};

const updateSet = (setIndex: number, field: keyof ExerciseSet, value: any) => {
    // TOUJOURS utiliser les sets réels de l'exercice, même s'ils sont vides
    // Si les sets n'existent pas dans props, on doit les créer et les initialiser
    let currentSets: ExerciseSet[];
    
    // Vérifier si les sets existent réellement dans props.sessionExercise.sets
    // IMPORTANT: Vérifier aussi si les sets sont un tableau vide (length === 0)
    const hasSets = props.sessionExercise.sets && Array.isArray(props.sessionExercise.sets) && props.sessionExercise.sets.length > 0;
    
    if (hasSets) {
        // Les sets existent, les copier en profondeur
        currentSets = props.sessionExercise.sets.map(set => ({ 
            set_number: set.set_number ?? 1,
            repetitions: set.repetitions ?? null,
            weight: set.weight ?? null,
            rest_time: set.rest_time ?? null,
            duration: set.duration ?? null,
            order: set.order ?? 0
        }));
    } else {
        // Les sets n'existent pas ou sont vides, créer un set par défaut avec les valeurs existantes de l'exercice
        currentSets = [{
            set_number: 1,
            repetitions: props.sessionExercise.repetitions ?? null,
            weight: props.sessionExercise.weight ?? null,
            rest_time: props.sessionExercise.rest_time ?? null,
            duration: props.sessionExercise.duration ?? null,
            order: 0
        }];
    }
    
    // S'assurer que le set à l'index existe
    if (!currentSets[setIndex]) {
        // Créer le set s'il n'existe pas
        while (currentSets.length <= setIndex) {
            currentSets.push({
                set_number: currentSets.length + 1,
                repetitions: null,
                weight: null,
                rest_time: null,
                duration: null,
                order: currentSets.length
            });
        }
    }
    
    // Mettre à jour le set
    currentSets[setIndex] = { ...currentSets[setIndex], [field]: value };
    
    // Debug
    console.log('updateSet:', { 
        setIndex, 
        field, 
        value, 
        currentSets,
        propsSets: props.sessionExercise.sets,
        propsSetsExists: !!props.sessionExercise.sets,
        propsSetsLength: props.sessionExercise.sets?.length || 0,
        hasSets: hasSets,
        exerciseId: props.sessionExercise.exercise_id,
        willEmit: true
    });
    
    // TOUJOURS émettre les sets, même si c'était un set par défaut
    // C'est crucial pour que les sets soient sauvegardés dans sessionExercises.value
    emit('update', { sets: currentSets });
};

const addSet = () => {
    // Utiliser les sets réels de l'exercice ou créer un tableau par défaut
    let currentSets: ExerciseSet[];
    if (props.sessionExercise.sets && props.sessionExercise.sets.length > 0) {
        currentSets = [...props.sessionExercise.sets];
    } else {
        // Créer un set par défaut avec les valeurs existantes de l'exercice
        currentSets = [{
            set_number: 1,
            repetitions: props.sessionExercise.repetitions ?? null,
            weight: props.sessionExercise.weight ?? null,
            rest_time: props.sessionExercise.rest_time ?? null,
            duration: props.sessionExercise.duration ?? null,
            order: 0
        }];
    }
    
    const newSet: ExerciseSet = {
        set_number: currentSets.length + 1,
        repetitions: null,
        weight: null,
        rest_time: null,
        duration: null,
        order: currentSets.length
    };
    const updatedSets = [...currentSets, newSet];
    emit('update', { sets: updatedSets });
};

const removeSet = (setIndex: number) => {
    // Utiliser les sets réels de l'exercice
    if (!props.sessionExercise.sets || props.sessionExercise.sets.length === 0) {
        return; // Pas de sets à supprimer
    }
    
    const currentSets = [...props.sessionExercise.sets];
    if (currentSets.length <= 1) return; // Garder au moins une série
    const updatedSets = currentSets.filter((_, index) => index !== setIndex);
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
        <Card class="transform transition-all duration-200 hover:shadow-lg hover:bg-neutral-50/50 dark:hover:bg-neutral-800/50">
            <!-- Numéro d'exercice en haut à gauche -->
            <div class="absolute -top-2 -left-2 z-10 flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold shadow-md">
                {{ (displayIndex !== undefined ? displayIndex : index) + 1 }}
            </div>
            <CardContent class="p-1.5">
                <!-- Contenu principal : Image, nom, commentaires avec icônes de drag and drop alignées -->
                <div class="flex items-end gap-2 mb-2">
                    <!-- Poignée de drag et boutons de déplacement -->
                    <div 
                        class="flex flex-col items-center gap-0.5 flex-shrink-0 pt-0.5"
                    >
                        <button
                            type="button"
                            @click.stop.prevent="emit('moveUp')"
                            class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded transition-colors text-neutral-400 hover:text-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
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
                            class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded transition-colors text-neutral-400 hover:text-blue-600 disabled:opacity-80 disabled:cursor-not-allowed"
                            :disabled="totalCount !== undefined && index === totalCount - 1"
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
                    <div class="flex items-center justify-end mb-1.5">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="addSet"
                            class="h-7 text-xs"
                        >
                            <Plus class="h-3 w-3 mr-1" />
                            Ajouter une ligne
                        </Button>
                    </div>

                    <div class="space-y-1.5">
                        <!-- Afficher les sets réels de l'exercice, ou un set par défaut si vide -->
                        <div
                            v-for="(set, setIndex) in (props.sessionExercise.sets && props.sessionExercise.sets.length > 0 ? props.sessionExercise.sets : [{ set_number: 1, repetitions: null, weight: null, rest_time: null, duration: null, order: 0 }])"
                            :key="setIndex"
                            class="relative p-2 bg-neutral-50 dark:bg-neutral-800/50 rounded-md"
                        >
                            <!-- Bouton supprimer la série - fixe en haut à gauche sur mobile, dans la grille sur desktop -->
                            <Button
                                v-if="sets.length > 1"
                                variant="ghost"
                                size="sm"
                                @click="removeSet(setIndex)"
                                class="absolute -top-2 -left-1 sm:hidden h-6 w-6 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 z-10"
                                title="Supprimer cette ligne"
                            >
                                <X class="h-3.5 w-3.5" />
                            </Button>
                            
                            <!-- Champs organisés en grille responsive : 2 colonnes sur mobile, 5 sur desktop (avec bouton supprimer) -->
                            <div class="grid grid-cols-2 sm:grid-cols-[auto_1fr_1fr_1fr_1fr] gap-2 items-end">
                                <!-- Bouton supprimer la série - tout à gauche sur desktop uniquement -->
                                <div class="hidden sm:flex items-end">
                                    <Button
                                        v-if="sets.length > 1"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeSet(setIndex)"
                                        class="h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 flex-shrink-0"
                                        title="Supprimer cette ligne"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                                <!-- Numéro de série (éditable pour chaque ligne) -->
                                <div>
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

                                <!-- Répétitions ou Durée (selon le switch) -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <Label class="text-xs text-neutral-500">
                                            {{ sessionExercise.use_duration ? 'Durée (seconde)' : 'Rep' }}
                                        </Label>
                                        <button
                                            type="button"
                                            @click.stop="updateField('use_duration', !sessionExercise.use_duration)"
                                            class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors"
                                            title="Basculer entre Rep et Durée"
                                        >
                                            <RotateCw class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                        </button>
                                    </div>
                                    <Input
                                        v-if="!sessionExercise.use_duration"
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

                                <!-- Charge (poids) ou Poids de corps (selon le switch) -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <Label class="text-xs text-neutral-500">
                                            {{ sessionExercise.use_bodyweight ? 'Poids de corps' : 'Charge (kg)' }}
                                        </Label>
                                        <button
                                            type="button"
                                            @click.stop="updateField('use_bodyweight', !sessionExercise.use_bodyweight)"
                                            class="p-0.5 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded transition-colors"
                                            title="Basculer entre Charge et Poids de corps"
                                        >
                                            <RotateCw class="h-3.5 w-3.5 text-neutral-600 dark:text-neutral-400" />
                                        </button>
                                    </div>
                                    <Input
                                        v-if="!sessionExercise.use_bodyweight"
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
                                        :disabled="sessionExercise.use_bodyweight"
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

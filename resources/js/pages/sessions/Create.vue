<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Calendar, 
    FileText, 
    Save, 
    Trash2, 
    Search, 
    Filter,
    Grid3x3,
    List,
    Plus,
    X,
    GripVertical,
    Clock,
    RotateCcw,
    Pause,
    AlertTriangle
} from 'lucide-vue-next';
import type { CreateSessionProps, Exercise, SessionExercise, Customer, Category } from './types';
import SessionExerciseItem from './SessionExerciseItem.vue';
import ExerciseLibrary from './ExerciseLibrary.vue';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';

const props = defineProps<CreateSessionProps>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes Séances',
        href: '/sessions',
    },
    {
        title: 'Nouvelle Séance',
        href: '/sessions/create',
    },
];

// État du formulaire
const form = useForm({
    name: '',
    customer_id: null as number | null,
    person_name: '',
    session_date: new Date().toISOString().split('T')[0],
    notes: '',
    exercises: [] as SessionExercise[],
});

// État de l'interface
const searchTerm = ref(props.filters.search || '');
const localSearchTerm = ref(props.filters.search || ''); // Pour la recherche locale avec debounce
const selectedCategoryId = ref<number | null>(props.filters.category_id || null);
const viewMode = ref<'grid-2' | 'grid-4' | 'list'>('grid-4');
const isSaving = ref(false);

// Liste des exercices dans la séance (avec drag and drop)
const sessionExercises = ref<SessionExercise[]>([]);
const isDraggingOver = ref(false);
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

// Sauvegarder les exercices dans le localStorage pour les préserver en cas de rafraîchissement
const STORAGE_KEY = 'fitnessclic_session_exercises';

// Charger les exercices depuis le localStorage au montage
const loadExercisesFromStorage = () => {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed) && parsed.length > 0) {
                // Filtrer les exercices invalides
                const validExercises = parsed.filter((ex: SessionExercise) => 
                    ex && ex.exercise_id !== null && ex.exercise_id !== undefined
                );
                if (validExercises.length > 0) {
                    sessionExercises.value = validExercises;
                    form.exercises = validExercises;
                }
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement depuis le localStorage:', error);
    }
};

// Sauvegarder les exercices dans le localStorage
const saveExercisesToStorage = () => {
    try {
        if (sessionExercises.value.length > 0) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(sessionExercises.value));
        } else {
            localStorage.removeItem(STORAGE_KEY);
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde dans le localStorage:', error);
    }
};

// Charger au montage
loadExercisesFromStorage();

// Ajouter un exercice à la séance
const addExerciseToSession = (exercise: Exercise) => {
    if (!exercise || !exercise.id) {
        console.error('Tentative d\'ajout d\'un exercice invalide:', exercise);
        return;
    }
    const sessionExercise: SessionExercise = {
        exercise_id: exercise.id,
        exercise: exercise,
        sets: 1,
        repetitions: '',
        rest_time: '',
        duration: '',
        description: '',
        order: sessionExercises.value.length,
    };
    sessionExercises.value.push(sessionExercise);
    // Forcer la réactivité en créant une nouvelle référence
    sessionExercises.value = [...sessionExercises.value];
    form.exercises = [...sessionExercises.value];
    saveExercisesToStorage();
    console.log('Exercice ajouté, total:', sessionExercises.value.length);
};

// Supprimer un exercice de la séance
const removeExerciseFromSession = (index: number) => {
    sessionExercises.value.splice(index, 1);
    // Réorganiser les ordres
    sessionExercises.value.forEach((ex, idx) => {
        ex.order = idx;
    });
    form.exercises = sessionExercises.value;
};

// Mettre à jour un exercice dans la séance
const updateSessionExercise = (index: number, updates: Partial<SessionExercise>) => {
    sessionExercises.value[index] = {
        ...sessionExercises.value[index],
        ...updates,
    };
    form.exercises = sessionExercises.value;
};

// Réorganiser les exercices (drag and drop)
const reorderExercises = (fromIndex: number, toIndex: number) => {
    if (fromIndex === toIndex) return;
    if (fromIndex < 0 || fromIndex >= sessionExercises.value.length) return;
    if (toIndex < 0 || toIndex >= sessionExercises.value.length) return;
    
    const [moved] = sessionExercises.value.splice(fromIndex, 1);
    sessionExercises.value.splice(toIndex, 0, moved);
    // Réorganiser les ordres
    sessionExercises.value.forEach((ex, idx) => {
        ex.order = idx;
    });
    form.exercises = [...sessionExercises.value]; // Créer une nouvelle référence pour forcer la réactivité
    saveExercisesToStorage();
};

// Gestion du drag and drop HTML5
const handleDragStart = (event: DragEvent, index: number) => {
    console.log('Drag start pour index:', index);
    draggedIndex.value = index;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', index.toString());
    }
};

const handleDragOver = (event: DragEvent, index: number) => {
    event.preventDefault();
    if (event.dataTransfer) {
        // Vérifier si c'est un drag depuis la bibliothèque
        const types = event.dataTransfer.types;
        if (types.includes('application/json')) {
            event.dataTransfer.dropEffect = 'copy';
        } else {
            event.dataTransfer.dropEffect = 'move';
        }
    }
    if (dragOverIndex.value !== index) {
        dragOverIndex.value = index;
    }
};

const handleDragLeave = () => {
    // Ne pas réinitialiser immédiatement pour éviter les tremblements
};

const handleDrop = (event: DragEvent, dropIndex: number) => {
    event.preventDefault();
    event.stopPropagation();
    
    console.log('Drop sur index:', dropIndex);
    
    if (!event.dataTransfer) {
        draggedIndex.value = null;
        dragOverIndex.value = null;
        return;
    }
    
    // Vérifier si c'est un drop depuis la bibliothèque (application/json)
    const exerciseData = event.dataTransfer.getData('application/json');
    if (exerciseData) {
        console.log('Drop depuis la bibliothèque');
        try {
            const exercise: Exercise = JSON.parse(exerciseData);
            addExerciseToSession(exercise);
        } catch (error) {
            console.error('Erreur lors du drop depuis la bibliothèque:', error);
        }
        draggedIndex.value = null;
        dragOverIndex.value = null;
        return;
    }
    
    // Sinon, c'est un réordonnancement
    let sourceIndex = draggedIndex.value;
    const data = event.dataTransfer.getData('text/plain');
    console.log('Données drag:', data, 'sourceIndex:', sourceIndex);
    if (data) {
        const parsedIndex = parseInt(data, 10);
        if (!isNaN(parsedIndex)) {
            sourceIndex = parsedIndex;
        }
    }
    
    if (sourceIndex !== null && sourceIndex !== undefined && !isNaN(sourceIndex) && sourceIndex !== dropIndex) {
        console.log('Réordonnancement de', sourceIndex, 'vers', dropIndex);
        reorderExercises(sourceIndex, dropIndex);
    }
    
    draggedIndex.value = null;
    dragOverIndex.value = null;
};

const handleDragEnd = () => {
    draggedIndex.value = null;
    dragOverIndex.value = null;
};

// Gestion du drop depuis la bibliothèque
const handleDropFromLibrary = (event: DragEvent) => {
    isDraggingOver.value = false;
    if (!event.dataTransfer) return;
    
    try {
        const exerciseData = event.dataTransfer.getData('application/json');
        if (exerciseData) {
            const exercise: Exercise = JSON.parse(exerciseData);
            addExerciseToSession(exercise);
        } else {
            // Fallback: essayer avec l'ID
            const exerciseId = event.dataTransfer.getData('text/plain');
            if (exerciseId) {
                const exercise = props.exercises.find(ex => ex.id === parseInt(exerciseId));
                if (exercise) {
                    addExerciseToSession(exercise);
                }
            }
        }
    } catch (error) {
        console.error('Erreur lors du drop:', error);
    }
};

// Gestion du drag over depuis la bibliothèque
watch(() => sessionExercises.value.length, () => {
    // Réinitialiser l'état de drag over quand la liste change
    isDraggingOver.value = false;
});

// Filtrer les exercices disponibles (recherche locale avec debounce)
const filteredExercises = computed(() => {
    let exercises = props.exercises;

    // Recherche locale (instantanée)
    if (localSearchTerm.value.trim()) {
        exercises = exercises.filter(ex => 
            ex.title.toLowerCase().includes(localSearchTerm.value.toLowerCase())
        );
    }

    // Filtre par catégorie
    if (selectedCategoryId.value) {
        exercises = exercises.filter(ex => 
            ex.categories?.some(cat => cat.id === selectedCategoryId.value)
        );
    }

    return exercises;
});

// Debounce pour la recherche (uniquement pour synchroniser avec l'URL si nécessaire)
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(localSearchTerm, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        searchTerm.value = newValue;
        // Ne pas recharger la page pour préserver les exercices
    }, 300);
});

// Appliquer les filtres (désactivé pour éviter de perdre les exercices)
// Le filtrage se fait maintenant entièrement côté client
const applyFilters = () => {
    // Désactivé pour éviter de perdre les exercices lors du rafraîchissement
    // router.get('/sessions/create', {
    //     search: searchTerm.value || null,
    //     category_id: selectedCategoryId.value || null,
    // }, {
    //     preserveState: true,
    //     preserveScroll: true,
    // });
};

// Sauvegarder la séance
const saveSession = () => {
    if (sessionExercises.value.length === 0) {
        alert('Veuillez ajouter au moins un exercice à la séance.');
        return;
    }

    isSaving.value = true;
    form.exercises = sessionExercises.value;
    
    form.post('/sessions', {
        onSuccess: () => {
            // Nettoyer le localStorage après sauvegarde réussie
            localStorage.removeItem(STORAGE_KEY);
            router.visit('/sessions');
        },
        onError: () => {
            isSaving.value = false;
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

// Effacer la séance
const clearSession = () => {
    if (confirm('Êtes-vous sûr de vouloir effacer cette séance ?')) {
        sessionExercises.value = [];
        form.reset();
        form.session_date = new Date().toISOString().split('T')[0];
        form.exercises = [];
        localStorage.removeItem(STORAGE_KEY);
    }
};

// Générer le PDF (sera implémenté après installation du package)
const generatePDF = () => {
    if (sessionExercises.value.length === 0) {
        alert('Veuillez ajouter au moins un exercice à la séance avant de générer le PDF.');
        return;
    }
    // TODO: Implémenter la génération PDF
    alert('La génération PDF sera disponible après l\'installation du package dompdf.');
};

// Nom du client sélectionné
const selectedCustomer = computed(() => {
    if (!form.customer_id) return null;
    return props.customers.find(c => c.id === form.customer_id) || null;
});

// Auto-remplir le champ nom/prénom quand un client est sélectionné
watch(() => form.customer_id, (customerId) => {
    if (customerId) {
        const customer = props.customers.find(c => c.id === customerId);
        if (customer) {
            form.person_name = customer.full_name;
        }
    } else {
        // Si aucun client n'est sélectionné, on peut vider le champ ou le laisser
        // form.person_name = '';
    }
});

// Détecter les exercices en double
const duplicateExercises = computed(() => {
    const exerciseIds = sessionExercises.value
        .filter(ex => ex && ex.exercise_id !== null && ex.exercise_id !== undefined)
        .map(ex => ex.exercise_id);
    const duplicates = exerciseIds.filter((id, index) => exerciseIds.indexOf(id) !== index);
    return [...new Set(duplicates)]; // Retourner les IDs uniques des exercices en double
});

const hasDuplicateExercises = computed(() => duplicateExercises.value.length > 0);

// Synchroniser les exercices avec le formulaire et le localStorage
watch(sessionExercises, () => {
    form.exercises = sessionExercises.value;
    saveExercisesToStorage();
}, { deep: true });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nouvelle Séance" />

        <div class="flex flex-col h-full">
            <!-- En-tête avec actions -->
            <div class="flex items-center justify-between border-b bg-white dark:bg-neutral-900 px-6 py-4">
                <div class="flex items-center gap-4">
                    <h1 class="text-2xl font-semibold">Nouvelle Séance</h1>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                        <Calendar class="h-4 w-4" />
                        <input
                            v-model="form.session_date"
                            type="date"
                            class="border-none bg-transparent text-sm focus:outline-none"
                        />
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="clearSession"
                    >
                        <Trash2 class="h-4 w-4 mr-2" />
                        Effacer
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="generatePDF"
                        :disabled="sessionExercises.length === 0"
                    >
                        <FileText class="h-4 w-4 mr-2" />
                        PDF
                    </Button>
                    <Button
                        size="sm"
                        @click="saveSession"
                        :disabled="isSaving || sessionExercises.length === 0"
                    >
                        <Save class="h-4 w-4 mr-2" />
                        {{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}
                    </Button>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="flex-1 flex overflow-hidden">
                <!-- Panneau gauche : Formulaire de séance -->
                <div class="w-1/2 border-r overflow-y-auto bg-neutral-50 dark:bg-neutral-950">
                    <div class="p-6 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Informations de la séance</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Sélection du client -->
                                <div class="space-y-2">
                                    <Label>Sélectionner un client (optionnel)</Label>
                                    <select
                                        :value="form.customer_id"
                                        @change="form.customer_id = $event.target.value ? parseInt($event.target.value) : null"
                                        class="flex h-9 w-full rounded-md border border-input bg-white dark:bg-neutral-900 px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm text-neutral-900 dark:text-neutral-100"
                                    >
                                        <option :value="null">Aucun client</option>
                                        <option
                                            v-for="customer in customers"
                                            :key="customer.id"
                                            :value="customer.id"
                                        >
                                            {{ customer.full_name || `${customer.first_name} ${customer.last_name}` }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Nom et prénom de la personne -->
                                <div class="space-y-2">
                                    <Label>Nom et prénom de la personne</Label>
                                    <Input
                                        v-model="form.person_name"
                                        placeholder="Ex: Jean Dupont"
                                    />
                                </div>

                                <!-- Notes -->
                                <div class="space-y-2">
                                    <Label>Notes (optionnel)</Label>
                                    <Textarea
                                        v-model="form.notes"
                                        placeholder="Ajouter des notes sur cette séance..."
                                        :rows="3"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Liste des exercices de la séance -->
                        <Card>
                            <CardHeader>
                                <div class="space-y-2">
                                    <CardTitle class="flex items-center justify-between">
                                        <span>Exercices de la séance</span>
                                        <span class="text-sm font-normal text-neutral-500">
                                            {{ sessionExercises.length }} exercice(s)
                                        </span>
                                    </CardTitle>
                                    <!-- Message d'avertissement pour les doublons -->
                                    <Alert
                                        v-if="hasDuplicateExercises"
                                        variant="destructive"
                                        class="py-2"
                                    >
                                        <AlertTriangle class="h-4 w-4" />
                                        <AlertDescription class="text-sm">
                                            Attention : {{ duplicateExercises.length }} exercice(s) {{ duplicateExercises.length > 1 ? 'sont' : 'est' }} présent{{ duplicateExercises.length > 1 ? 's' : '' }} plusieurs fois dans cette séance.
                                        </AlertDescription>
                                    </Alert>
                                </div>
                            </CardHeader>
                            <CardContent
                                @dragover.prevent="(e: DragEvent) => { 
                                    if (e.dataTransfer) {
                                        const types = e.dataTransfer.types;
                                        if (types.includes('application/json')) {
                                            e.dataTransfer.dropEffect = 'copy';
                                            isDraggingOver = true;
                                        } else {
                                            e.dataTransfer.dropEffect = 'move';
                                        }
                                    }
                                }"
                                @dragleave="(e: DragEvent) => { 
                                    // Ne pas réinitialiser si on entre dans un enfant
                                    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
                                    const x = e.clientX;
                                    const y = e.clientY;
                                    if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                                        isDraggingOver = false;
                                    }
                                }"
                                @drop.prevent="handleDropFromLibrary"
                                :class="{ 'border-2 border-dashed border-blue-400 bg-blue-50/50 dark:bg-blue-900/20': isDraggingOver }"
                                class="min-h-[200px] transition-colors"
                            >
                                <div v-if="sessionExercises.length === 0" class="text-center py-12 text-neutral-500">
                                    <p class="mb-2">Aucun exercice ajouté</p>
                                    <p class="text-sm">Glissez des exercices depuis la bibliothèque à droite</p>
                                </div>
                                <div v-else class="space-y-4">
                                    <SessionExerciseItem
                                        v-for="(sessionExercise, index) in sessionExercises"
                                        :key="`session-ex-${sessionExercise.exercise_id}-${index}`"
                                        :session-exercise="sessionExercise"
                                        :index="index"
                                        :draggable="true"
                                        :is-dragging="draggedIndex === index"
                                        :is-drag-over="dragOverIndex === index && draggedIndex !== index && draggedIndex !== null"
                                        @dragstart="handleDragStart($event, index)"
                                        @dragover="handleDragOver($event, index)"
                                        @dragleave="handleDragLeave"
                                        @drop="handleDrop($event, index)"
                                        @dragend="handleDragEnd"
                                        @update="(updates: Partial<SessionExercise>) => updateSessionExercise(index, updates)"
                                        @remove="() => removeExerciseFromSession(index)"
                                        @move-up="() => { if (index > 0) reorderExercises(index, index - 1); }"
                                        @move-down="() => { if (index < sessionExercises.length - 1) reorderExercises(index, index + 1); }"
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Panneau droit : Bibliothèque d'exercices -->
                <div class="w-1/2 overflow-y-auto bg-white dark:bg-neutral-900">
                    <ExerciseLibrary
                        :exercises="filteredExercises"
                        :categories="categories"
                        :search-term="localSearchTerm"
                        :selected-category-id="selectedCategoryId"
                        :view-mode="viewMode"
                        @search="(term: string) => { localSearchTerm = term; }"
                        @category-change="(id: number | null) => { selectedCategoryId = id; }"
                        @view-mode-change="(mode: 'grid-2' | 'grid-4' | 'list') => viewMode = mode"
                        @add-exercise="addExerciseToSession"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>


<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import InputError from '@/components/InputError.vue';
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { User, Users, CheckSquare, Square } from 'lucide-vue-next';
import { useNotifications } from '@/composables/useNotifications';

const props = defineProps<CreateSessionProps>();
const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);
const { success: notifySuccess, error: notifyError } = useNotifications();

// Écouter les messages flash et les convertir en notifications
// Utiliser un Set pour éviter les doublons
const shownFlashMessages = ref(new Set<string>());

watch(() => (page.props as any).flash, (flash) => {
    if (!flash) return;
    
    // Créer une clé unique pour chaque message
    const successKey = flash.success ? `success-${flash.success}` : null;
    const errorKey = flash.error ? `error-${flash.error}` : null;
    
    // Afficher la notification seulement si on ne l'a pas déjà affichée
    if (successKey && !shownFlashMessages.value.has(successKey)) {
        shownFlashMessages.value.add(successKey);
        notifySuccess(flash.success);
        // Nettoyer après 2 secondes pour permettre de réafficher le même message plus tard si nécessaire
        setTimeout(() => {
            shownFlashMessages.value.delete(successKey);
        }, 2000);
    }
    
    if (errorKey && !shownFlashMessages.value.has(errorKey)) {
        shownFlashMessages.value.add(errorKey);
        notifyError(flash.error);
        setTimeout(() => {
            shownFlashMessages.value.delete(errorKey);
        }, 2000);
    }
}, { immediate: true });

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
    customer_ids: [] as number[],
    session_date: new Date().toISOString().split('T')[0],
    notes: '',
    exercises: [] as SessionExercise[],
});

// État pour la modal de sélection de clients
const showCustomerModal = ref(false);
const tempSelectedCustomerIds = ref<number[]>([]);
const customerSearchTerm = ref('');

// Clients sélectionnés (computed depuis form.customer_ids)
const selectedCustomers = computed(() => {
    if (!form.customer_ids || form.customer_ids.length === 0) {
        return [];
    }
    return props.customers.filter(customer => 
        form.customer_ids.includes(customer.id)
    );
});

// Alias pour customers dans le template
const customers = computed(() => props.customers);

// Clients filtrés pour la recherche dans la modal
const filteredCustomersForModal = computed(() => {
    if (!customerSearchTerm.value.trim()) {
        return customers.value;
    }
    const search = customerSearchTerm.value.toLowerCase();
    return customers.value.filter(customer => {
        const fullName = (customer.full_name || `${customer.first_name} ${customer.last_name}`).toLowerCase();
        const email = (customer.email || '').toLowerCase();
        return fullName.includes(search) || email.includes(search);
    });
});

// Sélectionner/désélectionner tous les clients filtrés
const toggleAllFilteredCustomers = () => {
    const allFilteredSelected = filteredCustomersForModal.value.every(customer => 
        tempSelectedCustomerIds.value.includes(customer.id)
    );
    
    if (allFilteredSelected) {
        // Désélectionner tous les clients filtrés
        filteredCustomersForModal.value.forEach(customer => {
            const index = tempSelectedCustomerIds.value.indexOf(customer.id);
            if (index > -1) {
                tempSelectedCustomerIds.value.splice(index, 1);
            }
        });
    } else {
        // Sélectionner tous les clients filtrés
        filteredCustomersForModal.value.forEach(customer => {
            if (!tempSelectedCustomerIds.value.includes(customer.id)) {
                tempSelectedCustomerIds.value.push(customer.id);
            }
        });
    }
};

// Vérifier si tous les clients filtrés sont sélectionnés
const allFilteredSelected = computed(() => {
    if (filteredCustomersForModal.value.length === 0) return false;
    return filteredCustomersForModal.value.every(customer => 
        tempSelectedCustomerIds.value.includes(customer.id)
    );
});

// Ouvrir la modal avec les clients actuellement sélectionnés
const openCustomerModal = () => {
    tempSelectedCustomerIds.value = [...form.customer_ids];
    customerSearchTerm.value = '';
    showCustomerModal.value = true;
};

// Confirmer la sélection des clients
const confirmCustomerSelection = () => {
    form.customer_ids = [...tempSelectedCustomerIds.value];
    showCustomerModal.value = false;
};

// Toggle la sélection d'un client dans la modal
const toggleCustomer = (customerId: number, checked: boolean) => {
    if (checked) {
        // Ajouter le client s'il n'est pas déjà dans la liste
        if (!tempSelectedCustomerIds.value.includes(customerId)) {
            tempSelectedCustomerIds.value.push(customerId);
        }
    } else {
        // Retirer le client
        const index = tempSelectedCustomerIds.value.indexOf(customerId);
        if (index > -1) {
            tempSelectedCustomerIds.value.splice(index, 1);
        }
    }
};

// Retirer un client de la sélection
const removeCustomer = (customerId: number) => {
    form.customer_ids = form.customer_ids.filter(id => id !== customerId);
};

// État de l'interface
const searchTerm = ref(props.filters.search || '');
const localSearchTerm = ref(props.filters.search || ''); // Pour la recherche locale avec debounce
const selectedCategoryId = ref<number | null>(props.filters.category_id || null);
const viewMode = ref<'grid-2' | 'grid-4' | 'grid-6' | 'list'>('grid-4');
const showOnlyMine = ref(false);
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
        sets: [{
            set_number: 1,
            repetitions: null,
            weight: null,
            rest_time: null,
            duration: null,
            order: 0
        }],
        repetitions: null,
        weight: null,
        rest_time: null,
        duration: null,
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

    // Filtre par utilisateur (tous ou seulement les miens)
    if (showOnlyMine.value && currentUserId.value) {
        exercises = exercises.filter(ex => ex.user_id === currentUserId.value);
    }

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

// Validation du formulaire
const isFormValid = computed(() => {
    const hasName = form.name.trim().length > 0;
    const hasExercises = sessionExercises.value.length > 0;
    return hasName && hasExercises;
});

// Sauvegarder la séance
const saveSession = () => {
    // Validation côté client
    if (!form.name.trim()) {
        notifyError('Le nom de la séance est obligatoire.', 'Validation');
        return;
    }

    if (sessionExercises.value.length === 0) {
        notifyError('Veuillez ajouter au moins un exercice à la séance.', 'Validation');
        return;
    }

    isSaving.value = true;
    // S'assurer que customer_ids est bien un tableau
    if (!Array.isArray(form.customer_ids)) {
        form.customer_ids = [];
    }
    
    // Formater les exercices pour l'envoi au backend
    form.exercises = sessionExercises.value.map(ex => ({
        exercise_id: ex.exercise_id,
        sets: ex.sets && ex.sets.length > 0 ? ex.sets.map((set, idx) => ({
            set_number: set.set_number || idx + 1,
            repetitions: set.repetitions ?? null,
            weight: set.weight ?? null,
            rest_time: set.rest_time ?? null,
            duration: set.duration ?? null,
            order: set.order ?? idx
        })) : undefined,
        repetitions: ex.repetitions ?? null,
        weight: ex.weight ?? null,
        rest_time: ex.rest_time ?? null,
        duration: ex.duration ?? null,
        description: ex.description ?? null,
        order: ex.order
    }));
    
    form.post('/sessions', {
        preserveScroll: false,
        onSuccess: () => {
            // Nettoyer le localStorage après sauvegarde réussie
            localStorage.removeItem(STORAGE_KEY);
            // Réinitialiser le formulaire et les exercices
            form.reset();
            sessionExercises.value = [];
            // La notification sera affichée automatiquement via le message flash du backend
        },
        onError: (errors) => {
            isSaving.value = false;
            // Afficher les erreurs de validation
            if (errors.name) {
                notifyError(errors.name);
            } else if (errors.exercises) {
                notifyError(errors.exercises);
            } else {
                notifyError('Une erreur est survenue lors de la création de la séance.');
            }
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
                        :disabled="isSaving || !isFormValid"
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
                                <!-- Nom de la séance -->
                                <div class="space-y-2">
                                    <Label for="session-name">Nom de la séance <span class="text-red-500">*</span></Label>
                                    <Input
                                        id="session-name"
                                        v-model="form.name"
                                        placeholder="Ex: Séance jambes, Entraînement haut du corps..."
                                        class="w-full"
                                        :class="{ 'border-red-500 focus-visible:ring-red-500': form.errors.name }"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- Sélection des clients -->
                                <div class="space-y-2">
                                    <Label>Clients (optionnel)</Label>
                                    <div class="space-y-2">
                                        <!-- Bouton pour ouvrir la modal -->
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="w-full justify-start"
                                            @click="openCustomerModal"
                                        >
                                            <Users class="h-4 w-4 mr-2" />
                                            {{ selectedCustomers.length > 0 ? `${selectedCustomers.length} client(s) sélectionné(s)` : 'Sélectionner des clients' }}
                                        </Button>
                                        
                                        <!-- Liste des clients sélectionnés -->
                                        <div v-if="selectedCustomers.length > 0" class="flex flex-wrap gap-2 mt-2">
                                            <div
                                                v-for="customer in selectedCustomers"
                                                :key="customer.id"
                                                class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-full text-sm"
                                            >
                                                <User class="h-3.5 w-3.5" />
                                                <span>{{ customer.full_name || `${customer.first_name} ${customer.last_name}` }}</span>
                                                <button
                                                    type="button"
                                                    @click="removeCustomer(customer.id)"
                                                    class="ml-1 hover:text-blue-900 dark:hover:text-blue-100"
                                                >
                                                    <X class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
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
                        :show-only-mine="showOnlyMine"
                        :current-user-id="currentUserId"
                        @search="(term: string) => { localSearchTerm = term; }"
                        @category-change="(id: number | null) => { selectedCategoryId = id; }"
                        @view-mode-change="(mode: 'grid-2' | 'grid-4' | 'grid-6' | 'list') => viewMode = mode"
                        @filter-change="(showOnly: boolean) => { showOnlyMine = showOnly; }"
                        @add-exercise="addExerciseToSession"
                    />
                </div>
            </div>
        </div>

        <!-- Modal de sélection des clients -->
        <Dialog v-model:open="showCustomerModal">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Sélectionner les clients</DialogTitle>
                    <DialogDescription>
                        Choisissez un ou plusieurs clients pour cette séance.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="space-y-4 py-4">
                    <!-- Barre de recherche -->
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-neutral-400" />
                        <Input
                            v-model="customerSearchTerm"
                            placeholder="Rechercher un client par nom ou email..."
                            class="pl-9"
                        />
                    </div>

                    <!-- Actions rapides -->
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">
                            <span v-if="customerSearchTerm.trim()">
                                {{ filteredCustomersForModal.length }} résultat(s) sur {{ customers.length }} client(s)
                            </span>
                            <span v-else>
                                {{ customers.length }} client(s) au total
                            </span>
                        </div>
                        <Button
                            v-if="filteredCustomersForModal.length > 0"
                            variant="ghost"
                            size="sm"
                            @click="toggleAllFilteredCustomers"
                            class="text-xs"
                        >
                            <CheckSquare v-if="allFilteredSelected" class="h-3.5 w-3.5 mr-1" />
                            <Square v-else class="h-3.5 w-3.5 mr-1" />
                            {{ allFilteredSelected ? 'Tout désélectionner' : 'Tout sélectionner' }}
                        </Button>
                    </div>

                    <!-- Liste des clients avec checkboxes -->
                    <div class="space-y-1 max-h-96 overflow-y-auto rounded-md border border-input bg-white dark:bg-neutral-900 p-2">
                        <label
                            v-for="customer in filteredCustomersForModal"
                            :key="customer.id"
                            class="flex items-center gap-3 cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-800/50 rounded px-3 py-2 transition"
                        >
                            <Checkbox
                                :model-value="tempSelectedCustomerIds.includes(customer.id)"
                                @update:model-value="(checked: boolean) => toggleCustomer(customer.id, checked)"
                            />
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-sm truncate">
                                    {{ customer.full_name || `${customer.first_name} ${customer.last_name}` }}
                                </div>
                                <div v-if="customer.email" class="text-xs text-neutral-500 truncate">
                                    {{ customer.email }}
                                </div>
                            </div>
                        </label>
                        <p v-if="filteredCustomersForModal.length === 0" class="text-xs text-neutral-500 dark:text-neutral-400 text-center py-4">
                            <span v-if="customerSearchTerm.trim()">
                                Aucun client trouvé pour "{{ customerSearchTerm }}"
                            </span>
                            <span v-else>
                                Aucun client disponible
                            </span>
                        </p>
                    </div>

                    <!-- Compteur de sélection -->
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-neutral-600 dark:text-neutral-400">
                            <span v-if="tempSelectedCustomerIds.length > 0" class="font-medium text-blue-600 dark:text-blue-400">
                                {{ tempSelectedCustomerIds.length }} client(s) sélectionné(s)
                            </span>
                            <span v-else class="text-neutral-500">
                                Aucun client sélectionné
                            </span>
                        </span>
                        <Button
                            v-if="tempSelectedCustomerIds.length > 0"
                            variant="ghost"
                            size="sm"
                            @click="tempSelectedCustomerIds = []"
                            class="text-xs text-red-600 hover:text-red-700"
                        >
                            <X class="h-3.5 w-3.5 mr-1" />
                            Tout effacer
                        </Button>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showCustomerModal = false"
                    >
                        Annuler
                    </Button>
                    <Button
                        @click="confirmCustomerSelection"
                    >
                        Confirmer ({{ tempSelectedCustomerIds.length }})
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>


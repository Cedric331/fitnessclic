<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, nextTick } from 'vue';
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
    AlertTriangle,
    Library,
    Printer,
    ArrowLeft
} from 'lucide-vue-next';
import type { EditSessionProps, Exercise, SessionExercise, Customer, Category, ExerciseSet } from './types';
import SessionExerciseItem from './SessionExerciseItem.vue';
import SessionBlockSet from './SessionBlockSet.vue';
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
import {
    Sheet,
    SheetContent,
} from '@/components/ui/sheet';
import { Checkbox } from '@/components/ui/checkbox';
import { User, Users, CheckSquare, Square } from 'lucide-vue-next';
import { useNotifications } from '@/composables/useNotifications';
import { VueDraggable } from 'vue-draggable-plus';

const props = defineProps<EditSessionProps>();
const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);
const { success: notifySuccess, error: notifyError } = useNotifications();

// Écouter les messages flash et les convertir en notifications
const shownFlashMessages = ref(new Set<string>());

watch(() => (page.props as any).flash, (flash) => {
    if (!flash) return;
    
    const successKey = flash.success ? `success-${flash.success}` : null;
    const errorKey = flash.error ? `error-${flash.error}` : null;
    
    if (successKey && !shownFlashMessages.value.has(successKey)) {
        shownFlashMessages.value.add(successKey);
        // Ajouter un petit délai pour s'assurer que la page est bien chargée avant d'afficher la notification
        nextTick(() => {
            setTimeout(() => {
                notifySuccess(flash.success);
            }, 100);
        });
        setTimeout(() => {
            shownFlashMessages.value.delete(successKey);
        }, 4500); // Nettoyer après la durée d'affichage + marge
    }
    
    if (errorKey && !shownFlashMessages.value.has(errorKey)) {
        shownFlashMessages.value.add(errorKey);
        nextTick(() => {
            setTimeout(() => {
                notifyError(flash.error);
            }, 100);
        });
        setTimeout(() => {
            shownFlashMessages.value.delete(errorKey);
        }, 6500); // Nettoyer après la durée d'affichage + marge
    }
}, { immediate: true });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes Séances',
        href: '/sessions',
    },
    {
        title: props.session.name || 'Modifier la séance',
        href: `/sessions/${props.session.id}/edit`,
    },
];

// État du formulaire - initialiser avec les données de la session
const form = useForm({
    name: props.session?.name || '',
    customer_ids: (props.session?.customers?.map((c: Customer) => c.id) || []) as number[],
    session_date: props.session?.session_date ? new Date(props.session.session_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    notes: props.session?.notes || '',
    exercises: [] as SessionExercise[],
});

// Pré-remplir le formulaire avec les données de la session quand elles sont disponibles
watch(() => props.session, (session) => {
    if (session) {
        if (session.name !== undefined) {
            form.name = session.name || '';
        }
        if (session.customers) {
            form.customer_ids = session.customers.map((c: Customer) => c.id) || [];
        }
        if (session.session_date) {
            try {
                const date = new Date(session.session_date);
                if (!isNaN(date.getTime())) {
                    form.session_date = date.toISOString().split('T')[0];
                }
            } catch (e) {
                // Ignorer silencieusement les erreurs de format de date
            }
        }
        if (session.notes !== undefined) {
            form.notes = session.notes || '';
        }
    }
}, { immediate: true, deep: true });

// État pour la modal de sélection de clients
const showCustomerModal = ref(false);
const tempSelectedCustomerIds = ref<number[]>([]);
const customerSearchTerm = ref('');

// Clients sélectionnés
const selectedCustomers = computed(() => {
    if (!form.customer_ids || form.customer_ids.length === 0) {
        return [];
    }
    return props.customers.filter(customer => 
        form.customer_ids.includes(customer.id)
    );
});

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
        filteredCustomersForModal.value.forEach(customer => {
            const index = tempSelectedCustomerIds.value.indexOf(customer.id);
            if (index > -1) {
                tempSelectedCustomerIds.value.splice(index, 1);
            }
        });
    } else {
        filteredCustomersForModal.value.forEach(customer => {
            if (!tempSelectedCustomerIds.value.includes(customer.id)) {
                tempSelectedCustomerIds.value.push(customer.id);
            }
        });
    }
};

const allFilteredSelected = computed(() => {
    if (filteredCustomersForModal.value.length === 0) return false;
    return filteredCustomersForModal.value.every(customer => 
        tempSelectedCustomerIds.value.includes(customer.id)
    );
});

const openCustomerModal = () => {
    tempSelectedCustomerIds.value = [...form.customer_ids];
    customerSearchTerm.value = '';
    showCustomerModal.value = true;
};

const confirmCustomerSelection = () => {
    form.customer_ids = [...tempSelectedCustomerIds.value];
    showCustomerModal.value = false;
};

const toggleCustomer = (customerId: number, checked: boolean) => {
    if (checked) {
        if (!tempSelectedCustomerIds.value.includes(customerId)) {
            tempSelectedCustomerIds.value.push(customerId);
        }
    } else {
        const index = tempSelectedCustomerIds.value.indexOf(customerId);
        if (index > -1) {
            tempSelectedCustomerIds.value.splice(index, 1);
        }
    }
};

const removeCustomer = (customerId: number) => {
    form.customer_ids = form.customer_ids.filter(id => id !== customerId);
};

// État de l'interface
const searchTerm = ref(props.filters.search || '');
const localSearchTerm = ref(props.filters.search || '');
const selectedCategoryId = ref<number | null>(props.filters.category_id || null);
const viewMode = ref<'grid-2' | 'grid-4' | 'grid-6' | 'list'>('grid-4');
const showOnlyMine = ref(false);
const isSaving = ref(false);
const isLibraryOpen = ref(false); // Pour le drawer mobile

// Liste des exercices dans la séance
const sessionExercises = ref<SessionExercise[]>([]);
const isDraggingOver = ref(false);
// Compteur pour générer des IDs uniques pour les nouveaux exercices
let sessionExerciseIdCounter = 0;
// Compteur pour générer des IDs de blocs uniques
let nextBlockId = 1;
// État du mode Super Set (pour créer de nouveaux blocs en mode set)
const isSetMode = ref(false);

// Charger les exercices de la session existante
const loadSessionExercises = () => {
    // Vérifier si on a sessionExercises ou exercises (pour compatibilité)
    const exercisesData = props.session?.sessionExercises || props.session?.exercises || [];
    
    if (exercisesData.length > 0) {
        // Convertir les sessionExercises/exercises en SessionExercise pour le formulaire
        sessionExercises.value = exercisesData.map((se: any, index: number) => {
            // Pour les exercices via pivot (ancien système)
            if (se.pivot) {
                const exercise = props.exercises.find(e => e.id === se.id);
                if (!exercise) return null;
                
                return {
                    id: se.id || --sessionExerciseIdCounter, // Utiliser l'ID existant ou générer un nouveau
                    exercise_id: se.id,
                    exercise: exercise,
                    sets: [{
                        set_number: 1,
                        repetitions: se.pivot.repetitions ? parseInt(se.pivot.repetitions) : null,
                        weight: null,
                        rest_time: se.pivot.rest_time ?? null,
                        duration: se.pivot.duration ?? null,
                        order: 0
                    }],
                    repetitions: se.pivot.repetitions ? parseInt(se.pivot.repetitions) : null,
                    weight: null,
                    rest_time: se.pivot.rest_time ?? null,
                    duration: se.pivot.duration ?? null,
                    description: se.pivot.additional_description ?? null,
                    order: se.pivot.order ?? index
                };
            }
            
            // Pour les sessionExercises (nouveau système)
            // Trouver l'exercice dans la liste des exercices disponibles ou utiliser celui de la relation
            let exercise = se.exercise;
            if (!exercise || !exercise.image_url) {
                exercise = props.exercises.find(e => e.id === se.exercise_id);
            }
            
            if (!exercise) {
                return null;
            }
            
            // Convertir les sets si disponibles
            let sets: ExerciseSet[] = [];
            if (se.sets && se.sets.length > 0) {
                sets = se.sets.map((set: any) => ({
                    set_number: set.set_number || 1,
                    repetitions: set.repetitions ?? null,
                    weight: set.weight ?? null,
                    rest_time: set.rest_time ?? null,
                    duration: set.duration ?? null,
                    order: set.order ?? 0
                }));
            } else if (se.repetitions || se.duration || se.rest_time || se.weight) {
                // Si pas de sets mais des valeurs, créer un set par défaut
                sets = [{
                    set_number: 1,
                    repetitions: se.repetitions ?? null,
                    weight: se.weight ?? null,
                    rest_time: se.rest_time ?? null,
                    duration: se.duration ?? null,
                    order: 0
                }];
            } else {
                // Créer un set vide par défaut
                sets = [{
                    set_number: 1,
                    repetitions: null,
                    weight: null,
                    rest_time: null,
                    duration: null,
                    order: 0
                }];
            }
            
            return {
                id: se.id || --sessionExerciseIdCounter, // Utiliser l'ID existant ou générer un nouveau
                exercise_id: se.exercise_id,
                exercise: exercise,
                sets: sets,
                repetitions: se.repetitions ?? null,
                sets_count: se.sets_count ?? null,
                weight: se.weight ?? null,
                rest_time: se.rest_time ?? null,
                duration: se.duration ?? null,
                description: se.additional_description ?? null,
                order: se.order ?? index,
                // Champs Super Set
                block_id: se.block_id ?? null,
                block_type: se.block_type ?? null,
                position_in_block: se.position_in_block ?? null,
            };
        }).filter((ex: SessionExercise | null) => ex !== null) as SessionExercise[];
        
        // Initialiser nextBlockId avec la valeur maximale des block_id existants
        const maxBlockId = sessionExercises.value
            .filter(ex => ex.block_id !== null)
            .reduce((max, ex) => Math.max(max, ex.block_id || 0), 0);
        if (maxBlockId > 0) {
            nextBlockId = maxBlockId + 1;
        }
        
        form.exercises = sessionExercises.value;
    }
};

// Charger au montage et quand les props changent
onMounted(() => {
    // Attendre que les props soient disponibles
    nextTick(() => {
        loadSessionExercises();
    });
});

watch(() => props.session?.sessionExercises, () => {
    loadSessionExercises();
}, { deep: true, immediate: true });

// Aussi charger quand la session change
watch(() => props.session, () => {
    nextTick(() => {
        loadSessionExercises();
    });
}, { deep: true, immediate: true });

// Ajouter un exercice à la séance (mode standard)
const addExerciseToSession = (exercise: Exercise) => {
    if (!exercise || !exercise.id) {
        return;
    }
    const sessionExercise: SessionExercise = {
        id: --sessionExerciseIdCounter, // ID unique négatif pour les nouveaux exercices
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
        sets_count: null,
        rest_time: null,
        duration: null,
        description: '',
        order: sessionExercises.value.length,
        block_id: null,
        block_type: null,
        position_in_block: null,
    };
    sessionExercises.value.push(sessionExercise);
    sessionExercises.value = [...sessionExercises.value];
    form.exercises = [...sessionExercises.value];
};

// Créer un nouveau bloc Super Set
const createNewSetBlock = (exercise: Exercise) => {
    if (!exercise || !exercise.id) {
        return;
    }
    const blockId = nextBlockId++;
    const sessionExercise: SessionExercise = {
        id: --sessionExerciseIdCounter,
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
        sets_count: null,
        block_id: blockId,
        block_type: 'set',
        position_in_block: 0,
        order: sessionExercises.value.length,
    };
    
    sessionExercises.value.push(sessionExercise);
    form.exercises = [...sessionExercises.value];
};

// Ajouter un exercice à un bloc Super Set existant
const addExerciseToSetBlock = (exercise: Exercise, blockId: number) => {
    if (!exercise || !exercise.id) {
        return;
    }
    
    // Trouver la position dans le bloc
    const blockExercises = sessionExercises.value.filter(
        ex => ex.block_id === blockId && ex.block_type === 'set'
    );
    const positionInBlock = blockExercises.length;
    const sessionExercise: SessionExercise = {
        id: --sessionExerciseIdCounter,
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
        sets_count: null,
        block_id: blockId,
        block_type: 'set',
        position_in_block: positionInBlock,
        order: sessionExercises.value.length,
    };
    
    sessionExercises.value.push(sessionExercise);
    form.exercises = [...sessionExercises.value];
};

// Grouper les exercices par blocs (standard et Super Set)
const groupExercisesIntoBlocks = () => {
    const blocksMap = new Map<number, SessionExercise[]>();
    const standardExercises: SessionExercise[] = [];
    
    sessionExercises.value.forEach(ex => {
        if (ex.block_id && ex.block_type === 'set') {
            if (!blocksMap.has(ex.block_id)) {
                blocksMap.set(ex.block_id, []);
            }
            blocksMap.get(ex.block_id)!.push(ex);
        } else {
            // Exercice standard (pas de block_id ou block_type !== 'set')
            standardExercises.push(ex);
        }
    });
    
    // Convertir les blocs Super Set en objets SessionBlock
    const setBlocks = Array.from(blocksMap.entries())
        .map(([blockId, exercises]) => ({
            id: blockId,
            type: 'set' as const,
            exercises: exercises.sort((a, b) => 
                (a.position_in_block || 0) - (b.position_in_block || 0)
            ),
            order: exercises[0]?.order || 0,
            block_description: exercises[0]?.description || null,
        }))
        .sort((a, b) => a.order - b.order);
    
    return {
        standard: standardExercises.sort((a, b) => a.order - b.order),
        set: setBlocks,
    };
};

// Items ordonnés (standard et Super Set mélangés) - computed pour réactivité
const orderedItems = computed(() => {
    const blocks = groupExercisesIntoBlocks();
    const items: Array<{ type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock, key: string, order: number, displayIndex: number }> = [];
    
    // Combiner et trier tous les items
    const allItems: Array<{ type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock, order: number }> = [];
    
    // Ajouter les exercices standard
    blocks.standard.forEach(ex => {
        allItems.push({
            type: 'standard',
            exercise: ex,
            order: ex.order
        });
    });
    
    // Ajouter les blocs Super Set
    blocks.set.forEach(block => {
        allItems.push({
            type: 'set',
            block: block,
            order: block.order
        });
    });
    
    // Trier par ordre
    allItems.sort((a, b) => a.order - b.order);
    
    // Ajouter l'index d'affichage (compteur)
    allItems.forEach((item, index) => {
        items.push({
            ...item,
            key: item.type === 'set' ? `set-${item.block!.id}` : `standard-${item.exercise!.id}`,
            displayIndex: index
        });
    });
    
    return items;
});

// Convertir un exercice standard en bloc Super Set
const convertExerciseToSet = (exercise: SessionExercise) => {
    const index = sessionExercises.value.findIndex(e => e.id === exercise.id);
    if (index === -1) return;
    
    const blockId = nextBlockId++;
    const updatedExercise: SessionExercise = {
        ...exercise,
        block_id: blockId,
        block_type: 'set',
        position_in_block: 0,
    };
    
    sessionExercises.value[index] = updatedExercise;
    form.exercises = [...sessionExercises.value];
};

// Convertir un bloc Super Set en exercices standard
const convertBlockToStandard = (block: SessionBlock) => {
    const blockExercises = sessionExercises.value.filter(
        (ex: SessionExercise) => ex.block_id === block.id && ex.block_type === 'set'
    );
    
    blockExercises.forEach((ex: SessionExercise) => {
        const index = sessionExercises.value.findIndex((e: SessionExercise) => e.id === ex.id);
        if (index !== -1) {
            sessionExercises.value[index] = {
                ...ex,
                block_id: null,
                block_type: null,
                position_in_block: null,
            };
        }
    });
    
    form.exercises = [...sessionExercises.value];
};

// Gérer le dragover sur la zone principale
const handleDragOverMain = (e: DragEvent) => {
    if (!e.dataTransfer) return;
    const target = e.target as HTMLElement;
    const types = e.dataTransfer.types;
    if (types.includes('application/json')) {
        const supersetDropZone = target.closest('.superset-block [class*="border-dashed"]');
        if (supersetDropZone) return;
        e.dataTransfer.dropEffect = 'copy';
        isDraggingOver.value = true;
    } else {
        e.dataTransfer.dropEffect = 'move';
        isDraggingOver.value = false;
    }
};

// Gérer le dragleave sur la zone principale
const handleDragLeaveMain = (e: DragEvent) => {
    const target = e.relatedTarget as HTMLElement;
    if (target && target.closest('.superset-block')) return;
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    const x = e.clientX;
    const y = e.clientY;
    if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
        isDraggingOver.value = false;
    }
};

// Gérer le drop sur la zone principale
const handleDropMain = (e: DragEvent) => {
    if (!e.dataTransfer) return;
    const target = e.target as HTMLElement;
    const supersetDropZone = target.closest('.superset-block [class*="border-dashed"]');
    if (supersetDropZone) return;
    if (e.dataTransfer.types.includes('application/json')) {
        handleDropFromLibrary(e);
    }
};

// Supprimer un exercice de la séance
const removeExerciseFromSession = (index: number) => {
    sessionExercises.value.splice(index, 1);
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

// Réorganiser les exercices (utilisé par les boutons haut/bas et après drag)
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
    form.exercises = [...sessionExercises.value];
};

// Réorganiser les exercices après un drag (appelé automatiquement par VueDraggable)
const onDragEnd = () => {
    // Réorganiser les ordres après le drag
    sessionExercises.value.forEach((ex, idx) => {
        ex.order = idx;
    });
    form.exercises = [...sessionExercises.value];
};

const handleDropFromLibrary = (event: DragEvent) => {
    isDraggingOver.value = false;
    if (!event.dataTransfer) return;
    
    // Vérifier que le drop vient bien de la bibliothèque (pas d'un drag interne)
    // VueDraggable utilise des types différents, donc on vérifie la présence de 'application/json'
    const types = event.dataTransfer.types;
    if (!types.includes('application/json')) {
        // Ce n'est pas un drop depuis la bibliothèque, ignorer
        return;
    }
    
    try {
        const exerciseData = event.dataTransfer.getData('application/json');
        if (exerciseData) {
            const exercise: Exercise = JSON.parse(exerciseData);
            addExerciseToSession(exercise);
        } else {
            const exerciseId = event.dataTransfer.getData('text/plain');
            if (exerciseId) {
                const exercise = props.exercises.find(ex => ex.id === parseInt(exerciseId));
                if (exercise) {
                    addExerciseToSession(exercise);
                }
            }
        }
    } catch (error) {
        // Ignorer silencieusement les erreurs de drop
    }
};

watch(() => sessionExercises.value.length, () => {
    isDraggingOver.value = false;
});

// Filtrer les exercices disponibles
const filteredExercises = computed(() => {
    let exercises = props.exercises;

    if (showOnlyMine.value && currentUserId.value) {
        const userId = Number(currentUserId.value);
        exercises = exercises.filter(ex => Number(ex.user_id) === userId);
    }

    if (localSearchTerm.value.trim()) {
        exercises = exercises.filter(ex => 
            ex.title.toLowerCase().includes(localSearchTerm.value.toLowerCase())
        );
    }

    if (selectedCategoryId.value) {
        exercises = exercises.filter(ex => 
            ex.categories?.some(cat => cat.id === selectedCategoryId.value)
        );
    }

    return exercises;
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(localSearchTerm, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        searchTerm.value = newValue;
    }, 300);
});

// Validation du formulaire
const isFormValid = computed(() => {
    const hasName = form.name.trim().length > 0;
    const hasExercises = sessionExercises.value.length > 0;
    return hasName && hasExercises;
});

// Sauvegarder la séance (mise à jour)
const saveSession = () => {
    if (!form.name.trim()) {
        notifyError('Le nom de la séance est obligatoire.', 'Validation');
        return;
    }

    if (sessionExercises.value.length === 0) {
        notifyError('Veuillez ajouter au moins un exercice à la séance.', 'Validation');
        return;
    }

    isSaving.value = true;
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
    
    form.put(`/sessions/${props.session.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // La notification sera affichée automatiquement via le message flash du backend
            // Recharger la page d'édition avec les nouvelles données
            router.visit(`/sessions/${props.session.id}/edit`, {
                preserveScroll: true,
                preserveState: false,
            });
        },
        onError: (errors) => {
            isSaving.value = false;
            if (errors.name) {
                notifyError(errors.name);
            } else if (errors.exercises) {
                notifyError(errors.exercises);
            } else {
                notifyError('Une erreur est survenue lors de la mise à jour de la séance.');
            }
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

// Générer le PDF
const generatePDF = () => {
    if (sessionExercises.value.length === 0) {
        notifyError('Veuillez ajouter au moins un exercice à la séance avant de générer le PDF.', 'Validation');
        return;
    }

    if (!form.name.trim()) {
        notifyError('Le nom de la séance est obligatoire pour générer le PDF.', 'Validation');
        return;
    }

    const exercisesData = sessionExercises.value.map(ex => ({
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

    const requestData = {
        name: form.name,
        session_date: form.session_date,
        notes: form.notes || '',
        exercises: exercisesData,
    };

    const getCsrfToken = () => {
        const propsToken = (page.props as any).csrfToken;
        if (propsToken) return propsToken;
        
        const cookies = document.cookie.split(';');
        for (const cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'XSRF-TOKEN') {
                return decodeURIComponent(value);
            }
        }
        
        return '';
    };
    
    const csrfToken = getCsrfToken();
    
    if (!csrfToken) {
        notifyError('Token CSRF manquant. Veuillez rafraîchir la page.', 'Erreur');
        return;
    }

    fetch('/sessions/pdf-preview', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/pdf',
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(requestData),
    })
    .then(async response => {
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Erreur ${response.status}: ${errorText || 'Erreur lors de la génération du PDF'}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && !contentType.includes('application/pdf')) {
            const errorText = await response.text();
            throw new Error('Le serveur n\'a pas renvoyé un PDF valide');
        }
        
        return response.blob();
    })
    .then(blob => {
        if (blob.size === 0) {
            throw new Error('Le PDF généré est vide');
        }
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${form.name ? form.name.replace(/[^a-z0-9]/gi, '-').toLowerCase() : 'seance'}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        notifyError(error.message || 'Une erreur est survenue lors de la génération du PDF.', 'Erreur');
    });
};

// Ouvrir le PDF dans un nouvel onglet pour impression
const printPDF = () => {
    if (sessionExercises.value.length === 0) {
        notifyError('Veuillez ajouter au moins un exercice à la séance avant d\'imprimer.', 'Validation');
        return;
    }

    if (!form.name.trim()) {
        notifyError('Le nom de la séance est obligatoire pour imprimer.', 'Validation');
        return;
    }

    const exercisesData = sessionExercises.value.map(ex => ({
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

    const requestData = {
        name: form.name,
        session_date: form.session_date,
        notes: form.notes || '',
        exercises: exercisesData,
    };

    const getCsrfToken = () => {
        const propsToken = (page.props as any).csrfToken;
        if (propsToken) return propsToken;
        
        const cookies = document.cookie.split(';');
        for (const cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'XSRF-TOKEN') {
                return decodeURIComponent(value);
            }
        }
        
        return '';
    };
    
    const csrfToken = getCsrfToken();
    
    if (!csrfToken) {
        notifyError('Token CSRF manquant. Veuillez rafraîchir la page.', 'Erreur');
        return;
    }

    fetch('/sessions/pdf-preview', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/pdf',
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(requestData),
    })
    .then(async response => {
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Erreur serveur:', response.status, errorText);
            throw new Error(`Erreur ${response.status}: ${errorText || 'Erreur lors de la génération du PDF'}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && !contentType.includes('application/pdf')) {
            const errorText = await response.text();
            console.error('Réponse inattendue:', contentType, errorText);
            throw new Error('Le serveur n\'a pas renvoyé un PDF valide');
        }
        
        return response.blob();
    })
    .then(blob => {
        if (blob.size === 0) {
            throw new Error('Le PDF généré est vide');
        }
        
        // Créer une URL blob et ouvrir dans un nouvel onglet
        const url = window.URL.createObjectURL(blob);
        const printWindow = window.open(url, '_blank');
        
        if (printWindow) {
            // Attendre que le PDF soit chargé puis déclencher l'impression
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.print();
                }, 250);
            };
        } else {
            // Si la popup est bloquée, ouvrir dans le même onglet
            window.open(url, '_blank');
            notifyError('Veuillez autoriser les popups pour cette fonctionnalité.', 'Information');
        }
        
        // Nettoyer l'URL après un délai
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
        }, 1000);
    })
    .catch(error => {
        console.error('Erreur complète:', error);
        notifyError(error.message || 'Une erreur est survenue lors de l\'ouverture du PDF.', 'Erreur');
    });
};

// Détecter les exercices en double
const duplicateExercises = computed(() => {
    const exerciseIds = sessionExercises.value
        .filter(ex => ex && ex.exercise_id !== null && ex.exercise_id !== undefined)
        .map(ex => ex.exercise_id);
    const duplicates = exerciseIds.filter((id, index) => exerciseIds.indexOf(id) !== index);
    return [...new Set(duplicates)];
});

const hasDuplicateExercises = computed(() => duplicateExercises.value.length > 0);

// Synchroniser les exercices avec le formulaire
watch(sessionExercises, () => {
    form.exercises = sessionExercises.value;
}, { deep: true });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Modifier: ${session.name || 'Séance'}`" />

        <div class="flex flex-col h-full">
            <!-- Container global : aligne tout + espace avec le haut -->
            <div class="flex flex-col flex-1 p-6 space-y-4">
                <!-- En-tête aligné + détaché du haut -->
                <div
                    class="flex flex-col gap-3 border bg-white dark:bg-neutral-900 px-4 sm:px-6 lg:px-12 py-4 rounded-xl shadow-sm"
                >
                    <!-- Titre et date sur la même ligne sur grand écran -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <h1 class="text-xl sm:text-2xl font-semibold">Modifier la séance</h1>
                        <div class="flex items-center gap-1 text-sm text-neutral-600 dark:text-neutral-400 w-fit">
                            <Calendar class="h-4 w-4 flex-shrink-0" />
                            <input
                                v-model="form.session_date"
                                type="date"
                                class="border-none bg-transparent text-sm focus:outline-none w-auto"
                            />
                        </div>
                    </div>

                    <!-- Boutons organisés en grille responsive, alignés à droite sur grand écran -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:flex lg:items-center lg:justify-end lg:flex-wrap gap-2 sm:gap-3">
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full lg:w-auto"
                            @click="router.visit(`/sessions/${session.id}`)"
                        >
                            <ArrowLeft class="h-4 w-4 sm:mr-2" />
                            <span class="sm:inline">Annuler</span>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full lg:w-auto"
                            @click="generatePDF"
                            :disabled="sessionExercises.length === 0"
                        >
                            <FileText class="h-4 w-4 sm:mr-2" />
                            <span class="sm:inline">PDF</span>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full lg:w-auto"
                            @click="printPDF"
                            :disabled="sessionExercises.length === 0"
                        >
                            <Printer class="h-4 w-4 sm:mr-2" />
                            <span class="sm:inline">Imprimer</span>
                        </Button>
                        <Button
                            size="sm"
                            class="w-full lg:w-auto bg-blue-600 hover:bg-blue-700 text-white"
                            @click="saveSession"
                            :disabled="isSaving || !isFormValid"
                        >
                            <Save class="h-4 w-4 sm:mr-2" />
                            <span class="hidden sm:inline">{{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}</span>
                            <span class="sm:hidden">{{ isSaving ? '...' : 'Enregistrer' }}</span>
                        </Button>
                    </div>
                </div>

                <!-- Contenu principal, aligné sur le même container -->
                <div class="flex-1 flex flex-col lg:flex-row overflow-hidden gap-4">
                    <!-- Panneau gauche -->
                    <div class="w-full lg:w-3/5 overflow-y-auto rounded-xl">
                        <div class="space-y-6">
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
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="w-full justify-start"
                                            @click="openCustomerModal"
                                        >
                                            <Users class="h-4 w-4 mr-2" />
                                            {{ selectedCustomers.length > 0 ? `${selectedCustomers.length} client(s) sélectionné(s)` : 'Sélectionner des clients' }}
                                        </Button>
                                        
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
                                @dragover.prevent="handleDragOverMain"
                                @dragleave.prevent="handleDragLeaveMain"
                                @drop.prevent="handleDropMain"
                                class="min-h-[200px] transition-all duration-200 ease-out relative"
                            >
                                <!-- Ligne d'insertion discrète quand on drag depuis la bibliothèque -->
                                <div
                                    v-if="isDraggingOver"
                                    class="absolute top-0 left-0 right-0 h-0.5 bg-blue-500/40 rounded-full z-10"
                                ></div>
                                <div
                                    :class="{ 'opacity-50': isDraggingOver }"
                                    class="transition-opacity duration-200"
                                >
                                    <div v-if="sessionExercises.length === 0" class="text-center py-12 text-neutral-500">
                                        <p class="mb-2">Aucun exercice ajouté</p>
                                        <p class="text-sm">Glissez des exercices depuis la bibliothèque à droite</p>
                                    </div>
                                    <template v-if="orderedItems.length > 0">
                                        <template v-for="(item, itemIndex) in orderedItems" :key="item.key">
                                            <!-- Bloc Super Set -->
                                            <SessionBlockSet
                                                v-if="item.type === 'set'"
                                                :block="item.block!"
                                                :block-index="itemIndex"
                                                :display-index="item.displayIndex"
                                                :draggable="true"
                                                :total-count="orderedItems.length"
                                                @drop="(event: DragEvent, blockId: number) => handleDropFromLibrary(event, blockId)"
                                                @remove-exercise="(index: number) => handleRemoveExerciseFromBlock(item, index)"
                                                @update-exercise="(index: number, updates: Partial<SessionExercise>) => {
                                                    const blockExercises = sessionExercises.value.filter(
                                                        (ex: SessionExercise) => ex.block_id === item.block!.id && ex.block_type === 'set'
                                                    );
                                                    const exerciseIndex = sessionExercises.value.findIndex(
                                                        (ex: SessionExercise) => ex.id === blockExercises[index].id
                                                    );
                                                    if (exerciseIndex !== -1) {
                                                        updateSessionExercise(exerciseIndex, updates);
                                                    }
                                                }"
                                                @update-block-description="(description: string) => {
                                                    const blockExercises = sessionExercises.value.filter(
                                                        (ex: SessionExercise) => ex.block_id === item.block!.id && ex.block_type === 'set'
                                                    );
                                                    blockExercises.forEach((ex: SessionExercise) => {
                                                        ex.description = description;
                                                    });
                                                    form.exercises = [...sessionExercises.value];
                                                }"
                                                @convert-to-standard="() => convertBlockToStandard(item.block!)"
                                                @remove-block="() => handleRemoveBlock(item)"
                                                @move-up="() => { if (itemIndex > 0) reorderItems(item, orderedItems[itemIndex - 1]); }"
                                                @move-down="() => { if (itemIndex < orderedItems.length - 1) reorderItems(item, orderedItems[itemIndex + 1]); }"
                                            />
                                            <!-- Exercice standard -->
                                            <SessionExerciseItem
                                                v-else
                                                :session-exercise="item.exercise!"
                                                :index="sessionExercises.findIndex(e => e.id === item.exercise!.id)"
                                                :display-index="item.displayIndex"
                                                :draggable="true"
                                                :total-count="orderedItems.length"
                                                @update="(updates: Partial<SessionExercise>) => {
                                                    const index = sessionExercises.value.findIndex(e => e.id === item.exercise!.id);
                                                    if (index !== -1) {
                                                        updateSessionExercise(index, updates);
                                                    }
                                                }"
                                                @remove="() => handleRemoveExercise(item)"
                                                @move-up="() => { if (itemIndex > 0) reorderItems(item, orderedItems[itemIndex - 1]); }"
                                                @move-down="() => { if (itemIndex < orderedItems.length - 1) reorderItems(item, orderedItems[itemIndex + 1]); }"
                                                @convert-to-set="() => convertExerciseToSet(item.exercise!)"
                                            />
                                        </template>
                                    </template>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                    <!-- Panneau droit -->
                    <div class="hidden lg:block w-full lg:w-2/5 overflow-y-auto rounded-xl">
                        <div>
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
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-neutral-400" />
                        <Input
                            v-model="customerSearchTerm"
                            placeholder="Rechercher un client par nom ou email..."
                            class="pl-9"
                        />
                    </div>

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

        <!-- Bouton flottant pour ouvrir la bibliothèque sur mobile -->
        <Button
            class="fixed bottom-6 right-6 z-50 h-14 w-14 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg lg:hidden"
            @click="isLibraryOpen = true"
        >
            <Library class="h-6 w-6" />
        </Button>

        <!-- Sheet pour la bibliothèque sur mobile -->
        <Sheet v-model:open="isLibraryOpen">
            <SheetContent side="right" class="w-full sm:max-w-lg p-0">
                <div class="h-full">
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
                        @add-exercise="(exercise: Exercise) => { addExerciseToSession(exercise); isLibraryOpen = false; }"
                    />
                </div>
            </SheetContent>
        </Sheet>
    </AppLayout>
</template>


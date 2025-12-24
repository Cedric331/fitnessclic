<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { 
    Calendar, 
    FileText, 
    Save, 
    Trash2, 
    Search, 
    X,
    AlertTriangle,
    Library,
    Printer,
    Plus
} from 'lucide-vue-next';
import type { CreateSessionProps, Exercise, SessionExercise, SessionBlock, ExerciseSet } from './types';
import SessionExerciseItem from './SessionExerciseItem.vue';
import SessionBlockSet from './SessionBlockSet.vue';
import ExerciseLibrary from './ExerciseLibrary.vue';
import SessionLayoutEditor from './SessionLayoutEditor.vue';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
import UpgradeModal from '@/components/UpgradeModal.vue';

const props = defineProps<CreateSessionProps>();
const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);
const isPro = computed(() => (page.props.auth as any)?.user?.isPro ?? false);
const { success: notifySuccess, error: notifyError } = useNotifications();

const isUpgradeModalOpen = ref(false);
const showLayoutEditor = ref(false);
const savedSessionId = ref<number | null>(null);

// Dialog pour sélectionner un client avant export PDF/impression
const isCustomerSelectDialogOpen = ref(false);
const selectedCustomerIdForPdf = ref<number | null>(null);
const pendingPdfAction = ref<'download' | 'print' | null>(null);

// Empêche le "scroll fantôme" de la page quand l'éditeur libre (overlay) est ouvert
const previousOverflow = ref<{ html: string; body: string } | null>(null);
const lockPageScroll = () => {
    if (previousOverflow.value) return;
    previousOverflow.value = {
        html: document.documentElement.style.overflow,
        body: document.body.style.overflow,
    };
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
};
const unlockPageScroll = () => {
    if (!previousOverflow.value) return;
    document.documentElement.style.overflow = previousOverflow.value.html;
    document.body.style.overflow = previousOverflow.value.body;
    previousOverflow.value = null;
};

watch(showLayoutEditor, (open) => {
    if (open) lockPageScroll();
    else unlockPageScroll();
});

onUnmounted(() => {
    unlockPageScroll();
});

const shownFlashMessages = ref(new Set<string>());

watch(() => (page.props as any).flash, (flash) => {
    if (!flash) return;
    
    const successKey = flash.success ? `success-${flash.success}` : null;
    const errorKey = flash.error ? `error-${flash.error}` : null;
    
    if (successKey && !shownFlashMessages.value.has(successKey)) {
        shownFlashMessages.value.add(successKey);
        notifySuccess(flash.success);
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

const form = useForm({
    name: '',
    customer_ids: [] as number[],
    session_date: new Date().toISOString().split('T')[0],
    notes: '',
    exercises: [] as SessionExercise[],
});

const showCustomerModal = ref(false);
const tempSelectedCustomerIds = ref<number[]>([]);
const customerSearchTerm = ref('');

const selectedCustomers = computed(() => {
    if (!form.customer_ids || form.customer_ids.length === 0) {
        return [];
    }
    return props.customers.filter(customer => 
        form.customer_ids.includes(customer.id)
    );
});

const customers = computed(() => props.customers);

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

// Ouvrir la modal avec les clients actuellement sélectionnés
const openCustomerModal = () => {
    // Vérifier si l'utilisateur est Pro
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }
    tempSelectedCustomerIds.value = [...form.customer_ids];
    customerSearchTerm.value = '';
    showCustomerModal.value = true;
};

// Confirmer la sélection des clients
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

const searchTerm = ref(props.filters.search || '');
const localSearchTerm = ref(props.filters.search || '');
const selectedCategoryId = ref<number | null>(props.filters.category_id || null);
const getDefaultViewMode = (): 'grid-2' | 'grid-4' | 'grid-6' | 'list' => {
    if (typeof window !== 'undefined') {
        if (window.innerWidth < 640) {
            return 'grid-6';
        }
    }
    return 'grid-4';
};

const viewMode = ref<'grid-2' | 'grid-4' | 'grid-6' | 'list'>(getDefaultViewMode());

const DESKTOP_MIN_WIDTH = 1700; // 2xl breakpoint
const isDesktopWidth = () => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia(`(min-width: ${DESKTOP_MIN_WIDTH}px)`).matches;
};

// Fonction pour obtenir le mode depuis localStorage
const getEditMode = (): 'standard' | 'libre' => {
    if (typeof window !== 'undefined') {
        // Mode libre réservé au desktop
        if (!isDesktopWidth()) {
            try {
                localStorage.setItem('editMode', 'standard');
            } catch (e) {
            }
            return 'standard';
        }
        const stored = localStorage.getItem('editMode');
        if (stored === 'libre' || stored === 'standard') {
            return stored;
        }
    }
    return 'standard'; // Par défaut: Standard
};

onMounted(() => {
    // Mettre à jour la détection mobile
    updateMobileDevice();
    
    const handleResize = () => {
        // Mettre à jour la détection mobile
        updateMobileDevice();
        
        if (window.innerWidth < 640 && viewMode.value !== 'grid-6') {
            viewMode.value = 'grid-6';
        } else if (window.innerWidth >= 640 && viewMode.value === 'grid-6' && window.innerWidth < 1024) {
            viewMode.value = 'grid-6';
        }

        // Si on passe sur tablette/mobile, forcer le mode standard et fermer l'éditeur libre
        if (window.innerWidth < DESKTOP_MIN_WIDTH) {
            showLayoutEditor.value = false;
            try {
                localStorage.setItem('editMode', 'standard');
            } catch (e) {
            }
        }
    };
    
    window.addEventListener('resize', handleResize);
    handleResize();
    
    // Si le mode est "libre", ouvrir directement l'éditeur
    const editMode = getEditMode();
    if (editMode === 'libre') {
        showLayoutEditor.value = true;
    }
});
const showOnlyMine = ref(false);
const isSaving = ref(false);
const isLibraryOpen = ref(false);
const isClearDialogOpen = ref(false);
const libraryToggleButton = ref<HTMLElement | null>(null);

// Modal pour choisir le super set sur mobile
const showSuperSetModal = ref(false);
const pendingExercise = ref<Exercise | null>(null);
const availableSuperSets = computed(() => {
    const blocks = groupExercisesIntoBlocks();
    return blocks.set;
});

const sessionExercises = ref<SessionExercise[]>([]);

const getSessionExercises = (): SessionExercise[] => {
    if (!sessionExercises.value || !Array.isArray(sessionExercises.value)) {
        sessionExercises.value = [];
    }
    return sessionExercises.value;
};

const exercisesKey = ref(0);
const isDraggingOver = ref(false);
let sessionExerciseIdCounter = 0;

let nextBlockId = 1;

const isSetMode = ref(false);

const STORAGE_KEY = 'fitnessclic_session_exercises';

const loadExercisesFromStorage = () => {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed) && parsed.length > 0) {
                const validExercises = parsed
                    .filter((ex: SessionExercise) => 
                        ex && ex.exercise_id !== null && ex.exercise_id !== undefined
                    )
                    .map((ex: SessionExercise, idx: number) => {
                        if (!ex.id) {
                            ex.id = --sessionExerciseIdCounter;
                        }
                        return ex;
                    });
                
                // Mettre à jour le compteur pour éviter les IDs dupliqués
                // Trouver le plus petit ID négatif et initialiser le compteur en dessous
                const minId = validExercises.reduce((min: number, ex: SessionExercise) => {
                    if (ex.id && ex.id < 0 && ex.id < min) {
                        return ex.id;
                    }
                    return min;
                }, 0);
                if (minId < 0) {
                    sessionExerciseIdCounter = minId - 1;
                }
                
                if (validExercises.length > 0) {
                    sessionExercises.value = validExercises;
                    form.exercises = validExercises;
                }
            }
        }
    } catch (error) {
    }
};

const saveExercisesToStorage = () => {
    try {
        if (sessionExercises.value.length > 0) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(sessionExercises.value));
        } else {
            localStorage.removeItem(STORAGE_KEY);
        }
    } catch (error) {
    }
};

loadExercisesFromStorage();

// Détecter si on est sur mobile/tablette (même logique que le reste du codebase)
const isMobileDevice = ref(false);
const updateMobileDevice = () => {
    if (typeof window !== 'undefined') {
        isMobileDevice.value = window.innerWidth < DESKTOP_MIN_WIDTH;
    }
};

const addExerciseToSession = (exercise: Exercise, targetBlockId?: number) => {
    if (!exercise || !exercise.id) {
        return;
    }
    
    // Si on est sur mobile/tablette et qu'il y a des super sets, afficher la modal
    if (isMobileDevice.value && availableSuperSets.value.length > 0 && targetBlockId === undefined) {
        pendingExercise.value = exercise;
        showSuperSetModal.value = true;
        return;
    }
    
    // Ajouter l'exercice normalement ou dans un super set
    if (targetBlockId) {
        addExerciseToSetBlock(exercise, targetBlockId);
    } else {
        const sessionExercise: SessionExercise = {
            id: --sessionExerciseIdCounter,
            exercise_id: exercise.id,
            exercise: exercise,
            sets: [{
                set_number: 1,
                repetitions: 10,
                weight: 10,
                rest_time: '30s',
                duration: '30s',
                order: 0
            }],
            repetitions: 10,
            weight: 10,
            rest_time: '30s',
            duration: '30s',
            description: '',
            sets_count: 1,
            order: sessionExercises.value.length,
            block_id: null,
            block_type: null,
            position_in_block: null,
        };
        sessionExercises.value.push(sessionExercise);
        sessionExercises.value = [...sessionExercises.value];
        form.exercises = [...sessionExercises.value];
        saveExercisesToStorage();
    }
};

// Confirmer l'ajout dans un super set depuis la modal
const confirmAddToSuperSet = (blockId: number) => {
    if (pendingExercise.value) {
        addExerciseToSetBlock(pendingExercise.value, blockId);
        showSuperSetModal.value = false;
        pendingExercise.value = null;
        isLibraryOpen.value = false;
    }
};

// Ajouter normalement (sans super set)
const addToSessionNormal = () => {
    if (pendingExercise.value) {
        addExerciseToSession(pendingExercise.value, undefined);
        showSuperSetModal.value = false;
        pendingExercise.value = null;
        isLibraryOpen.value = false;
    }
};

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
    saveExercisesToStorage();
};

const addExerciseToSetBlock = (exercise: Exercise, blockId: number) => {
    if (!exercise || !exercise.id) {
        return;
    }
    
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
            repetitions: 10,
            weight: 10,
            rest_time: '30s',
            duration: '30',
            order: 0
        }],
        repetitions: 10,
        weight: 10,
        rest_time: '30s',
        duration: '30',
        description: '',
        sets_count: 1,
        block_id: blockId,
        block_type: 'set',
        position_in_block: positionInBlock,
        order: sessionExercises.value.length,
    };
    
    sessionExercises.value.push(sessionExercise);
    form.exercises = [...sessionExercises.value];
    saveExercisesToStorage();
};

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
            standardExercises.push(ex);
        }
    });
    
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

const handleRemoveExercise = (item: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }) => {
    if (!item?.exercise?.id) {
        return;
    }
    const index = sessionExercises.value.findIndex((e: SessionExercise) => e?.id === item.exercise!.id);
    if (index !== -1) {
        removeExerciseFromSession(index);
    }
};

const handleRemoveExerciseFromBlock = (item: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }, exerciseId: number) => {
    console.log('Create - handleRemoveExerciseFromBlock:', {
        exerciseId,
        item,
        blockId: item?.block?.id,
        allSessionExercises: sessionExercises.value.map((e, i) => ({ 
            index: i, 
            id: e.id, 
            block_id: e.block_id, 
            position_in_block: e.position_in_block,
            title: e.exercise?.title 
        }))
    });
    
    if (!exerciseId) {
        console.log('Create - handleRemoveExerciseFromBlock: No exerciseId provided');
        return;
    }
    
    // Si plusieurs exercices ont le même ID (bug), trouver celui qui correspond au bloc
    let exerciseIndex = -1;
    if (item?.block?.id) {
        // Chercher dans le bloc spécifique d'abord
        const blockExercises = sessionExercises.value.filter(
            (ex: SessionExercise) => ex?.block_id === item.block!.id && ex?.block_type === 'set'
        );
        const exerciseInBlock = blockExercises.find((ex: SessionExercise) => ex?.id === exerciseId);
        if (exerciseInBlock) {
            exerciseIndex = sessionExercises.value.findIndex((e: SessionExercise) => e === exerciseInBlock);
        }
    }
    
    // Si pas trouvé dans le bloc, chercher globalement (fallback)
    if (exerciseIndex === -1) {
        exerciseIndex = sessionExercises.value.findIndex(
            (ex: SessionExercise) => ex?.id === exerciseId
        );
    }
        
    if (exerciseIndex !== -1) {
        const exerciseToRemove = sessionExercises.value[exerciseIndex];
        console.log('Create - handleRemoveExerciseFromBlock: Removing exercise:', {
            index: exerciseIndex,
            id: exerciseToRemove.id,
            title: exerciseToRemove.exercise?.title
        });
        removeExerciseFromSession(exerciseIndex);
    }
};

const handleRemoveBlock = (item: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }) => {
    if (!item?.block?.id) {
        return;
    }
    const blockExercises = sessionExercises.value.filter(
        (ex: SessionExercise) => ex.block_id === item.block!.id && ex.block_type === 'set'
    );
    blockExercises.reverse().forEach((ex: SessionExercise) => {
        const index = sessionExercises.value.findIndex((e: SessionExercise) => e.id === ex.id);
        if (index !== -1) {
            removeExerciseFromSession(index);
        }
    });
};

// Supprimer un exercice de la séance
const removeExerciseFromSession = (index: number) => {
    if (index < 0 || index >= sessionExercises.value.length) {
        return;
    }
    const exercise = sessionExercises.value[index];
    if (!exercise) {
        return;
    }
    
    sessionExercises.value.splice(index, 1);
    
    if (exercise?.block_id && exercise?.block_type === 'set') {
        const blockExercises = sessionExercises.value.filter(
            ex => ex && ex.block_id === exercise.block_id && ex.block_type === 'set'
        );
        blockExercises.forEach((ex, idx) => {
            ex.position_in_block = idx;
        });
    }
    
    sessionExercises.value.forEach((ex, idx) => {
        ex.order = idx;
    });
    
    form.exercises = [...sessionExercises.value];
    saveExercisesToStorage();
};

const handleExerciseUpdate = (exercise: SessionExercise | undefined, updates: Partial<SessionExercise>) => {
    if (!sessionExercises) {
        return;
    }
    
    if (!sessionExercises.value) {
        sessionExercises.value = [];
    }
    
    if (!exercise) {
        return;
    }
    
    let index = -1;
    
    // PRIORITÉ 1: Chercher par exercise_id ET order (le plus fiable car unique)
    // C'est la meilleure méthode car exercise_id + order identifie de manière unique un exercice
    index = sessionExercises.value.findIndex((e: SessionExercise) => 
        e.exercise_id === exercise.exercise_id && 
        e.order === exercise.order &&
        (!e.block_id && !e.block_type) === (!exercise.block_id && !exercise.block_type)
    );
    
    // PRIORITÉ 2: Si pas trouvé, chercher par ID (peut être dupliqué, donc moins fiable)
    if (index === -1 && exercise.id) {
        // Si plusieurs exercices ont le même ID, chercher celui avec le même exercise_id et order
        const candidatesById = sessionExercises.value
            .map((e, idx) => ({ e, idx }))
            .filter(({ e }) => e.id === exercise.id);
        
        if (candidatesById.length === 1) {
            index = candidatesById[0].idx;
        } else if (candidatesById.length > 1) {
            // Si plusieurs exercices avec le même ID, prendre celui avec le même exercise_id et order
            const bestMatch = candidatesById.find(({ e }) => 
                e.exercise_id === exercise.exercise_id && 
                e.order === exercise.order
            );
            if (bestMatch) {
                index = bestMatch.idx;
            } else {
                // Si pas de match parfait, prendre le premier avec le même exercise_id
                const sameExerciseId = candidatesById.find(({ e }) => e.exercise_id === exercise.exercise_id);
                index = sameExerciseId ? sameExerciseId.idx : candidatesById[0].idx;
            }
        }
    }
    
    // PRIORITÉ 3: Dernier recours - chercher uniquement par exercise_id
    if (index === -1 && exercise.exercise_id) {
        const candidates = sessionExercises.value
            .map((e, idx) => ({ e, idx }))
            .filter(({ e }) => 
                e.exercise_id === exercise.exercise_id && 
                (!e.block_id && !e.block_type) === (!exercise.block_id && !exercise.block_type)
            );
        
        if (candidates.length === 1) {
            index = candidates[0].idx;
        } else if (candidates.length > 1) {
            // Si plusieurs correspondances, prendre celui avec le même order ou le dernier
            const sameOrder = candidates.find(({ e }) => e.order === exercise.order);
            index = sameOrder ? sameOrder.idx : candidates[candidates.length - 1].idx;
        }
    }
    
    if (index !== -1) {
        updateSessionExercise(index, updates);
    }
};

const updateSessionExercise = (index: number, updates: Partial<SessionExercise>) => {
    const currentExercise = sessionExercises.value[index];
    
    if (updates.sets) {
        const setsArray = Array.isArray(updates.sets) 
            ? updates.sets.map((set: any) => ({
                set_number: set.set_number ?? 1,
                repetitions: set.repetitions ?? null,
                weight: set.weight ?? null,
                rest_time: set.rest_time ?? null,
                duration: set.duration ?? null,
                use_duration: set.use_duration !== undefined ? set.use_duration : (currentExercise.use_duration ?? false),
                use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (currentExercise.use_bodyweight ?? false),
                order: set.order ?? 0
            } as ExerciseSet))
            : [];
        
        const { sets: _, ...updatesWithoutSets } = updates;
        
        const exercise = sessionExercises.value[index];
        for (const key in updatesWithoutSets) {
            (exercise as any)[key] = (updatesWithoutSets as any)[key];
        }
        exercise.sets = setsArray;
        // Forcer la réactivité en créant une nouvelle référence du tableau
        sessionExercises.value = [...sessionExercises.value];
    } else {
        // Mettre à jour directement les propriétés pour préserver la référence
        const exercise = sessionExercises.value[index];
        for (const key in updates) {
            (exercise as any)[key] = (updates as any)[key];
        }
        // Forcer la réactivité en créant une nouvelle référence du tableau
        sessionExercises.value = [...sessionExercises.value];
    }
    
    
    // Sauvegarder dans le localStorage pour persister les changements
    saveExercisesToStorage();
};

const reorderExercises = (fromIndex: number, toIndex: number) => {
    if (fromIndex === toIndex) return;
    if (fromIndex < 0 || fromIndex >= sessionExercises.value.length) return;
    if (toIndex < 0 || toIndex >= sessionExercises.value.length) return;
    
    const [moved] = sessionExercises.value.splice(fromIndex, 1);
    sessionExercises.value.splice(toIndex, 0, moved);
    sessionExercises.value.forEach((ex, idx) => {
        ex.order = idx;
    });
    form.exercises = [...sessionExercises.value];
    saveExercisesToStorage();
};

// Réorganiser les items (exercices standard ou blocs Super Set)
const reorderItems = (fromItem: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }, toItem: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }) => {
    if (fromItem.type === 'standard' && toItem.type === 'standard') {
        // Deux exercices standard
        const fromIndex = sessionExercises.value.findIndex(e => e.id === fromItem.exercise!.id);
        const toIndex = sessionExercises.value.findIndex(e => e.id === toItem.exercise!.id);
        if (fromIndex !== -1 && toIndex !== -1) {
            reorderExercises(fromIndex, toIndex);
        }
    } else if (fromItem.type === 'set' && toItem.type === 'set') {
        // Deux blocs Super Set - réorganiser tous les exercices du bloc
        const fromBlockExercises = sessionExercises.value.filter(
            (ex: SessionExercise) => ex.block_id === fromItem.block!.id && ex.block_type === 'set'
        );
        const toBlockExercises = sessionExercises.value.filter(
            (ex: SessionExercise) => ex.block_id === toItem.block!.id && ex.block_type === 'set'
        );
        
        if (fromBlockExercises.length > 0 && toBlockExercises.length > 0) {
            const fromOrder = fromBlockExercises[0].order;
            const toOrder = toBlockExercises[0].order;
            
            // Échanger les ordres
            fromBlockExercises.forEach(ex => {
                const index = sessionExercises.value.findIndex(e => e.id === ex.id);
                if (index !== -1) {
                    sessionExercises.value[index].order = toOrder;
                }
            });
            toBlockExercises.forEach(ex => {
                const index = sessionExercises.value.findIndex(e => e.id === ex.id);
                if (index !== -1) {
                    sessionExercises.value[index].order = fromOrder;
                }
            });
            
            // Réorganiser tous les ordres
            sessionExercises.value.sort((a, b) => a.order - b.order);
            sessionExercises.value.forEach((ex, idx) => {
                ex.order = idx;
            });
            form.exercises = [...sessionExercises.value];
            saveExercisesToStorage();
        }
    } else {
        // Mélange standard/Super Set - réorganiser par ordre
        const fromOrder = fromItem.type === 'standard' 
            ? fromItem.exercise!.order 
            : sessionExercises.value.find((ex: SessionExercise) => ex.block_id === fromItem.block!.id && ex.block_type === 'set')?.order || 0;
        const toOrder = toItem.type === 'standard'
            ? toItem.exercise!.order
            : sessionExercises.value.find((ex: SessionExercise) => ex.block_id === toItem.block!.id && ex.block_type === 'set')?.order || 0;
        
        if (fromItem.type === 'standard') {
            const index = sessionExercises.value.findIndex(e => e.id === fromItem.exercise!.id);
            if (index !== -1) {
                sessionExercises.value[index].order = toOrder;
            }
        } else {
            const fromBlockExercises = sessionExercises.value.filter(
                (ex: SessionExercise) => ex.block_id === fromItem.block!.id && ex.block_type === 'set'
            );
            fromBlockExercises.forEach(ex => {
                const index = sessionExercises.value.findIndex(e => e.id === ex.id);
                if (index !== -1) {
                    sessionExercises.value[index].order = toOrder;
                }
            });
        }
        
        if (toItem.type === 'standard') {
            const index = sessionExercises.value.findIndex(e => e.id === toItem.exercise!.id);
            if (index !== -1) {
                sessionExercises.value[index].order = fromOrder;
            }
        } else {
            const toBlockExercises = sessionExercises.value.filter(
                (ex: SessionExercise) => ex.block_id === toItem.block!.id && ex.block_type === 'set'
            );
            toBlockExercises.forEach(ex => {
                const index = sessionExercises.value.findIndex(e => e.id === ex.id);
                if (index !== -1) {
                    sessionExercises.value[index].order = fromOrder;
                }
            });
        }
        
        // Réorganiser tous les ordres
        sessionExercises.value.sort((a, b) => a.order - b.order);
        sessionExercises.value.forEach((ex, idx) => {
            ex.order = idx;
        });
        form.exercises = [...sessionExercises.value];
        saveExercisesToStorage();
    }
};

// Réorganiser les exercices après un drag (appelé automatiquement par VueDraggable)
const onDragUpdate = (newItems: Array<{ type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock, key: string, order: number, displayIndex: number, exerciseIndexInSession?: number }>) => {
    const keysSeen = new Set<string>();
    const duplicates: string[] = [];
    newItems.forEach((item, idx) => {
        if (keysSeen.has(item.key)) {
            duplicates.push(`Index ${idx}: ${item.key}`);
        } else {
            keysSeen.add(item.key);
        }
    });
    if (duplicates.length > 0) {
        return;
    }
    
    const processedExerciseIndices = new Set<number>();
    const processedBlockIds = new Set<number>();
    
    newItems.forEach((item, newIndex) => {
        if (item.type === 'standard' && item.exercise) {
            let index = item.exerciseIndexInSession;
            if (index === undefined || index < 0) {
                const keyMatch = item.key.match(/standard-.*-idx(\d+)/);
                if (keyMatch) {
                    index = parseInt(keyMatch[1], 10);
                } else {
                    index = sessionExercises.value.findIndex(e => e === item.exercise);
                }
            }
            
            if (index >= 0 && index < sessionExercises.value.length) {
                if (processedExerciseIndices.has(index)) {
                    return;
                }
                
                sessionExercises.value[index].order = newIndex;
                processedExerciseIndices.add(index);
            }
        } else if (item.type === 'set' && item.block && item.block.id !== undefined) {
            if (processedBlockIds.has(item.block.id)) {
                return;
            }
            
            const blockExercises = sessionExercises.value.filter(
                (ex: SessionExercise) => ex.block_id === item.block!.id && ex.block_type === 'set'
            );
            blockExercises.forEach(ex => {
                const index = sessionExercises.value.findIndex(e => e === ex);
                if (index >= 0 && index < sessionExercises.value.length) {
                    if (processedExerciseIndices.has(index)) {
                        return;
                    }
                    sessionExercises.value[index].order = newIndex;
                    processedExerciseIndices.add(index);
                }
            });
            processedBlockIds.add(item.block.id);
        }
    });
    
    form.exercises = [...sessionExercises.value];
    saveExercisesToStorage();
};

const orderedItems = computed(() => {
    const blocks = groupExercisesIntoBlocks();
    const items: Array<{ type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock, key: string, order: number, displayIndex: number, exerciseIndexInSession?: number }> = [];
    
    const allItems: Array<{ type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock, order: number, exerciseIndexInSession?: number }> = [];
    
    blocks.standard.forEach(ex => {
        const indexInSession = sessionExercises.value.findIndex(e => e === ex);
        allItems.push({
            type: 'standard',
            exercise: ex,
            order: ex.order,
            exerciseIndexInSession: indexInSession >= 0 ? indexInSession : undefined
        });
    });
    
    blocks.set.forEach(block => {
        allItems.push({
            type: 'set',
            block: block,
            order: block.order
        });
    });
    
    allItems.sort((a, b) => a.order - b.order);
    
    allItems.forEach((item, displayIndex) => {
        let key: string;
        if (item.type === 'set') {
            key = `set-${item.block?.id ?? 'unknown'}`;
        } else {
            const indexInSession = item.exerciseIndexInSession ?? sessionExercises.value.findIndex(e => e === item.exercise);
            key = `standard-${item.exercise?.id ?? 'unknown'}-idx${indexInSession}`;
        }
        
        items.push({
            ...item,
            key: key,
            displayIndex: displayIndex
        });
    });
    
    return items;
});


const getExerciseIndex = (exercise: SessionExercise): number => {
    return sessionExercises.value.findIndex(e => e.id === exercise.id);
};

const convertExerciseToSet = (exercise: SessionExercise) => {
    const index = sessionExercises.value.findIndex(e => e.id === exercise.id);
    if (index === -1) return;
    
    const blockId = nextBlockId++;
    const updatedSets = exercise.sets && exercise.sets.length > 0 
        ? exercise.sets.map(set => ({
            ...set,
            repetitions: set.repetitions ?? 10,
            weight: set.weight ?? 10,
            rest_time: set.rest_time ?? '30s',
            duration: set.duration ?? '30',
        }))
        : [{
            set_number: 1,
            repetitions: 10,
            weight: 10,
            rest_time: '30s',
            duration: '30',
            order: 0
        }];
    
    const updatedExercise: SessionExercise = {
        ...exercise,
        sets: updatedSets,
        repetitions: exercise.repetitions ?? 10,
        weight: exercise.weight ?? 10,
        rest_time: exercise.rest_time ?? '30s',
        duration: exercise.duration ?? '30',
        sets_count: exercise.sets_count ?? 1,
        block_id: blockId,
        block_type: 'set',
        position_in_block: 0,
    };
    
    sessionExercises.value[index] = updatedExercise;
    form.exercises = [...sessionExercises.value];
    saveExercisesToStorage();
};

const handleUpdateBlockDescription = (blockId: number, description: string) => {
    const exercises = getSessionExercises();
    if (!exercises || exercises.length === 0) {
        return;
    }
    const blockExercises = exercises.filter(
        (ex: SessionExercise) => ex.block_id === blockId && ex.block_type === 'set'
    );
    blockExercises.forEach((ex: SessionExercise) => {
        const index = exercises.findIndex((e: SessionExercise) => e.id === ex.id);
        if (index !== -1) {
            updateSessionExercise(index, { description });
        }
    });
    form.exercises = [...getSessionExercises()];
    saveExercisesToStorage();
};

const handleUpdateExerciseFromBlock = (item: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }, exerciseIdOrIndex: number, updates: Partial<SessionExercise>) => {
    if (!sessionExercises.value || !Array.isArray(sessionExercises.value)) {
        return;
    }
    
    let exerciseToUpdate: SessionExercise | undefined;
    
    if (exerciseIdOrIndex < 0 || exerciseIdOrIndex > 1000) {
        const index = sessionExercises.value.findIndex((e: SessionExercise) => e.id === exerciseIdOrIndex);
        if (index !== -1) {
            updateSessionExercise(index, updates);
            return;
        }
    } else {
        if (!item.block || !item.block.exercises[exerciseIdOrIndex]) {
            return;
        }
        exerciseToUpdate = item.block.exercises[exerciseIdOrIndex];
    }
    
    if (exerciseToUpdate) {
        let index = -1;
        if (exerciseToUpdate.id && exerciseToUpdate.id > 0) {
            index = sessionExercises.value.findIndex((e: SessionExercise) => e.id === exerciseToUpdate.id);
        }
        
        if (index === -1 && item.block) {
            const targetPositionInBlock = exerciseToUpdate.position_in_block ?? exerciseIdOrIndex;
            index = sessionExercises.value.findIndex((e: SessionExercise) => 
                e.block_id === item.block!.id && 
                e.position_in_block === targetPositionInBlock &&
                e.block_type === 'set'
            );
            
            if (index === -1) {
                const blockExercises = sessionExercises.value.filter(
                    (ex: SessionExercise) => ex.block_id === item.block!.id && ex.block_type === 'set'
                );
                const sortedBlockExercises = blockExercises.sort((a, b) => 
                    (a.position_in_block || 0) - (b.position_in_block || 0)
                );
                if (sortedBlockExercises[exerciseIdOrIndex]) {
                    const targetExercise = sortedBlockExercises[exerciseIdOrIndex];
                    index = sessionExercises.value.findIndex((e: SessionExercise) => 
                        e.id === targetExercise.id
                    );
                }
            }
        }
        
        if (index !== -1) {
            updateSessionExercise(index, updates);
        }
    }
};

const convertBlockToStandard = (block: SessionBlock) => {
    const blockExercises = sessionExercises.value.filter(
        (ex: SessionExercise) => ex.block_id === block.id && ex.block_type === 'set'
    );
    
    const sortedExercises = [...blockExercises].sort((a, b) => 
        (a.position_in_block || 0) - (b.position_in_block || 0)
    );
    
    if (sortedExercises.length === 0) {
        return;
    }
    
    const firstExercise = sortedExercises[0];
    const firstIndex = sessionExercises.value.findIndex((e: SessionExercise) => e.id === firstExercise.id);
    if (firstIndex !== -1) {
        sessionExercises.value[firstIndex] = {
            ...firstExercise,
            block_id: null,
            block_type: null,
            position_in_block: null,
        };
    }
    
    const exercisesToRemove = sortedExercises.slice(1).reverse();
    exercisesToRemove.forEach((ex: SessionExercise) => {
        const index = sessionExercises.value.findIndex((e: SessionExercise) => e.id === ex.id);
        if (index !== -1) {
            removeExerciseFromSession(index);
        }
    });
    
    form.exercises = [...sessionExercises.value];
    saveExercisesToStorage();
};

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

const handleDropMain = (e: DragEvent) => {
    const target = e.target as HTMLElement;
    const supersetDropZone = target.closest('.superset-block [class*="border-dashed"]');
    if (supersetDropZone) return;
    
    // Vérifier si c'est un drop custom (mobile) ou un drop HTML5 normal
    const customData = (e.dataTransfer as any)?.dropData;
    if (customData || (e.dataTransfer && e.dataTransfer.types.includes('application/json'))) {
        handleDropFromLibrary(e);
    }
};

const handleDropFromLibrary = (event: DragEvent, targetBlockId?: number) => {
    isDraggingOver.value = false;
    
    let exercise: Exercise | null = null;
    
    // Vérifier si c'est un drop custom (mobile)
    const customData = (event.dataTransfer as any)?.dropData;
    if (customData) {
        exercise = customData.exercise || JSON.parse(customData.json);
    } else if (event.dataTransfer) {
        const types = event.dataTransfer.types;
        if (!types.includes('application/json')) {
            return;
        }
        
        try {
            const exerciseData = event.dataTransfer.getData('application/json');
            if (exerciseData) {
                exercise = JSON.parse(exerciseData);
            } else {
                const exerciseId = event.dataTransfer.getData('text/plain');
                if (exerciseId) {
                    exercise = props.exercises.find(ex => ex.id === parseInt(exerciseId)) || null;
                }
            }
        } catch (error) {
            return;
        }
    }
    
    if (!exercise) return;
    
    try {
        if (targetBlockId) {
            addExerciseToSetBlock(exercise, targetBlockId);
        } else if (isSetMode.value) {
            createNewSetBlock(exercise);
        } else {
            addExerciseToSession(exercise);
        }
        // Fermer la bibliothèque après un drop réussi
        isLibraryOpen.value = false;
    } catch (error) {
    }
};

watch(() => sessionExercises.value.length, () => {
    isDraggingOver.value = false;
});

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

const isFormValid = computed(() => {
    const hasName = form.name.trim().length > 0;
    const hasExercises = sessionExercises.value.length > 0;
    return hasName && hasExercises;
});

const saveSession = () => {
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }

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
    
    form.exercises = sessionExercises.value.map(ex => {
        const setsArray = ex.sets ? (Array.isArray(ex.sets) ? [...ex.sets] : []) : [];
        const hasSets = setsArray.length > 0;
        let sets = undefined;
        
        if (hasSets) {
            sets = setsArray.map((set: any, idx) => {
                const formattedSet = {
                    set_number: set.set_number || 1,
                    repetitions: set.repetitions ?? null,
                    weight: set.weight ?? null,
                    rest_time: set.rest_time ?? null,
                    duration: set.duration ?? null,
                    use_duration: set.use_duration !== undefined ? set.use_duration : (ex.use_duration ?? false),
                    use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (ex.use_bodyweight ?? false),
                    order: set.order ?? idx
                } as ExerciseSet;
                return formattedSet;
            });
        }
        
        
        return {
            exercise_id: ex.exercise_id,
            sets: sets,
            repetitions: hasSets ? null : (ex.repetitions ?? null),
            weight: hasSets ? null : (ex.weight ?? null),
            rest_time: hasSets ? null : (ex.rest_time ?? null),
            duration: hasSets ? null : (ex.duration ?? null),
            description: ex.description ?? null,
            sets_count: ex.sets_count ?? null,
            order: ex.order,
            block_id: ex.block_id ?? null,
            block_type: ex.block_type ?? null,
            position_in_block: ex.position_in_block ?? null,
            custom_exercise_name: ex.custom_exercise_name ?? null,
            use_duration: ex.use_duration ?? false,
            use_bodyweight: ex.use_bodyweight ?? false,
        };
    });
    
    
    form.post('/sessions', {
        preserveScroll: false,
        onSuccess: () => {
            localStorage.removeItem(STORAGE_KEY);
            form.reset();
            sessionExercises.value = [];
        },
        onError: (errors) => {
            isSaving.value = false;
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

const openClearDialog = () => {
    isClearDialogOpen.value = true;
};

const confirmClearSession = () => {
    sessionExercises.value = [];
    form.reset();
    form.session_date = new Date().toISOString().split('T')[0];
    form.exercises = [];
    localStorage.removeItem(STORAGE_KEY);
    isClearDialogOpen.value = false;
};

const generatePDF = () => {
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }

    if (sessionExercises.value.length === 0) {
        notifyError('Veuillez ajouter au moins un exercice à la séance avant de générer le PDF.', 'Validation');
        return;
    }

    // Validation du nom
    if (!form.name.trim()) {
        notifyError('Le nom de la séance est obligatoire pour générer le PDF.', 'Validation');
        return;
    }

    // Vérifier si plusieurs clients sont sélectionnés
    const validCustomersCount = selectedCustomers.value.length;
    
    if (validCustomersCount > 1) {
        pendingPdfAction.value = 'download';
        selectedCustomerIdForPdf.value = null;
        isCustomerSelectDialogOpen.value = true;
        return;
    }

    // Si un seul client ou aucun, procéder directement
    const customerId = validCustomersCount === 1 ? selectedCustomers.value[0].id : null;
    executeGeneratePDF(customerId);
};

const executeGeneratePDF = (customerId: number | null = null) => {
    // Formater les exercices pour l'envoi au backend
    const exercisesData = sessionExercises.value.map(ex => ({
        exercise_id: ex.exercise_id,
        custom_exercise_name: ex.custom_exercise_name ?? null,
        sets: ex.sets && ex.sets.length > 0 ? ex.sets.map((set, idx) => ({
            set_number: set.set_number || idx + 1,
            repetitions: set.repetitions ?? null,
            weight: set.weight ?? null,
            rest_time: set.rest_time ?? null,
            duration: set.duration ?? null,
            use_duration: set.use_duration !== undefined ? set.use_duration : (ex.use_duration ?? false),
            use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (ex.use_bodyweight ?? false),
            order: set.order ?? idx
        })) : undefined,
        repetitions: ex.repetitions ?? null,
        weight: ex.weight ?? null,
        rest_time: ex.rest_time ?? null,
        duration: ex.duration ?? null,
        description: ex.description ?? null,
        sets_count: ex.sets_count ?? null,
        order: ex.order,
        use_duration: ex.use_duration ?? false,
        use_bodyweight: ex.use_bodyweight ?? false,
        block_id: ex.block_id ?? null,
        block_type: ex.block_type ?? null,
        position_in_block: ex.position_in_block ?? null,
    }));

    const requestData = {
        name: form.name,
        session_date: form.session_date,
        notes: form.notes || '',
        exercises: exercisesData,
        customer_id: customerId,
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
        a.download = `${form.name ? form.name.replace(/[^a-z0-9]/gi, '-').toLowerCase() : 'nouvelle-seance'}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        notifyError(error.message || 'Une erreur est survenue lors de la génération du PDF.', 'Erreur');
    });
};

const printPDF = () => {
    if (sessionExercises.value.length === 0) {
        notifyError('Veuillez ajouter au moins un exercice à la séance avant d\'imprimer.', 'Validation');
        return;
    }

    // Validation du nom
    if (!form.name.trim()) {
        notifyError('Le nom de la séance est obligatoire pour imprimer.', 'Validation');
        return;
    }

    // Vérifier si plusieurs clients sont sélectionnés
    const validCustomersCount = selectedCustomers.value.length;
    
    if (validCustomersCount > 1) {
        pendingPdfAction.value = 'print';
        selectedCustomerIdForPdf.value = null;
        isCustomerSelectDialogOpen.value = true;
        return;
    }

    // Si un seul client ou aucun, procéder directement
    const customerId = validCustomersCount === 1 ? selectedCustomers.value[0].id : null;
    executePrintPDF(customerId);
};

const executePrintPDF = (customerId: number | null = null) => {
    // Formater les exercices pour l'envoi au backend
    const exercisesData = sessionExercises.value.map(ex => ({
        exercise_id: ex.exercise_id,
        custom_exercise_name: ex.custom_exercise_name ?? null,
        sets: ex.sets && ex.sets.length > 0 ? ex.sets.map((set: any, idx) => ({
            set_number: set.set_number || idx + 1,
            repetitions: set.repetitions ?? null,
            weight: set.weight ?? null,
            use_duration: set.use_duration !== undefined ? set.use_duration : (ex.use_duration ?? false),
            use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (ex.use_bodyweight ?? false),
            rest_time: set.rest_time ?? null,
            duration: set.duration ?? null,
            order: set.order ?? idx
        } as ExerciseSet)) : undefined,
        repetitions: ex.repetitions ?? null,
        weight: ex.weight ?? null,
        rest_time: ex.rest_time ?? null,
        duration: ex.duration ?? null,
        description: ex.description ?? null,
        sets_count: ex.sets_count ?? null,
        order: ex.order,
        use_duration: ex.use_duration ?? false,
        use_bodyweight: ex.use_bodyweight ?? false,
        // Champs Super Set
        block_id: ex.block_id ?? null,
        block_type: ex.block_type ?? null,
        position_in_block: ex.position_in_block ?? null,
    }));

    const requestData = {
        name: form.name,
        session_date: form.session_date,
        notes: form.notes || '',
        exercises: exercisesData,
        customer_id: customerId,
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
        const printWindow = window.open(url, '_blank');
        
        if (printWindow) {
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.print();
                }, 250);
            };
        } else {
            window.open(url, '_blank');
            notifyError('Veuillez autoriser les popups pour cette fonctionnalité.', 'Information');
        }
        
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
        }, 1000);
    })
    .catch(error => {
        notifyError(error.message || 'Une erreur est survenue lors de l\'ouverture du PDF.', 'Erreur');
    });
};

const confirmCustomerSelectionForPdf = () => {
    if (!selectedCustomerIdForPdf.value) {
        notifyError('Veuillez sélectionner un client', 'Erreur');
        return;
    }
    
    if (pendingPdfAction.value === 'download') {
        executeGeneratePDF(selectedCustomerIdForPdf.value);
    } else if (pendingPdfAction.value === 'print') {
        executePrintPDF(selectedCustomerIdForPdf.value);
    }
    
    isCustomerSelectDialogOpen.value = false;
    pendingPdfAction.value = null;
    selectedCustomerIdForPdf.value = null;
};

watch(isCustomerSelectDialogOpen, (open) => {
    if (!open) {
        pendingPdfAction.value = null;
        selectedCustomerIdForPdf.value = null;
    }
});

const duplicateExercises = computed(() => {
    const exerciseIds = sessionExercises.value
        .filter(ex => ex && ex.exercise_id !== null && ex.exercise_id !== undefined)
        .map(ex => ex.exercise_id);
    const duplicates = exerciseIds.filter((id, index) => exerciseIds.indexOf(id) !== index);
    return [...new Set(duplicates)];
});

const hasDuplicateExercises = computed(() => duplicateExercises.value.length > 0);

watch(sessionExercises, () => {
    saveExercisesToStorage();
}, { deep: true });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nouvelle Séance">
            <meta name="description" content="Créez une nouvelle séance d'entraînement personnalisée. Ajoutez des exercices, définissez les paramètres et partagez avec vos clients." />
        </Head>

        <div class="mx-auto flex h-full w-full flex-1 flex-col gap-4 sm:gap-6 rounded-xl px-3 sm:px-6 py-3 sm:py-5">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
                <div class="flex flex-col gap-0.5">
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                        Nouvelle Séance
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">
                        Créez une nouvelle séance d'entraînement personnalisée
                    </p>
                </div>
                
                <div class="flex flex-row items-center gap-2">
                    <div class="flex items-center gap-1 text-xs sm:text-sm text-neutral-600 dark:text-neutral-400 border rounded-md px-2 py-1.5 sm:border-none sm:px-0 sm:py-0">
                        <Calendar class="h-3.5 w-3.5 sm:h-4 sm:w-4 flex-shrink-0" />
                        <input
                            v-model="form.session_date"
                            type="date"
                            class="border-none bg-transparent text-xs sm:text-sm focus:outline-none w-full sm:w-auto"
                        />
                    </div>
                    <div class="flex flex-row gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            class="sm:gap-2 text-xs sm:text-sm aspect-square sm:aspect-auto"
                            @click="openClearDialog"
                        >
                            <Trash2 class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            <span class="hidden sm:inline">Effacer</span>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="sm:gap-2 text-xs sm:text-sm aspect-square sm:aspect-auto"
                            :class="{ 'opacity-50 cursor-not-allowed': !isPro }"
                            @click="generatePDF"
                            :disabled="sessionExercises.length === 0 || !isPro"
                        >
                            <FileText class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            <span class="hidden sm:inline">PDF</span>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="sm:gap-2 text-xs sm:text-sm aspect-square sm:aspect-auto"
                            @click="printPDF"
                            :disabled="sessionExercises.length === 0"
                        >
                            <Printer class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            <span class="hidden sm:inline">Imprimer</span>
                        </Button>
                        <Button
                            size="sm"
                            class="sm:gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm aspect-square sm:aspect-auto"
                            @click="saveSession"
                            :disabled="isSaving || !isFormValid"
                        >
                            <Save class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            <span class="hidden sm:inline">{{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="flex-1 flex flex-col 2xl:flex-row overflow-hidden gap-4">
                    
                    <!-- Panneau gauche -->
                    <div class="w-full 2xl:w-3/5 overflow-y-auto rounded-xl min-h-0">
                        <div class="space-y-6">
                        <Card class="shadow-md py-4">
                            <CardContent class="space-y-4">
                                <!-- Nom de la séance et Clients sur une ligne pour desktop/tablette -->
                                <div class="flex flex-col md:flex-row md:gap-4 gap-4">
                                    <!-- Nom de la séance -->
                                    <div class="space-y-2 flex-1">
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
                                    <div class="space-y-2 flex-1">
                                        <Label>Clients (optionnel)</Label>
                                        <div class="space-y-2">
                                            <!-- Bouton pour ouvrir la modal -->
                                            <Button
                                                type="button"
                                                variant="outline"
                                                class="w-full justify-start"
                                                :disabled="!isPro"
                                                :class="{ 'opacity-50 cursor-not-allowed': !isPro }"
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
                                                        v-if="isPro"
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
                                </div>

                                <!-- Notes -->
                                <div class="space-y-2">
                                    <Label>Notes (optionnel)</Label>
                                    <Textarea
                                        v-model="form.notes"
                                        placeholder="Ajouter des notes sur cette séance..."
                                        :rows="2"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Liste des exercices de la séance -->
                        <Card class="shadow-md py-3">
                            <CardContent
                                data-drop-zone
                                @dragover.prevent="handleDragOverMain"
                                @dragleave="handleDragLeaveMain"
                                @drop.prevent="handleDropMain"
                                class="min-h-[200px] transition-all duration-200 ease-out relative pt-2"
                            >
                                <!-- Message d'avertissement pour les doublons -->
                                <Alert
                                    v-if="hasDuplicateExercises"
                                    variant="destructive"
                                    class="py-2 mb-4"
                                >
                                    <AlertTriangle class="h-4 w-4" />
                                    <AlertDescription class="text-sm">
                                        Attention : {{ duplicateExercises.length }} exercice(s) {{ duplicateExercises.length > 1 ? 'sont' : 'est' }} présent{{ duplicateExercises.length > 1 ? 's' : '' }} plusieurs fois dans cette séance.
                                    </AlertDescription>
                                </Alert>
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
                                        <p class="text-sm">Glissez des exercices depuis la bibliothèque</p>
                                    </div>
                                    <div v-else>
                                        <!-- Afficher les exercices standard et les blocs Super Set mélangés avec drag and drop -->
                                        <VueDraggable
                                            :model-value="orderedItems"
                                            @update:model-value="onDragUpdate"
                                            :animation="150"
                                            handle=".handle"
                                            class="flex flex-col gap-4"
                                            ghost-class="fc-ghost"
                                            drag-class="fc-drag"
                                            chosen-class="fc-chosen"
                                            :item-key="(item: any) => item.key"
                                        >
                                            <template v-for="(item, itemIndex) in orderedItems" :key="item.key">
                                            <!-- Bloc Super Set -->
                                            <SessionBlockSet
                                                v-if="item.type === 'set'"
                                                :key="`block-${item.block!.id}`"
                                                :block="item.block!"
                                                :block-index="item.displayIndex"
                                                :display-index="item.displayIndex"
                                                :draggable="true"
                                                :total-count="orderedItems.length"
                                                @drop="(event: DragEvent, blockId: number) => handleDropFromLibrary(event, blockId)"
                                                @remove-exercise="(exerciseId: number) => handleRemoveExerciseFromBlock(item, exerciseId)"
                                                @update-exercise="(exerciseIdOrIndex: number, updates: Partial<SessionExercise>) => handleUpdateExerciseFromBlock(item, exerciseIdOrIndex, updates)"
                                                @update-block-description="(description: string) => handleUpdateBlockDescription(item.block!.id, description)"
                                                @convert-to-standard="convertBlockToStandard(item.block!)"
                                                @remove-block="handleRemoveBlock(item)"
                                            />
                                            
                                            <!-- Exercice standard -->
                                            <SessionExerciseItem
                                                v-else-if="item.type === 'standard'"
                                                :session-exercise="item.exercise!"
                                                :index="item.displayIndex"
                                                :display-index="item.displayIndex"
                                                :draggable="true"
                                                :total-count="orderedItems.length"
                                                @update="(updates: Partial<SessionExercise>) => handleExerciseUpdate(item.exercise, updates)"
                                                @remove="handleRemoveExercise(item)"
                                                @convert-to-set="convertExerciseToSet(item.exercise!)"
                                            />
                                            </template>
                                        </VueDraggable>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                    <!-- Panneau droit -->
                    <div class="hidden 2xl:block w-full 2xl:w-2/5 overflow-y-auto rounded-xl min-h-0">
                        <div>
                            <ExerciseLibrary
                                :categories="categories"
                                :search-term="localSearchTerm"
                                :selected-category-id="selectedCategoryId"
                                :view-mode="viewMode"
                                :show-only-mine="showOnlyMine"
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

        <!-- Bouton flottant pour ouvrir/fermer la bibliothèque sur mobile -->
        <Button
            ref="libraryToggleButton"
            class="fixed bottom-6 right-6 z-[60] pointer-events-auto h-14 w-14 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg 2xl:hidden"
            @click.stop="isLibraryOpen = !isLibraryOpen"
        >
            <X v-if="isLibraryOpen" class="h-6 w-6" />
            <Library v-else class="h-6 w-6" />
        </Button>

        <!-- Sheet pour la bibliothèque sur mobile -->
        <Sheet v-model:open="isLibraryOpen">
            <SheetContent 
                side="right" 
                class="w-full sm:max-w-lg 2xl:hidden p-0"
                @pointer-down-outside="(event: any) => {
                    const target = event.detail.originalEvent.target as HTMLElement;
                    if (libraryToggleButton.value && (target === libraryToggleButton.value || libraryToggleButton.value.contains(target))) {
                        event.preventDefault();
                    }
                }"
            >
                <div class="h-full">
                    <ExerciseLibrary
                        :categories="categories"
                        :search-term="localSearchTerm"
                        :selected-category-id="selectedCategoryId"
                        :view-mode="viewMode"
                        :show-only-mine="showOnlyMine"
                        @search="(term: string) => { localSearchTerm = term; }"
                        @category-change="(id: number | null) => { selectedCategoryId = id; }"
                        @view-mode-change="(mode: 'grid-2' | 'grid-4' | 'grid-6' | 'list') => viewMode = mode"
                        @filter-change="(showOnly: boolean) => { showOnlyMine = showOnly; }"
                        @add-exercise="(exercise: Exercise) => { addExerciseToSession(exercise); if (!showSuperSetModal.value) { isLibraryOpen = false; } }"
                    />
                </div>
            </SheetContent>
        </Sheet>

        <!-- Modal de sélection du super set sur mobile -->
        <Dialog v-model:open="showSuperSetModal">
            <DialogContent class="sm:max-w-[480px]">
                <DialogHeader>
                    <DialogTitle class="text-xl font-semibold">Ajouter l'exercice</DialogTitle>
                    <DialogDescription>
                        {{ pendingExercise?.title }}
                    </DialogDescription>
                </DialogHeader>
                
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <p class="text-sm text-neutral-600 dark:text-neutral-400">
                            Où souhaitez-vous ajouter cet exercice ?
                        </p>
                        
                        <!-- Option : Ajouter normalement -->
                        <Button
                            variant="outline"
                            class="w-full justify-start"
                            @click="addToSessionNormal"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            Ajouter à la séance (normal)
                        </Button>
                        
                        <!-- Liste des super sets disponibles -->
                        <div v-if="availableSuperSets.length > 0" class="space-y-2">
                            <p class="text-sm font-medium text-neutral-700 dark:text-neutral-300 mt-4">
                                Ou ajouter dans un Super Set :
                            </p>
                            <div
                                v-for="block in availableSuperSets"
                                :key="block.id"
                                class="border rounded-lg p-3 hover:bg-neutral-50 dark:hover:bg-neutral-800 cursor-pointer transition-colors"
                                @click="confirmAddToSuperSet(block.id)"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium">Super Set #{{ block.id }}</p>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ block.exercises.length }} exercice(s)
                                        </p>
                                    </div>
                                    <Plus class="h-4 w-4 text-blue-600" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showSuperSetModal = false; pendingExercise = null"
                    >
                        Annuler
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal de confirmation d'effacement -->
        <Dialog v-model:open="isClearDialogOpen">
            <DialogContent class="sm:max-w-[480px]">
                <DialogHeader>
                    <DialogTitle class="text-xl font-semibold">Effacer la séance</DialogTitle>
                    <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        Cette action est irréversible. Toutes les données non sauvegardées seront perdues.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Êtes-vous sûr de vouloir effacer cette séance ? Tous les exercices ajoutés seront supprimés.
                    </p>
                </div>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="isClearDialogOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button
                        variant="destructive"
                        class="flex items-center gap-2"
                        @click="confirmClearSession"
                    >
                        <Trash2 class="h-4 w-4" />
                        Effacer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <UpgradeModal
            v-model:open="isUpgradeModalOpen"
            feature="La sélection de clients et l'enregistrement des séances sont réservés aux abonnés Pro. Passez à Pro pour débloquer toutes les fonctionnalités."
        />

        <!-- Modal de sélection du client pour PDF/impression -->
        <Dialog v-model:open="isCustomerSelectDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Sélectionner un client</DialogTitle>
                    <DialogDescription>
                        Cette séance est associée à plusieurs clients. Veuillez sélectionner le client pour lequel générer le PDF{{ pendingPdfAction === 'print' ? ' et imprimer' : '' }}.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="customer-select-pdf-create">Client</Label>
                        <select
                            id="customer-select-pdf-create"
                            v-model="selectedCustomerIdForPdf"
                            class="h-10 w-full rounded-2xl border border-slate-200 bg-white px-3 text-sm text-slate-700 transition focus:border-blue-500 focus:outline-none focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                        >
                            <option :value="null">Sélectionner un client</option>
                            <option
                                v-for="customer in selectedCustomers"
                                :key="customer.id"
                                :value="customer.id"
                            >
                                {{ customer.full_name || `${customer.first_name} ${customer.last_name}` }}
                            </option>
                        </select>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="isCustomerSelectDialogOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button
                        @click="confirmCustomerSelectionForPdf"
                        :disabled="!selectedCustomerIdForPdf"
                    >
                        {{ pendingPdfAction === 'print' ? 'Imprimer' : 'Télécharger' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Layout Editor -->
        <div v-if="showLayoutEditor" class="fixed inset-0 z-50 bg-white dark:bg-neutral-900 overflow-hidden">
            <SessionLayoutEditor
                :session-id="savedSessionId || undefined"
                :exercises="exercises"
                :categories="categories"
                :customers="customers"
                :session-name="form.name"
                @close="showLayoutEditor = false"
                @saved="(id: number) => { savedSessionId = id; }"
            />
        </div>
    </AppLayout>
</template>
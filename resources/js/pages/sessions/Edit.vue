<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, nextTick, triggerRef } from 'vue';
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
    ArrowLeft,
    Layout
} from 'lucide-vue-next';
import type { EditSessionProps, Exercise, SessionExercise, Customer, Category, ExerciseSet, SessionBlock } from './types';
import SessionExerciseItem from './SessionExerciseItem.vue';
import SessionBlockSet from './SessionBlockSet.vue';
import ExerciseLibrary from './ExerciseLibrary.vue';
import SessionLayoutEditor from './SessionLayoutEditor.vue';
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

const shownFlashMessages = ref(new Set<string>());

watch(() => (page.props as any).flash, (flash) => {
    if (!flash) return;
    
    const successKey = flash.success ? `success-${flash.success}` : null;
    const errorKey = flash.error ? `error-${flash.error}` : null;
    
    if (successKey && !shownFlashMessages.value.has(successKey)) {
        shownFlashMessages.value.add(successKey);
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

const form = useForm({
    name: props.session?.name || '',
    customer_ids: (props.session?.customers?.map((c: Customer) => c.id) || []) as number[],
    session_date: props.session?.session_date ? new Date(props.session.session_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    notes: props.session?.notes || '',
    exercises: [] as SessionExercise[],
});

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
            }
        }
        if (session.notes !== undefined) {
            form.notes = session.notes || '';
        }
    }
}, { immediate: true, deep: true });

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
const getDefaultViewMode = (): 'grid-2' | 'grid-4' | 'grid-6' | 'list' => {
    if (typeof window !== 'undefined') {
        if (window.innerWidth < 640) {
            return 'grid-6';
        }
    }
    return 'grid-4';
};

const viewMode = ref<'grid-2' | 'grid-4' | 'grid-6' | 'list'>(getDefaultViewMode());

onMounted(() => {
    const handleResize = () => {
        if (window.innerWidth < 640 && viewMode.value !== 'grid-6') {
            viewMode.value = 'grid-6';
        } else if (window.innerWidth >= 640 && viewMode.value === 'grid-6' && window.innerWidth < 1024) {
            viewMode.value = 'grid-6';
        }
    };
    
    window.addEventListener('resize', handleResize);
    handleResize();
});
const showOnlyMine = ref(false);
const isSaving = ref(false);
const isLibraryOpen = ref(false);
// Mode d'édition: 'standard' ou 'libre'
// Initialiser le mode en fonction de l'URL et des props
const getInitialEditMode = (): 'standard' | 'libre' => {
    if (typeof window === 'undefined') return 'standard';
    const urlParams = new URLSearchParams(window.location.search);
    const shouldOpenEditor = urlParams.get('editor') === 'true';
    return (shouldOpenEditor || props.session.has_custom_layout) ? 'libre' : 'standard';
};
const editMode = ref<'standard' | 'libre'>(getInitialEditMode());
const sessionLayout = ref<any>(null);
const isLayoutLoading = ref(false);

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

const loadSessionExercises = () => {
    const exercisesData = props.session?.sessionExercises || props.session?.exercises || [];
    
    if (exercisesData.length > 0) {
        sessionExercises.value = exercisesData.map((se: any, index: number) => {
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
                    order: se.pivot.order ?? index,
                    custom_exercise_name: null,
                    use_duration: false,
                    use_bodyweight: false,
                };
            }
            
            let exercise = se.exercise;
            if (!exercise || !exercise.image_url) {
                exercise = props.exercises.find(e => e.id === se.exercise_id);
            }
            
            if (!exercise) {
                return null;
            }
            
            let sets: ExerciseSet[] = [];
            if (se.sets && se.sets.length > 0) {
                sets = se.sets.map((set: any) => ({
                    set_number: set.set_number || 1,
                    repetitions: set.repetitions ?? null,
                    weight: set.weight ?? null,
                    rest_time: set.rest_time ?? null,
                    duration: set.duration ?? null,
                    use_duration: set.use_duration !== undefined ? set.use_duration : (se.use_duration ?? false),
                    use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (se.use_bodyweight ?? false),
                    order: set.order ?? 0
                }));
            } else if (se.repetitions || se.duration || se.rest_time || se.weight) {
                sets = [{
                    set_number: 1,
                    repetitions: se.repetitions ?? null,
                    weight: se.weight ?? null,
                    rest_time: se.rest_time ?? null,
                    duration: se.duration ?? null,
                    use_duration: se.use_duration ?? false,
                    use_bodyweight: se.use_bodyweight ?? false,
                    order: 0
                }];
            } else {
                sets = [{
                    set_number: 1,
                    repetitions: null,
                    weight: null,
                    rest_time: null,
                    duration: null,
                    use_duration: se.use_duration ?? false,
                    use_bodyweight: se.use_bodyweight ?? false,
                    order: 0
                }];
            }
            
            const loadedExercise = {
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
                block_id: se.block_id ?? null,
                block_type: se.block_type ?? null,
                position_in_block: se.position_in_block ?? null,
                custom_exercise_name: se.custom_exercise_name ?? null,
                use_duration: se.use_duration !== null && se.use_duration !== undefined ? Boolean(se.use_duration) : false,
                use_bodyweight: se.use_bodyweight !== null && se.use_bodyweight !== undefined ? Boolean(se.use_bodyweight) : false,
            };
            
            
            return loadedExercise;
        }).filter((ex: SessionExercise | null) => ex !== null) as SessionExercise[];
        
        const maxBlockId = sessionExercises.value
            .filter(ex => ex.block_id !== null)
            .reduce((max, ex) => Math.max(max, ex.block_id || 0), 0);
        if (maxBlockId > 0) {
            nextBlockId = maxBlockId + 1;
        }
        
        form.exercises = sessionExercises.value;
    }
};

onMounted(async () => {
    loadSessionExercises();
    
    // Vérifier à nouveau le mode au cas où l'URL aurait changé
    const urlParams = new URLSearchParams(window.location.search);
    const shouldOpenEditor = urlParams.get('editor') === 'true';
    if (shouldOpenEditor || props.session.has_custom_layout) {
        editMode.value = 'libre';
        // Charger la mise en page avant d'afficher l'éditeur
        await loadLayout();
        await nextTick();
    }
});

watch(() => props.session?.sessionExercises, () => {
    if (!sessionExercises.value || !Array.isArray(sessionExercises.value)) {
        sessionExercises.value = [];
    }
    loadSessionExercises();
}, { deep: true, immediate: true });

watch(() => props.session, () => {
    nextTick(() => {
        loadSessionExercises();
    });
}, { deep: true, immediate: true });

const addExerciseToSession = (exercise: Exercise) => {
    if (!exercise || !exercise.id) {
        return;
    }
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
        sets_count: null,
        rest_time: null,
        duration: null,
        description: '',
        order: sessionExercises.value.length,
        block_id: null,
        block_type: null,
        position_in_block: null,
        custom_exercise_name: null,
        use_duration: false,
        use_bodyweight: false,
    };
    sessionExercises.value.push(sessionExercise);
    sessionExercises.value = [...sessionExercises.value];
    form.exercises = [...sessionExercises.value];
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
};

// Ajouter un exercice à un bloc Super Set existant
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
        custom_exercise_name: null,
        use_duration: false,
        use_bodyweight: false,
        order: sessionExercises.value.length,
    };
    
    sessionExercises.value.push(sessionExercise);
    form.exercises = [...sessionExercises.value];
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

const handleUpdateExerciseFromBlock = (item: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }, exerciseIdOrIndex: number, updates: Partial<SessionExercise>) => {
    if (!sessionExercises.value || !Array.isArray(sessionExercises.value)) {
        return;
    }
    
    const indexById = sessionExercises.value.findIndex((e: SessionExercise) => e.id === exerciseIdOrIndex);
    if (indexById !== -1) {
        updateSessionExercise(indexById, updates);
        return;
    }
    
    if (!item.block) {
        return;
    }
    
    if (exerciseIdOrIndex < 0 || exerciseIdOrIndex >= item.block.exercises.length) {
        return;
    }
    
    const exerciseToUpdate = item.block.exercises[exerciseIdOrIndex];
    if (!exerciseToUpdate) {
        return;
    }
    
    let index = -1;
    if (exerciseToUpdate.id) {
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
    if (!e.dataTransfer) return;
    const target = e.target as HTMLElement;
    const supersetDropZone = target.closest('.superset-block [class*="border-dashed"]');
    if (supersetDropZone) return;
    if (e.dataTransfer.types.includes('application/json')) {
        handleDropFromLibrary(e);
    }
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

const handleRemoveExerciseFromBlock = (item: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }, index: number) => {
    if (!item?.block?.id) {
        return;
    }
    const blockExercises = sessionExercises.value.filter(
        (ex: SessionExercise) => ex?.block_id === item.block!.id && ex?.block_type === 'set'
    );
    if (!blockExercises[index]?.id) {
        return;
    }
    const exerciseIndex = sessionExercises.value.findIndex(
        (ex: SessionExercise) => ex?.id === blockExercises[index].id
    );
    if (exerciseIndex !== -1) {
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
};

const handleExerciseUpdate = (exerciseId: number | undefined, updates: Partial<SessionExercise>) => {
    if (!sessionExercises) {
        return;
    }
    
    if (!sessionExercises.value) {
        sessionExercises.value = [];
    }
    
    if (!exerciseId) {
        return;
    }
    
    const index = sessionExercises.value.findIndex((e: SessionExercise) => e.id === exerciseId);
    
    if (index !== -1) {
        updateSessionExercise(index, updates);
    }
};

const updateSessionExercise = (index: number, updates: Partial<SessionExercise>) => {
    const currentExercise = sessionExercises.value[index];
    
    if (updates.sets) {
        const setsArray = Array.isArray(updates.sets) 
            ? updates.sets.map(set => ({
                set_number: set.set_number ?? 1,
                repetitions: set.repetitions ?? null,
                weight: set.weight ?? null,
                rest_time: set.rest_time ?? null,
                duration: set.duration ?? null,
                use_duration: set.use_duration !== undefined ? set.use_duration : (currentExercise.use_duration ?? false),
                use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (currentExercise.use_bodyweight ?? false),
                order: set.order ?? 0
            }))
            : [];
        
        const { sets: _, ...updatesWithoutSets } = updates;
        
        const exercise = sessionExercises.value[index];
        for (const key in updatesWithoutSets) {
            (exercise as any)[key] = (updatesWithoutSets as any)[key];
        }
        exercise.sets = setsArray;
        sessionExercises.value = [...sessionExercises.value];
        triggerRef(sessionExercises);
        exercisesKey.value++;
    } else {
        const exercise = sessionExercises.value[index];
        for (const key in updates) {
            (exercise as any)[key] = (updates as any)[key];
        }
        sessionExercises.value = [...sessionExercises.value];
        
        triggerRef(sessionExercises);
        exercisesKey.value++;
    }
    
    
    // Ne pas mettre à jour form.exercises ici, il sera formaté lors de la sauvegarde
    // form.exercises sera mis à jour lors de la sauvegarde avec le bon format
};

// Réorganiser les exercices (utilisé par les boutons haut/bas et après drag)
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
};

const reorderItems = (fromItem: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }, toItem: { type: 'standard' | 'set', exercise?: SessionExercise, block?: SessionBlock }) => {
    if (fromItem.type === 'standard' && toItem.type === 'standard') {
        const fromIndex = sessionExercises.value.findIndex(e => e.id === fromItem.exercise!.id);
        const toIndex = sessionExercises.value.findIndex(e => e.id === toItem.exercise!.id);
        if (fromIndex !== -1 && toIndex !== -1) {
            reorderExercises(fromIndex, toIndex);
        }
    } else if (fromItem.type === 'set' && toItem.type === 'set') {
        const fromBlockExercises = sessionExercises.value.filter(
            (ex: SessionExercise) => ex.block_id === fromItem.block!.id && ex.block_type === 'set'
        );
        const toBlockExercises = sessionExercises.value.filter(
            (ex: SessionExercise) => ex.block_id === toItem.block!.id && ex.block_type === 'set'
        );
        
        if (fromBlockExercises.length > 0 && toBlockExercises.length > 0) {
            const fromOrder = fromBlockExercises[0].order;
            const toOrder = toBlockExercises[0].order;
            
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
            
            sessionExercises.value.sort((a, b) => a.order - b.order);
            form.exercises = [...sessionExercises.value];
        }
    } else {
        const fromOrder = fromItem.type === 'standard' 
            ? fromItem.exercise!.order 
            : sessionExercises.value.find(e => e.block_id === fromItem.block!.id && e.block_type === 'set')?.order || 0;
        const toOrder = toItem.type === 'standard' 
            ? toItem.exercise!.order 
            : sessionExercises.value.find(e => e.block_id === toItem.block!.id && e.block_type === 'set')?.order || 0;
        
        if (fromItem.type === 'standard') {
            // Déplacer un exercice standard
            const fromIndex = sessionExercises.value.findIndex(e => e.id === fromItem.exercise!.id);
            if (fromIndex !== -1) {
                sessionExercises.value[fromIndex].order = toOrder;
            }
        } else {
            // Déplacer un bloc Super Set
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
            const toIndex = sessionExercises.value.findIndex(e => e.id === toItem.exercise!.id);
            if (toIndex !== -1) {
                sessionExercises.value[toIndex].order = fromOrder;
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
        
        sessionExercises.value.sort((a, b) => a.order - b.order);
        form.exercises = [...sessionExercises.value];
    }
};

const handleDropFromLibrary = (event: DragEvent, targetBlockId?: number) => {
    isDraggingOver.value = false;
    if (!event.dataTransfer) return;
    
    const types = event.dataTransfer.types;
    if (!types.includes('application/json')) {
        return;
    }
    
    try {
        const exerciseData = event.dataTransfer.getData('application/json');
        if (exerciseData) {
            const exercise: Exercise = JSON.parse(exerciseData);
            
            if (targetBlockId) {
                addExerciseToSetBlock(exercise, targetBlockId);
            } else {
                addExerciseToSession(exercise);
            }
        } else {
            const exerciseId = event.dataTransfer.getData('text/plain');
            if (exerciseId) {
                const exercise = props.exercises.find(ex => ex.id === parseInt(exerciseId));
                if (exercise) {
                    if (targetBlockId) {
                        addExerciseToSetBlock(exercise, targetBlockId);
                    } else {
                        addExerciseToSession(exercise);
                    }
                }
            }
        }
    } catch (error) {
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
    
    form.exercises = sessionExercises.value.map(ex => {
        const setsArray = ex.sets ? (Array.isArray(ex.sets) ? [...ex.sets] : []) : [];
        const hasSets = setsArray.length > 0;
        let sets = undefined;
        
        if (hasSets) {
            sets = setsArray.map((set, idx) => {
                const formattedSet = {
                    set_number: set.set_number || idx + 1,
                    repetitions: set.repetitions ?? null,
                    weight: set.weight ?? null,
                    rest_time: set.rest_time ?? null,
                    duration: set.duration ?? null,
                    use_duration: set.use_duration !== undefined ? set.use_duration : (ex.use_duration ?? false),
                    use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (ex.use_bodyweight ?? false),
                    order: set.order ?? idx
                };
                return formattedSet;
            });
        }
        
        
        const formattedExercise = {
            exercise_id: ex.exercise_id,
            sets: sets,
            // Envoyer les valeurs directes seulement si pas de sets
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
        
        
        return formattedExercise;
    });
    
    
    form.put(`/sessions/${props.session.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            router.reload({
                only: ['session', 'exercises'],
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
            use_duration: set.use_duration !== undefined ? set.use_duration : (ex.use_duration ?? false),
            use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (ex.use_bodyweight ?? false),
            order: set.order ?? idx
        })) : undefined,
        repetitions: ex.repetitions ?? null,
        weight: ex.weight ?? null,
        rest_time: ex.rest_time ?? null,
        duration: ex.duration ?? null,
        description: ex.description ?? null,
        order: ex.order,
        use_duration: ex.use_duration ?? false,
        use_bodyweight: ex.use_bodyweight ?? false,
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
            use_duration: set.use_duration !== undefined ? set.use_duration : (ex.use_duration ?? false),
            use_bodyweight: set.use_bodyweight !== undefined ? set.use_bodyweight : (ex.use_bodyweight ?? false),
            order: set.order ?? idx
        })) : undefined,
        repetitions: ex.repetitions ?? null,
        weight: ex.weight ?? null,
        rest_time: ex.rest_time ?? null,
        duration: ex.duration ?? null,
        description: ex.description ?? null,
        order: ex.order,
        use_duration: ex.use_duration ?? false,
        use_bodyweight: ex.use_bodyweight ?? false,
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
        
        // Créer une URL blob et ouvrir dans un nouvel onglet
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

const duplicateExercises = computed(() => {
    const exerciseIds = sessionExercises.value
        .filter(ex => ex && ex.exercise_id !== null && ex.exercise_id !== undefined)
        .map(ex => ex.exercise_id);
    const duplicates = exerciseIds.filter((id, index) => exerciseIds.indexOf(id) !== index);
    return [...new Set(duplicates)];
});

const hasDuplicateExercises = computed(() => duplicateExercises.value.length > 0);

// Load layout
const loadLayout = async () => {
    isLayoutLoading.value = true;
    try {
        const response = await fetch(`/sessions/${props.session.id}/layout`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        if (response.ok) {
            const data = await response.json();
            if (data.layout) {
                sessionLayout.value = {
                    layout_data: data.layout.layout_data || [],
                    canvas_width: data.layout.canvas_width || 800,
                    canvas_height: data.layout.canvas_height || 1000,
                };
                console.log('Layout loaded successfully:', sessionLayout.value);
            } else {
                sessionLayout.value = null;
                console.log('No layout found for this session');
            }
        } else {
            sessionLayout.value = null;
        }
    } catch (error) {
        console.error('Error loading layout:', error);
        sessionLayout.value = null;
    } finally {
        isLayoutLoading.value = false;
    }
};

// Switch edit mode
const switchMode = async (mode: 'standard' | 'libre') => {
    editMode.value = mode;
    if (mode === 'libre') {
        await loadLayout();
        await nextTick();
    }
};

// Handle layout saved
const handleLayoutSaved = async (sessionId: number) => {
    // La notification est déjà affichée par l'éditeur, pas besoin de la dupliquer
    // Recharger la mise en page pour pouvoir l'éditer à nouveau
    await loadLayout();
    // Recharger la session pour mettre à jour has_custom_layout
    router.reload({
        only: ['session'],
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Modifier: ${session.name || 'Séance'}`" />

        <!-- Mode Standard -->
        <div v-if="editMode === 'standard'" class="mx-auto flex h-full w-full flex-1 flex-col gap-4 sm:gap-6 rounded-xl px-3 sm:px-6 py-3 sm:py-5">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
                <div class="flex flex-col gap-0.5">
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                        Modifier la séance
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">
                        Modifiez votre séance d'entraînement personnalisée
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
                            @click="router.visit(`/sessions/${session.id}`)"
                        >
                            <ArrowLeft class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            <span class="hidden sm:inline">Annuler</span>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="sm:gap-2 text-xs sm:text-sm aspect-square sm:aspect-auto"
                            @click="generatePDF"
                            :disabled="sessionExercises.length === 0"
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
                    <div class="flex-1 flex flex-col xl:flex-row overflow-hidden gap-4">
                        <!-- Panneau gauche -->
                        <div class="w-full xl:w-3/5 overflow-y-auto rounded-xl min-h-0">
                            <div class="space-y-6">
                        <Card class="shadow-md">
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
                                        :rows="2"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Liste des exercices de la séance -->
                        <Card class="shadow-md">
                            <CardContent
                                @dragover.prevent="handleDragOverMain"
                                @dragleave.prevent="handleDragLeaveMain"
                                @drop.prevent="handleDropMain"
                                class="min-h-[200px] transition-all duration-200 ease-out relative pt-6"
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
                                                :key="`block-${item.block!.id}-${exercisesKey}`"
                                                :block="item.block!"
                                                :block-index="item.displayIndex"
                                                :display-index="item.displayIndex"
                                                :draggable="true"
                                                :total-count="orderedItems.length"
                                                @drop="(event: DragEvent, blockId: number) => handleDropFromLibrary(event, blockId)"
                                                @remove-exercise="(index: number) => handleRemoveExerciseFromBlock(item, index)"
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
                                                @update="(updates: Partial<SessionExercise>) => handleExerciseUpdate(item.exercise?.id, updates)"
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
                    <div class="hidden xl:block w-full xl:w-2/5 overflow-y-auto rounded-xl min-h-0">
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

        <!-- Mode Libre -->
        <div v-if="editMode === 'libre'" class="fixed inset-0 z-50 bg-white dark:bg-neutral-900">
            <div v-if="isLayoutLoading" class="flex items-center justify-center h-full">
                <div class="text-center">
                    <p class="text-lg text-neutral-600 dark:text-neutral-400">Chargement de la mise en page...</p>
                </div>
            </div>
            <SessionLayoutEditor
                v-else
                :session-id="session.id"
                :exercises="exercises"
                :initial-layout="sessionLayout"
                :customers="customers"
                :session-name="session.name"
                :session-customers="session.customers"
                @close="() => { switchMode('standard'); loadLayout(); }"
                @saved="handleLayoutSaved"
            />
        </div>
    </AppLayout>
</template>


<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft, Edit, Trash2, Download, Calendar, Users, FileText } from 'lucide-vue-next';
import { computed, ref, watch, nextTick } from 'vue';
import type { BreadcrumbItem } from '@/types';
import SessionDeleteDialog from './SessionDeleteDialog.vue';
import type { Session } from './types';
import { useNotifications } from '@/composables/useNotifications';

interface Props {
    session: Session;
}

const props = defineProps<Props>();

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();

// Écouter les messages flash et les convertir en notifications
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
        }, 4500);
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
        }, 6500);
    }
}, { immediate: true });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes Séances',
        href: '/sessions',
    },
    {
        title: props.session.name || 'Séance',
        href: `/sessions/${props.session.id}`,
    },
];

const isDeleteDialogOpen = ref(false);
const isDeleteProcessing = ref(false);

const formatDate = (value?: string | null) => {
    if (!value) {
        return '—';
    }
    const date = new Date(value);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatShortDate = (value?: string | null) => {
    if (!value) {
        return '—';
    }
    const date = new Date(value);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const handleEdit = () => {
    router.visit(`/sessions/${props.session.id}/edit`);
};

const handleDelete = () => {
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (isDeleteProcessing.value) {
        return;
    }

    isDeleteProcessing.value = true;

    router.delete(`/sessions/${props.session.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/sessions');
        },
        onFinish: () => {
            isDeleteProcessing.value = false;
        },
    });
};

const handleDownloadPdf = () => {
    window.open(`/sessions/${props.session.id}/pdf`, '_blank');
};

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        isDeleteProcessing.value = false;
    }
});

// Grouper les exercices par blocs (standard et Super Set)
const groupExercisesIntoBlocks = () => {
    if (!props.session.sessionExercises) {
        return { standard: [], set: [] };
    }
    
    const blocksMap = new Map<number, typeof props.session.sessionExercises>();
    const standardExercises: typeof props.session.sessionExercises = [];
    
    props.session.sessionExercises.forEach(ex => {
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
    
    // Convertir les blocs Super Set en objets
    const setBlocks = Array.from(blocksMap.entries())
        .map(([blockId, exercises]) => ({
            id: blockId,
            type: 'set' as const,
            exercises: exercises.sort((a, b) => 
                (a.position_in_block || 0) - (b.position_in_block || 0)
            ),
            order: exercises[0]?.order || 0,
        }))
        .sort((a, b) => a.order - b.order);
    
    return {
        standard: standardExercises.sort((a, b) => (a.order ?? 0) - (b.order ?? 0)),
        set: setBlocks,
    };
};

// Items ordonnés (standard et Super Set mélangés) - computed pour réactivité
const orderedItems = computed(() => {
    const blocks = groupExercisesIntoBlocks();
    const items: Array<{ 
        type: 'standard' | 'set', 
        exercise?: typeof props.session.sessionExercises[0], 
        block?: { id: number; type: 'set'; exercises: typeof props.session.sessionExercises; order: number },
        key: string, 
        order: number, 
        displayIndex: number 
    }> = [];
    
    // Combiner et trier tous les items
    const allItems: Array<{ 
        type: 'standard' | 'set', 
        exercise?: typeof props.session.sessionExercises[0], 
        block?: { id: number; type: 'set'; exercises: typeof props.session.sessionExercises; order: number },
        order: number 
    }> = [];
    
    // Ajouter les exercices standard
    blocks.standard.forEach(ex => {
        allItems.push({
            type: 'standard',
            exercise: ex,
            order: ex.order ?? 0
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

// Pour compatibilité avec l'ancien code
const sortedExercises = computed(() => {
    if (!props.session.sessionExercises) {
        return [];
    }
    return [...props.session.sessionExercises].sort((a, b) => {
        const orderA = a.order ?? 0;
        const orderB = b.order ?? 0;
        return orderA - orderB;
    });
});

// Fonctions helper pour formater les données comme dans le PDF
const extractRestSeconds = (restTime: string | null | undefined): string | number => {
    if (!restTime || restTime === '-') return '-';
    // Extraire le nombre de secondes depuis le format "X seconde(s)" ou "X secondes"
    const secondsMatch = restTime.match(/(\d+)\s*seconde/i);
    if (secondsMatch) {
        return parseInt(secondsMatch[1]);
    }
    // Si c'est juste un nombre, le retourner
    const numberMatch = restTime.match(/^(\d+)$/);
    if (numberMatch) {
        return parseInt(numberMatch[1]);
    }
    // Si c'est au format "X minute(s) Y seconde(s)", convertir en secondes
    let totalSeconds = 0;
    const minutesMatch = restTime.match(/(\d+)\s*minute/i);
    if (minutesMatch) totalSeconds += parseInt(minutesMatch[1]) * 60;
    const secondsInMatch = restTime.match(/(\d+)\s*seconde/i);
    if (secondsInMatch) totalSeconds += parseInt(secondsInMatch[1]);
    return totalSeconds > 0 ? totalSeconds : '-';
};

const extractDurationSeconds = (duration: string | null | undefined): string | number => {
    if (!duration || duration === '-') return '-';
    // Extraire le nombre de secondes depuis le format "X seconde(s)" ou "X secondes"
    const secondsMatch = duration.match(/(\d+)\s*seconde/i);
    if (secondsMatch) {
        return parseInt(secondsMatch[1]);
    }
    // Si c'est juste un nombre, le retourner
    const numberMatch = duration.match(/^(\d+)$/);
    if (numberMatch) {
        return parseInt(numberMatch[1]);
    }
    // Si c'est au format "X minute(s) Y seconde(s)", convertir en secondes
    let totalSeconds = 0;
    const minutesMatch = duration.match(/(\d+)\s*minute/i);
    if (minutesMatch) totalSeconds += parseInt(minutesMatch[1]) * 60;
    const secondsInMatch = duration.match(/(\d+)\s*seconde/i);
    if (secondsInMatch) totalSeconds += parseInt(secondsInMatch[1]);
    return totalSeconds > 0 ? totalSeconds : '-';
};

// Formater une série pour l'affichage
const formatSeriesLine = (set: any, sessionExercise: any): string => {
    const setNumber = set.set_number || 1;
    
    // Déterminer le label et la valeur pour répétitions/durée
    let repsLabel = 'Repets';
    let repsValue: string | number = '-';
    const useDuration = sessionExercise.use_duration === true || sessionExercise.use_duration === 1 || sessionExercise.use_duration === '1' || sessionExercise.use_duration === 'true';
    
    if (useDuration) {
        const rawDuration = set.duration ?? sessionExercise.duration ?? '-';
        repsLabel = 'Durée';
        repsValue = extractDurationSeconds(rawDuration);
    } else {
        repsValue = set.repetitions ?? sessionExercise.repetitions ?? '-';
    }
    
    // Déterminer le texte pour la charge
    let chargeText = '';
    const useBodyweight = sessionExercise.use_bodyweight === true || sessionExercise.use_bodyweight === 1 || sessionExercise.use_bodyweight === '1' || sessionExercise.use_bodyweight === 'true';
    
    if (useBodyweight) {
        chargeText = 'Poids de corps';
    } else {
        const weight = set.weight ?? sessionExercise.weight ?? '-';
        if (weight !== '-' && weight !== null) {
            const weightValue = typeof weight === 'number' ? Math.round(weight) : weight;
            chargeText = `Charges : ${weightValue}`;
        }
    }
    
    // Repos
    const rawRest = set.rest_time ?? sessionExercise.rest_time ?? '-';
    const restSeconds = extractRestSeconds(rawRest);
    
    // Construire la ligne
    let line = `Série : ${setNumber} - ${repsLabel} : ${repsValue}`;
    if (chargeText) {
        line += ` - ${chargeText}`;
    }
    line += `, Repos : ${restSeconds}`;
    
    return line;
};

// Formater une série sans sets (fallback)
const formatSeriesLineFallback = (sessionExercise: any, setsCount: number): string => {
    const useDuration = sessionExercise.use_duration === true || sessionExercise.use_duration === 1 || sessionExercise.use_duration === '1' || sessionExercise.use_duration === 'true';
    const useBodyweight = sessionExercise.use_bodyweight === true || sessionExercise.use_bodyweight === 1 || sessionExercise.use_bodyweight === '1' || sessionExercise.use_bodyweight === 'true';
    
    let repsLabel = 'Repets';
    let repsValue: string | number = '-';
    
    if (useDuration) {
        const rawDuration = sessionExercise.duration ?? '-';
        repsLabel = 'Durée';
        repsValue = extractDurationSeconds(rawDuration);
    } else {
        repsValue = sessionExercise.repetitions ?? '-';
    }
    
    let chargeText = '';
    if (useBodyweight) {
        chargeText = 'Poids de corps';
    } else {
        const weight = sessionExercise.weight ?? '-';
        if (weight !== '-' && weight !== null) {
            const weightValue = typeof weight === 'number' ? Math.round(weight) : weight;
            chargeText = `Charges : ${weightValue}`;
        }
    }
    
    const rawRest = sessionExercise.rest_time ?? '-';
    const restSeconds = extractRestSeconds(rawRest);
    
    let line = `Série : ${setsCount} - ${repsLabel} : ${repsValue}`;
    if (chargeText) {
        line += ` - ${chargeText}`;
    }
    line += `, Repos : ${restSeconds}`;
    
    return line;
};
</script>

<template>
    <Head :title="`${session.name || 'Séance'} • Mes Séances`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 pt-6 pb-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Détails de la séance
                    </p>
                    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                        {{ session.name || 'Séance sans titre' }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <span v-if="session.session_date" class="flex items-center gap-1">
                            <Calendar class="size-4" />
                            {{ formatShortDate(session.session_date) }}
                        </span>
                        <span v-if="sortedExercises.length" class="flex items-center gap-1">
                            · {{ sortedExercises.length }} exercice{{ sortedExercises.length > 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:flex sm:flex-row gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 w-full sm:w-auto"
                        @click="router.visit('/sessions')"
                    >
                        <ArrowLeft class="size-4" />
                        <span>Retour</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 w-full sm:w-auto"
                        @click="handleDownloadPdf"
                    >
                        <Download class="size-4" />
                        <span>PDF</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 w-full sm:w-auto"
                        @click="handleEdit"
                    >
                        <Edit class="size-4" />
                        <span>Modifier</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 w-full sm:w-auto"
                        @click="handleDelete"
                    >
                        <Trash2 class="size-4" />
                        <span>Supprimer</span>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Informations principales -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="size-5" />
                            Informations
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Clients associés -->
                        <div v-if="session.customers && session.customers.length > 0">
                            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-2">
                                <Users class="size-4" />
                                Client{{ session.customers.length > 1 ? 's' : '' }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="customer in session.customers"
                                    :key="customer.id"
                                    variant="default"
                                    class="bg-blue-600 text-white"
                                >
                                    {{ customer.full_name }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="session.notes">
                            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">
                                Notes
                            </p>
                            <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                                {{ session.notes }}
                            </p>
                        </div>
                        <div v-else>
                            <p class="text-xs text-slate-400 dark:text-slate-500">
                                Aucune note pour cette séance.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Métadonnées -->
                <Card>
                    <CardHeader>
                        <CardTitle>Détails</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Date de la séance</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">
                                {{ session.session_date ? formatDate(session.session_date) : 'Non définie' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Nombre d'exercices</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">
                                {{ sortedExercises.length }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Créée le</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">
                                {{ formatDate(session.created_at) }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Liste des exercices -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        Exercices ({{ sortedExercises.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="orderedItems.length > 0" class="space-y-[18px]">
                        <template v-for="item in orderedItems" :key="item.key">
                            <!-- Super Set -->
                            <div
                                v-if="item.type === 'set' && item.block"
                                class="relative rounded-lg bg-[#f7f7f7] p-2"
                            >
                                <div class="flex gap-3">
                                    <!-- Numéro du Super Set sur le côté gauche -->
                                    <div class="flex-shrink-0 w-10 flex items-start justify-center pt-1 border-l-2 border-[#212121] pl-2">
                                        <span class="text-2xl font-bold text-[#212121]">{{ item.displayIndex + 1 }}</span>
                                    </div>
                                    
                                    <!-- Contenu du Super Set -->
                                    <div class="flex-1">
                                        <!-- Label Super Set -->
                                        <div
                                            v-if="item.block.exercises[0]?.additional_description || item.block.exercises[0]?.description"
                                            class="text-right mb-3 mr-6 text-xs"
                                        >
                                            <span class="font-bold">Super set :</span> {{ item.block.exercises[0]?.additional_description || item.block.exercises[0]?.description }}
                                        </div>
                                        
                                        <!-- Exercices du Super Set -->
                                        <div class="space-y-3">
                                            <div
                                                v-for="(sessionExercise, exerciseIndex) in item.block.exercises"
                                                :key="sessionExercise.id || `ex-${item.block.id}-${exerciseIndex}`"
                                                class="flex gap-3"
                                            >
                                                <!-- Image de l'exercice à gauche (plus grande, prend toute la hauteur) -->
                                                <div
                                                    v-if="sessionExercise.exercise?.image_url"
                                                    class="flex-shrink-0 self-start"
                                                >
                                                    <img
                                                        :src="sessionExercise.exercise.image_url"
                                                        :alt="sessionExercise.exercise.title"
                                                        class="w-24 h-auto max-h-28 object-contain rounded"
                                                    />
                                                </div>
                                                <div
                                                    v-else
                                                    class="flex-shrink-0 w-24 h-28 rounded bg-slate-100 dark:bg-slate-800 flex items-center justify-center self-start"
                                                >
                                                    <FileText class="size-6 text-slate-400" />
                                                </div>

                                                <!-- Titre et lignes de séries à droite -->
                                                <div class="flex-1 flex flex-col">
                                                    <!-- Titre de l'exercice -->
                                                    <div class="mb-2">
                                                        <h3 class="font-bold text-sm text-[#212121] mb-1">
                                                            {{ sessionExercise.custom_exercise_name || sessionExercise.exercise?.title || 'Exercice inconnu' }}
                                                        </h3>
                                                    </div>

                                                    <!-- Lignes de séries avec style PDF -->
                                                    <div class="space-y-0.5">
                                                        <div
                                                            v-if="sessionExercise.sets && sessionExercise.sets.length > 0"
                                                            v-for="(set, setIndex) in sessionExercise.sets"
                                                            :key="set.id || `set-${item.block.id}-${exerciseIndex}-${setIndex}`"
                                                            class="text-xs bg-[#d5f5f5] py-1.5 px-3 rounded text-center leading-tight"
                                                        >
                                                            {{ formatSeriesLine(set, sessionExercise) }}
                                                        </div>
                                                        <!-- Fallback pour les anciennes données (sans sets) -->
                                                        <div
                                                            v-else-if="sessionExercise.repetitions || sessionExercise.duration || sessionExercise.rest_time || sessionExercise.weight || sessionExercise.use_bodyweight"
                                                            class="text-xs bg-[#d5f5f5] py-1.5 px-3 rounded text-center leading-tight"
                                                        >
                                                            {{ formatSeriesLineFallback(sessionExercise, sessionExercise.sets_count || 1) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Exercice standard -->
                            <div
                                v-else-if="item.type === 'standard' && item.exercise"
                                :key="item.key"
                                class="relative rounded-lg bg-[#f7f7f7] p-2"
                            >
                                <div class="flex gap-3">
                                    <!-- Numéro d'exercice sur le côté gauche -->
                                    <div class="flex-shrink-0 w-10 flex items-start justify-center pt-1 border-l-2 border-[#212121] pl-2">
                                        <span class="text-2xl font-bold text-[#212121]">{{ item.displayIndex + 1 }}</span>
                                    </div>
                                    
                                    <!-- Contenu de l'exercice -->
                                    <div class="flex-1 flex gap-4">
                                        <!-- Image de l'exercice à gauche (plus grande, prend toute la hauteur) -->
                                        <div
                                            v-if="item.exercise.exercise?.image_url"
                                            class="flex-shrink-0 self-start"
                                        >
                                            <img
                                                :src="item.exercise.exercise.image_url"
                                                :alt="item.exercise.exercise.title"
                                                class="w-28 h-auto max-h-32 object-contain rounded"
                                            />
                                        </div>
                                        <div
                                            v-else
                                            class="flex-shrink-0 w-28 h-32 rounded bg-slate-100 dark:bg-slate-800 flex items-center justify-center self-start"
                                        >
                                            <FileText class="size-8 text-slate-400" />
                                        </div>

                                        <!-- Titre et lignes de séries à droite -->
                                        <div class="flex-1 flex flex-col">
                                            <!-- Titre de l'exercice -->
                                            <div class="mb-2">
                                                <h3 class="font-bold text-base text-[#212121] mb-1">
                                                    {{ item.exercise.custom_exercise_name || item.exercise.exercise?.title || 'Exercice inconnu' }}
                                                </h3>
                                                <p
                                                    v-if="item.exercise.additional_description"
                                                    class="text-xs text-slate-600 dark:text-slate-400"
                                                >
                                                    {{ item.exercise.additional_description }}
                                                </p>
                                            </div>

                                            <!-- Lignes de séries avec style PDF -->
                                            <div class="space-y-0.5">
                                                <div
                                                    v-if="item.exercise.sets && item.exercise.sets.length > 0"
                                                    v-for="(set, setIndex) in item.exercise.sets"
                                                    :key="set.id || `set-${item.exercise.id}-${setIndex}`"
                                                    class="text-xs bg-[#d5f5f5] py-1.5 px-3 rounded text-center leading-tight"
                                                >
                                                    {{ formatSeriesLine(set, item.exercise) }}
                                                </div>
                                                <!-- Fallback pour les anciennes données (sans sets) -->
                                                <div
                                                    v-else-if="item.exercise.repetitions || item.exercise.duration || item.exercise.rest_time || item.exercise.weight || item.exercise.use_bodyweight"
                                                    class="text-xs bg-[#d5f5f5] py-1.5 px-3 rounded text-center leading-tight"
                                                >
                                                    {{ formatSeriesLineFallback(item.exercise, item.exercise.sets_count || 1) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div v-else class="text-center py-8">
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Aucun exercice dans cette séance.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Modal de suppression -->
            <SessionDeleteDialog
                v-model:open="isDeleteDialogOpen"
                :session="session"
                :loading="isDeleteProcessing"
                @confirm="confirmDelete"
            />
        </div>
    </AppLayout>
</template>


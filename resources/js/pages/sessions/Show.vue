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

// Trier les exercices par ordre
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
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="router.visit('/sessions')"
                    >
                        <ArrowLeft class="size-4" />
                        <span>Retour</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="handleDownloadPdf"
                    >
                        <Download class="size-4" />
                        <span>PDF</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="handleEdit"
                    >
                        <Edit class="size-4" />
                        <span>Modifier</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
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
                    <div v-if="sortedExercises.length > 0" class="space-y-4">
                        <div
                            v-for="(sessionExercise, index) in sortedExercises"
                            :key="sessionExercise.id || `ex-${index}`"
                            class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/50"
                        >
                            <div class="flex gap-4">
                                <!-- Image de l'exercice -->
                                <div
                                    v-if="sessionExercise.exercise?.image_url"
                                    class="flex-shrink-0"
                                >
                                    <img
                                        :src="sessionExercise.exercise.image_url"
                                        :alt="sessionExercise.exercise.title"
                                        class="size-20 rounded-lg object-cover"
                                    />
                                </div>
                                <div
                                    v-else
                                    class="flex-shrink-0 size-20 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center"
                                >
                                    <FileText class="size-8 text-slate-400" />
                                </div>

                                <!-- Détails de l'exercice -->
                                <div class="flex-1 space-y-2">
                                    <div>
                                        <h3 class="font-semibold text-slate-900 dark:text-white">
                                            {{ index + 1 }}. {{ sessionExercise.exercise?.title || 'Exercice inconnu' }}
                                        </h3>
                                        <!-- Catégories -->
                                        <div v-if="sessionExercise.exercise?.categories && sessionExercise.exercise.categories.length > 0" class="flex flex-wrap gap-1 mt-1">
                                            <Badge
                                                v-for="category in sessionExercise.exercise.categories"
                                                :key="category.id"
                                                variant="secondary"
                                                class="text-xs"
                                            >
                                                {{ category.name }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <!-- Détails des sets -->
                                    <div v-if="sessionExercise.sets && sessionExercise.sets.length > 0" class="space-y-1">
                                        <div
                                            v-for="(set, setIndex) in sessionExercise.sets"
                                            :key="set.id || `set-${index}-${setIndex}`"
                                            class="flex flex-wrap gap-2 text-sm"
                                        >
                                            <Badge
                                                variant="outline"
                                                class="text-xs"
                                            >
                                                Série {{ set.set_number }}:
                                                <span v-if="set.repetitions">{{ set.repetitions }} répétitions</span>
                                                <span v-if="set.weight"> · {{ set.weight }} kg</span>
                                                <span v-if="set.duration"> · Durée: {{ set.duration }}</span>
                                                <span v-if="set.rest_time"> · Repos: {{ set.rest_time }}</span>
                                            </Badge>
                                        </div>
                                    </div>
                                    <!-- Fallback pour les anciennes données (sans sets) -->
                                    <div v-else-if="sessionExercise.repetitions || sessionExercise.duration || sessionExercise.rest_time || sessionExercise.weight" class="flex flex-wrap gap-2">
                                        <Badge
                                            v-if="sessionExercise.repetitions"
                                            variant="outline"
                                            class="text-xs"
                                        >
                                            {{ sessionExercise.repetitions }} répétitions
                                        </Badge>
                                        <Badge
                                            v-if="sessionExercise.weight"
                                            variant="outline"
                                            class="text-xs"
                                        >
                                            {{ sessionExercise.weight }} kg
                                        </Badge>
                                        <Badge
                                            v-if="sessionExercise.duration"
                                            variant="outline"
                                            class="text-xs"
                                        >
                                            Durée : {{ sessionExercise.duration }}
                                        </Badge>
                                        <Badge
                                            v-if="sessionExercise.rest_time"
                                            variant="outline"
                                            class="text-xs"
                                        >
                                            Repos : {{ sessionExercise.rest_time }}
                                        </Badge>
                                    </div>

                                    <!-- Description additionnelle -->
                                    <p
                                        v-if="sessionExercise.additional_description"
                                        class="text-sm text-slate-600 dark:text-slate-400"
                                    >
                                        {{ sessionExercise.additional_description }}
                                    </p>
                                </div>
                            </div>
                        </div>
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


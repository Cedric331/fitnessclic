<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { ArrowLeft, Edit, Trash2, CheckCircle2, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { BreadcrumbItemType } from '@/types';
import ExerciseFormDialog from './ExerciseFormDialog.vue';
import ExerciseDeleteDialog from './ExerciseDeleteDialog.vue';

interface Exercise {
    id: number;
    title: string;
    description: string | null;
    suggested_duration: string | null;
    image_url: string | null;
    created_at: string;
    user_id: number;
    user_name: string | null;
}

interface Category {
    id: number;
    name: string;
}

interface Session {
    id: number;
    name: string;
    session_date: string | null;
    customer: {
        id: number;
        first_name: string;
        last_name: string;
    } | null;
    pivot: {
        repetitions: number | null;
        rest_time: string | null;
        duration: string | null;
        additional_description: string | null;
        order: number;
    };
}

interface Props {
    exercise: Exercise;
    categories: Category[];
    sessions: Session[];
    categories_list: Category[];
}

const props = defineProps<Props>();

const page = usePage();
const flashMessage = computed(() => {
    const flash = (page.props as any).flash;
    if (!flash) {
        return null;
    }
    return {
        success: flash.success ?? null,
        error: flash.error ?? null,
    };
});

const breadcrumbs: BreadcrumbItemType[] = [
    {
        title: "Bibliothèque d'Exercices",
        href: '/exercises',
    },
    {
        title: props.exercise.title,
        href: `/exercises/${props.exercise.id}`,
    },
];

const isEditDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const canEdit = computed(() => {
    const user = (page.props as any).auth?.user;
    return user && (user.id === props.exercise.user_id || user.role === 'admin');
});
const canDelete = computed(() => {
    const user = (page.props as any).auth?.user;
    // Seul le créateur ou un admin peut supprimer
    return user && (user.id === props.exercise.user_id || user.role === 'admin');
});

const formatDate = (value?: string | null) => {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatSessionDate = (date: string | null) => {
    if (!date) {
        return 'Date non définie';
    }
    return formatDate(date);
};
</script>

<template>
    <Head :title="`${exercise.title} • Exercice`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-full w-full flex-1 flex-col gap-6 rounded-xl px-6 py-5">
            <!-- Messages flash -->
            <div class="space-y-3">
                <Alert
                    v-if="flashMessage?.success"
                    class="flex items-start gap-3 rounded-2xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200"
                >
                    <CheckCircle2 class="size-4 text-emerald-600 dark:text-emerald-300" />
                    <div>
                        <AlertTitle>Succès</AlertTitle>
                        <AlertDescription>{{ flashMessage.success }}</AlertDescription>
                    </div>
                </Alert>
                <Alert
                    v-if="flashMessage?.error"
                    variant="destructive"
                    class="flex items-start gap-3 rounded-2xl border border-destructive-200/70 bg-destructive-50/80 px-4 py-3 text-destructive-600 dark:border-destructive-800/70 dark:bg-destructive-900/30 dark:text-destructive-200"
                >
                    <XCircle class="size-4 text-destructive-500 dark:text-destructive-300" />
                    <div>
                        <AlertTitle>Erreur</AlertTitle>
                        <AlertDescription>{{ flashMessage.error }}</AlertDescription>
                    </div>
                </Alert>
            </div>

            <!-- En-tête -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Détails de l'exercice
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                            {{ exercise.title }}
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Créé le {{ formatDate(exercise.created_at) }}
                        <span v-if="exercise.user_name" class="mx-1">·</span>
                        <span v-if="exercise.user_name">Par {{ exercise.user_name }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="router.visit('/exercises')"
                    >
                        <ArrowLeft class="size-4" />
                        <span>Retour</span>
                    </Button>
                    <Button
                        v-if="canEdit"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="isEditDialogOpen = true"
                    >
                        <Edit class="size-4" />
                        <span>Modifier</span>
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Colonne principale -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Image -->
                    <Card v-if="exercise.image_url">
                        <CardContent class="p-0">
                            <div class="relative aspect-video w-full overflow-hidden rounded-t-lg">
                                <img
                                    :src="exercise.image_url"
                                    :alt="exercise.title"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Description -->
                    <Card v-if="exercise.description">
                        <CardHeader>
                            <CardTitle>Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                                {{ exercise.description }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Sessions où l'exercice est utilisé -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Sessions utilisant cet exercice</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="sessions.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400">
                                <p>Aucune session n'utilise cet exercice pour le moment.</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="session in sessions"
                                    :key="session.id"
                                    class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 space-y-2">
                                            <h4 class="font-semibold text-slate-900 dark:text-white">
                                                {{ session.name }}
                                            </h4>
                                            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                                <span v-if="session.customer">
                                                    Client : {{ session.customer.first_name }} {{ session.customer.last_name }}
                                                </span>
                                                <span v-if="session.session_date">
                                                    · Date : {{ formatSessionDate(session.session_date) }}
                                                </span>
                                            </div>
                                            <div v-if="session.pivot.repetitions || session.pivot.duration || session.pivot.rest_time" class="flex flex-wrap gap-2 text-xs">
                                                <Badge v-if="session.pivot.repetitions" variant="outline">
                                                    {{ session.pivot.repetitions }} répétitions
                                                </Badge>
                                                <Badge v-if="session.pivot.duration" variant="outline">
                                                    Durée : {{ session.pivot.duration }}
                                                </Badge>
                                                <Badge v-if="session.pivot.rest_time" variant="outline">
                                                    Repos : {{ session.pivot.rest_time }}
                                                </Badge>
                                            </div>
                                            <p v-if="session.pivot.additional_description" class="text-sm text-slate-600 dark:text-slate-400">
                                                {{ session.pivot.additional_description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Colonne latérale -->
                <div class="space-y-6">
                    <!-- Informations -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Informations</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="exercise.suggested_duration">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Durée suggérée
                                </p>
                                <p class="text-sm text-slate-900 dark:text-white">
                                    {{ exercise.suggested_duration }}
                                </p>
                            </div>
                            <Separator v-if="exercise.suggested_duration" />
                        </CardContent>
                    </Card>

                    <!-- Catégories -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Catégories</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="categories.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
                                Aucune catégorie
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="category in categories"
                                    :key="category.id"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    {{ category.name }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Modal d'édition -->
        <ExerciseFormDialog
            v-if="canEdit"
            v-model:open="isEditDialogOpen"
            :exercise="{
                id: exercise.id,
                name: exercise.title,
                title: exercise.title,
                description: exercise.description,
                suggested_duration: exercise.suggested_duration,
                image_url: exercise.image_url,
                category_id: categories.length > 0 ? categories[0].id : null,
                created_at: exercise.created_at,
            }"
            :categories="categories_list"
        />

        <ExerciseDeleteDialog
            v-if="canDelete"
            v-model:open="isDeleteDialogOpen"
            :exercise="{
                id: exercise.id,
                name: exercise.title,
                image_url: exercise.image_url || '',
                category_name: categories.length > 0 ? categories[0].name : 'Sans catégorie',
                created_at: exercise.created_at,
            }"
        />
    </AppLayout>
</template>


<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft, Edit, Trash2, Eye, Download, Printer } from 'lucide-vue-next';
import { computed, ref, watch, nextTick } from 'vue';
import type { BreadcrumbItemType } from '@/types';
import ExerciseFormDialog from './ExerciseFormDialog.vue';
import ExerciseDeleteDialog from './ExerciseDeleteDialog.vue';
import { useNotifications } from '@/composables/useNotifications';
import UpgradeModal from '@/components/UpgradeModal.vue';

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
const isUpgradeModalOpen = ref(false);
const canEdit = computed(() => {
    const user = (page.props as any).auth?.user;
    return user && (user.id === props.exercise.user_id || user.isAdmin);
});
const canDelete = computed(() => {
    const user = (page.props as any).auth?.user;
    // Seul le créateur ou un admin peut supprimer
    return user && (user.id === props.exercise.user_id || user.isAdmin);
});
const isPro = computed(() => {
    const user = (page.props as any).auth?.user;
    return user?.isPro ?? false;
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

// Actions pour les séances
const handleViewSession = (session: Session) => {
    router.visit(`/sessions/${session.id}`);
};

const handleDownloadPdf = (session: Session) => {
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }
    window.open(`/sessions/${session.id}/pdf`, '_blank');
};

const handlePrint = (session: Session) => {
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }
    
    // Récupérer le token CSRF
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

    // Récupérer le PDF via fetch
    fetch(`/sessions/${session.id}/pdf`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/pdf',
        },
        credentials: 'include',
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
        notifyError(error.message || 'Une erreur est survenue lors de l\'ouverture du PDF.', 'Erreur');
    });
};
</script>

<template>
    <Head :title="`${exercise.title} • Exercice`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-full w-full flex-1 flex-col gap-6 rounded-xl px-6 py-5">
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
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="isEditDialogOpen = true"
                    >
                        <Edit class="size-4" />
                        <span>Modifier</span>
                    </Button>
                    <Button
                        v-if="canDelete"
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                        @click="isDeleteDialogOpen = true"
                    >
                        <Trash2 class="size-4" />
                        <span>Supprimer</span>
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Colonne principale -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Image -->
                    <Card v-if="exercise.image_url">
                        <CardContent class="p-4">
                            <div class="relative w-full overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">
                                <img
                                    :src="exercise.image_url"
                                    :alt="exercise.title"
                                    class="w-full h-auto max-h-96 object-contain"
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
                                    <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 text-xs"
                                            @click="handleViewSession(session)"
                                        >
                                            <Eye class="size-3.5 mr-1.5" />
                                            Consulter
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 text-xs"
                                            @click="handleDownloadPdf(session)"
                                        >
                                            <Download class="size-3.5 mr-1.5" />
                                            PDF
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 text-xs"
                                            @click="handlePrint(session)"
                                        >
                                            <Printer class="size-3.5 mr-1.5" />
                                            Imprimer
                                        </Button>
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
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Date de création
                                </p>
                                <p class="text-sm text-slate-900 dark:text-white">
                                    {{ formatDate(exercise.created_at) }}
                                </p>
                            </div>
                            <div v-if="exercise.user_name">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Créé par
                                </p>
                                <p class="text-sm text-slate-900 dark:text-white">
                                    {{ exercise.user_name }}
                                </p>
                            </div>
                            <Separator v-if="exercise.suggested_duration || exercise.user_name" />
                            <div v-if="exercise.suggested_duration">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Durée suggérée
                                </p>
                                <p class="text-sm text-slate-900 dark:text-white">
                                    {{ exercise.suggested_duration }}
                                </p>
                            </div>
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

        <!-- Modal d'abonnement -->
        <UpgradeModal
            v-model:open="isUpgradeModalOpen"
            feature="L'export PDF est réservé aux abonnés Pro. Passez à Pro pour exporter vos séances en PDF."
        />
    </AppLayout>
</template>


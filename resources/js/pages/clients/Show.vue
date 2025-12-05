<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ArrowLeft, Eye, Download, Printer, Mail, Edit, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch, nextTick } from 'vue';
import type { Customer, TrainingSessionHistory } from './types';
import { useNotifications } from '@/composables/useNotifications';
import CustomerEditDialog from './CustomerEditDialog.vue';
import CustomerDeleteDialog from './CustomerDeleteDialog.vue';

interface Props {
    customer: Customer;
    training_sessions: TrainingSessionHistory[];
}

const props = defineProps<Props>();

const pageTitle = computed(() => `${props.customer.first_name} ${props.customer.last_name}`);
const breadcrumbs = computed(() => [
    { title: 'Mes Clients', href: '/customers' },
    { title: pageTitle.value, href: `/customers/${props.customer.id}` },
]);

const sessionHistory = computed(() => props.training_sessions ?? []);
const totalSessions = computed(() => sessionHistory.value.length);
const lastSessionDate = computed(() => {
    const firstSession = sessionHistory.value[0];
    return firstSession?.session_date ?? firstSession?.created_at ?? null;
});

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

const formatSessionDate = (session: TrainingSessionHistory) => {
    const dateValue = session.session_date ?? session.created_at;
    if (!dateValue) {
        return 'Date inconnue';
    }

    return formatDate(dateValue);
};

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();

// État pour l'envoi d'email
const isSendEmailDialogOpen = ref(false);
const sessionToSend = ref<TrainingSessionHistory | null>(null);
const isSendingEmail = ref(false);

// État pour l'édition et la suppression du client
const isEditDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const isDeleteProcessing = ref(false);

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

// Actions pour les séances
const handleViewSession = (session: TrainingSessionHistory) => {
    router.visit(`/sessions/${session.id}`);
};

const handleDownloadPdf = (session: TrainingSessionHistory) => {
    window.open(`/sessions/${session.id}/pdf`, '_blank');
};

const handlePrint = (session: TrainingSessionHistory) => {
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
        console.error('Erreur complète:', error);
        notifyError(error.message || 'Une erreur est survenue lors de l\'ouverture du PDF.', 'Erreur');
    });
};

const handleSendEmail = (session: TrainingSessionHistory) => {
    sessionToSend.value = session;
    isSendEmailDialogOpen.value = true;
};

const confirmSendEmail = () => {
    if (!sessionToSend.value || isSendingEmail.value) {
        return;
    }

    // Vérifier que le client a un email
    if (!props.customer.email) {
        notifyError('Ce client n\'a pas d\'adresse email.');
        isSendEmailDialogOpen.value = false;
        return;
    }

    isSendingEmail.value = true;

    router.post(`/sessions/${sessionToSend.value.id}/send-email`, {
        customer_id: props.customer.id,
        redirect_to_customer: true,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSendEmailDialogOpen.value = false;
            sessionToSend.value = null;
        },
        onFinish: () => {
            isSendingEmail.value = false;
        },
    });
};

watch(isSendEmailDialogOpen, (open) => {
    if (!open) {
        sessionToSend.value = null;
        isSendingEmail.value = false;
    }
});

// Fonctions pour l'édition et la suppression du client
const handleEditCustomer = () => {
    isEditDialogOpen.value = true;
};

const handleDeleteCustomer = () => {
    isDeleteDialogOpen.value = true;
};

const confirmDeleteCustomer = () => {
    if (isDeleteProcessing.value) {
        return;
    }

    isDeleteProcessing.value = true;

    router.delete(`/customers/${props.customer.id}`, {
        preserveScroll: false,
        onSuccess: () => {
            router.visit('/customers');
        },
        onFinish: () => {
            isDeleteProcessing.value = false;
        },
    });
};

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        isDeleteProcessing.value = false;
    }
});
</script>

<template>
    <Head :title="`${pageTitle} • Client`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 pt-6 pb-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Profil client
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                            {{ pageTitle }}
                        </h1>
                        <Badge
                            :variant="props.customer.is_active ? 'default' : 'secondary'"
                            class="text-xs"
                        >
                            {{ props.customer.is_active ? 'Actif' : 'Inactif' }}
                        </Badge>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ props.customer.email || 'Email non renseigné' }}
                        <span v-if="props.customer.phone" class="mx-1">·</span>
                        {{ props.customer.phone || 'Téléphone non renseigné' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="handleEditCustomer"
                    >
                        <Edit class="size-4" />
                        <span>Modifier</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                        @click="handleDeleteCustomer"
                    >
                        <Trash2 class="size-4" />
                        <span>Supprimer</span>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="inline-flex items-center gap-2"
                        @click="router.visit('/customers')"
                    >
                        <ArrowLeft class="size-4" />
                        <span>Retour</span>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardContent class="space-y-3">
                        <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">
                            Informations générales
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase text-slate-500">Email</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ props.customer.email || '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-slate-500">Téléphone</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ props.customer.phone || '—' }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs uppercase text-slate-500">Note interne</p>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ props.customer.internal_note || 'Aucune note enregistrée.' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="space-y-4">
                        <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">
                            Statistiques
                        </p>
                        <div class="grid gap-4">
                            <div>
                                <p class="text-xs text-slate-500">Séances enregistrées</p>
                                <p class="text-2xl font-semibold text-slate-900 dark:text-white">
                                    {{ totalSessions }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Dernière séance</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ lastSessionDate ? formatDate(lastSessionDate) : 'Pas encore de séance' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                Historique des séances
                            </p>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ totalSessions }} séance{{ totalSessions > 1 ? 's' : '' }}
                            </h2>
                        </div>
                        <Badge variant="outline" class="text-xs text-slate-500">
                            {{ props.customer.is_active ? 'Profil actif' : 'Profil inactif' }}
                        </Badge>
                    </div>

                    <Separator />

                    <div class="space-y-3">
                        <div
                            v-for="session in sessionHistory"
                            :key="session.id"
                            class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm transition-colors hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/70"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex-1">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        {{ formatSessionDate(session) }}
                                    </p>
                                    <p class="text-base font-semibold text-slate-900 dark:text-white">
                                        {{ session.name || 'Séance sans titre' }}
                                    </p>
                                </div>
                                <Badge variant="outline" class="text-xs text-slate-500">
                                    {{ (session.exercises_count ?? 0) }} exercice{{ (session.exercises_count ?? 0) > 1 ? 's' : '' }}
                                </Badge>
                            </div>
                            <p v-if="session.notes" class="text-sm text-slate-600 dark:text-slate-300 mt-2">
                                {{ session.notes }}
                            </p>
                            <p v-else class="text-xs text-slate-400 mt-2">
                                Aucune note sur cette séance.
                            </p>
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
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 text-xs"
                                    @click="handleSendEmail(session)"
                                    :disabled="!props.customer.email"
                                >
                                    <Mail class="size-3.5 mr-1.5" />
                                    Envoyer par mail
                                </Button>
                            </div>
                        </div>

                        <p v-if="sessionHistory.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
                            Aucun entraînement enregistré pour l’instant. Commencez par créer une session depuis l’accueil.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Modal d'envoi par email -->
            <Dialog v-model:open="isSendEmailDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Envoyer la séance par email</DialogTitle>
                        <DialogDescription>
                            La séance "{{ sessionToSend?.name || 'Sans titre' }}" sera envoyée par email à {{ props.customer.first_name }} {{ props.customer.last_name }} ({{ props.customer.email }}).
                        </DialogDescription>
                    </DialogHeader>
                    
                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isSendEmailDialogOpen = false"
                            :disabled="isSendingEmail"
                        >
                            Annuler
                        </Button>
                        <Button
                            @click="confirmSendEmail"
                            :disabled="isSendingEmail"
                        >
                            {{ isSendingEmail ? 'Envoi en cours...' : 'Envoyer' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Dialog d'édition du client -->
            <CustomerEditDialog
                v-model:open="isEditDialogOpen"
                :customer="props.customer"
            />

            <!-- Dialog de suppression du client -->
            <CustomerDeleteDialog
                v-model:open="isDeleteDialogOpen"
                :customer="props.customer"
                :loading="isDeleteProcessing"
                @confirm="confirmDeleteCustomer"
            />
        </div>
    </AppLayout>
</template>


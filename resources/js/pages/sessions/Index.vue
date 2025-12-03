<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { useNotifications } from '@/composables/useNotifications';
import { Plus } from 'lucide-vue-next';
import SessionList from './SessionList.vue';
import SessionDeleteDialog from './SessionDeleteDialog.vue';
import type { Session, SessionsProps, Customer } from './types';
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const props = defineProps<SessionsProps>();
const page = usePage();
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
];

const searchTerm = ref(props.filters.search || '');
const sortOrder = ref<'newest' | 'oldest'>(props.filters.sort || 'newest');
const customerFilter = ref<number | null>(props.filters.customer_id || null);

const searchInput = ref<HTMLInputElement | null>(null);
const isDeleteDialogOpen = ref(false);
const sessionToDelete = ref<Session | null>(null);
const isDeleteProcessing = ref(false);

// Modal d'envoi par email
const isSendEmailDialogOpen = ref(false);
const sessionToSend = ref<Session | null>(null);
const selectedCustomerId = ref<number | null>(null);
const isSendingEmail = ref(false);

// Clients disponibles pour l'envoi (clients associés à la séance et actifs)
const availableCustomers = computed(() => {
    if (!sessionToSend.value || !sessionToSend.value.customers) {
        return [];
    }
    return sessionToSend.value.customers.filter((customer: Customer) => {
        // Vérifier que le client est actif et a un email
        return customer.is_active !== false && customer.email;
    });
});

// Synchroniser searchTerm avec les props
watch(() => props.filters.search, (value) => {
    searchTerm.value = value || '';
});

watch(() => props.filters.sort, (value) => {
    sortOrder.value = (value as 'newest' | 'oldest') || 'newest';
});

watch(() => props.filters.customer_id, (value) => {
    customerFilter.value = value || null;
});

const applyFilters = () => {
    const query: Record<string, string | number> = {};
    
    if (searchTerm.value.trim()) {
        query.search = searchTerm.value.trim();
    }
    
    if (customerFilter.value !== null) {
        query.customer_id = customerFilter.value;
    }
    
    if (sortOrder.value) {
        query.sort = sortOrder.value;
    }
    
    router.get('/sessions', query, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Recherche en temps réel avec debounce
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
let isInitialMount = true;

watch(searchTerm, (newValue, oldValue) => {
    // Ignorer la première synchronisation lors du montage
    if (isInitialMount) {
        isInitialMount = false;
        return;
    }
    
    // Si la valeur n'a pas changé, ne pas rechercher
    if (newValue === oldValue) {
        return;
    }
    
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300); // 300ms de délai
});

const handleSortChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    sortOrder.value = target.value as 'newest' | 'oldest';
    applyFilters();
};

const handleCustomerFilterChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    customerFilter.value = target.value ? Number(target.value) : null;
    applyFilters();
};

const handleDelete = (session: Session) => {
    sessionToDelete.value = session;
    isDeleteDialogOpen.value = true;
};

const handleSendEmail = (session: Session) => {
    sessionToSend.value = session;
    selectedCustomerId.value = null;
    isSendEmailDialogOpen.value = true;
};

const confirmSendEmail = () => {
    if (!sessionToSend.value || !selectedCustomerId.value || isSendingEmail.value) {
        return;
    }

    isSendingEmail.value = true;

    router.post(`/sessions/${sessionToSend.value.id}/send-email`, {
        customer_id: selectedCustomerId.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSendEmailDialogOpen.value = false;
            sessionToSend.value = null;
            selectedCustomerId.value = null;
        },
        onFinish: () => {
            isSendingEmail.value = false;
        },
    });
};

watch(isSendEmailDialogOpen, (open) => {
    if (!open) {
        sessionToSend.value = null;
        selectedCustomerId.value = null;
        isSendingEmail.value = false;
    }
});

const confirmDelete = () => {
    if (!sessionToDelete.value || isDeleteProcessing.value) {
        return;
    }

    isDeleteProcessing.value = true;

    router.delete(`/sessions/${sessionToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            sessionToDelete.value = null;
        },
        onFinish: () => {
            isDeleteProcessing.value = false;
        },
    });
};

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        sessionToDelete.value = null;
        isDeleteProcessing.value = false;
    }
});
</script>

<template>
    <Head title="Mes Séances" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto w-full px-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex flex-col gap-0.5">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                        Mes Séances
                    </h1>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Consultez vos séances d'entraînement enregistrées
                    </p>
                </div>
            
                <Button
                    variant="default"
                    size="sm"
                    @click="router.visit('/sessions/create')"
                >
                    <Plus class="size-4 mr-2" />
                    Créer une Séance
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:gap-3">
                <!-- Search Bar -->
                <div class="flex flex-col gap-1.5 lg:flex-1">
                    <label class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                        Rechercher
                    </label>
                    <div class="relative">
                        <Search
                            class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
                        />
                        <Input
                            ref="searchInput"
                            v-model="searchTerm"
                            type="text"
                            placeholder="Rechercher une séance..."
                            class="w-full pl-10"
                        />
                    </div>
                </div>

                <!-- Customer Filter -->
                <div class="flex flex-col gap-1.5 lg:flex-1">
                    <label class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                        Client
                    </label>
                    <select
                        :value="customerFilter || ''"
                        class="h-10 w-full rounded-2xl border border-slate-200 bg-white px-3 text-sm text-slate-700 transition focus:border-blue-500 focus:outline-none focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                        @change="handleCustomerFilterChange"
                    >
                        <option value="">Tous les clients</option>
                        <option
                            v-for="customer in customers"
                            :key="customer.id"
                            :value="customer.id"
                        >
                            {{ customer.full_name }}
                        </option>
                    </select>
                </div>

                <!-- Sort -->
                <div class="flex flex-col gap-1.5 lg:flex-1">
                    <label class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                        Trier
                    </label>
                    <select
                        :value="sortOrder"
                        class="h-10 w-full rounded-2xl border border-slate-200 bg-white px-3 text-sm text-slate-700 transition focus:border-blue-500 focus:outline-none focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                        @change="handleSortChange"
                    >
                        <option value="newest">Plus récentes</option>
                        <option value="oldest">Plus anciennes</option>
                    </select>
                </div>
            </div>

            <!-- Session List -->
            <SessionList
                :sessions="sessions.data"
                :has-search="!!props.filters.search"
                @delete="handleDelete"
                @send-email="handleSendEmail"
            />

            <!-- Modal de suppression -->
            <SessionDeleteDialog
                v-model:open="isDeleteDialogOpen"
                :session="sessionToDelete"
                :loading="isDeleteProcessing"
                @confirm="confirmDelete"
            />

            <!-- Modal d'envoi par email -->
            <Dialog v-model:open="isSendEmailDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Envoyer la séance par email</DialogTitle>
                        <DialogDescription>
                            Sélectionnez le client à qui envoyer la séance "{{ sessionToSend?.name || 'Sans titre' }}".
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="space-y-4 py-4">
                        <div v-if="availableCustomers.length === 0" class="text-sm text-slate-600 dark:text-slate-400 text-center py-4">
                            <p>Aucun client actif avec une adresse email n'est associé à cette séance.</p>
                        </div>
                        <div v-else class="space-y-2">
                            <Label for="customer-select">Client</Label>
                            <select
                                id="customer-select"
                                v-model="selectedCustomerId"
                                class="h-10 w-full rounded-2xl border border-slate-200 bg-white px-3 text-sm text-slate-700 transition focus:border-blue-500 focus:outline-none focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                            >
                                <option :value="null">Sélectionner un client</option>
                                <option
                                    v-for="customer in availableCustomers"
                                    :key="customer.id"
                                    :value="customer.id"
                                >
                                    {{ customer.full_name }} {{ customer.email ? `(${customer.email})` : '' }}
                                </option>
                            </select>
                        </div>
                    </div>

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
                            :disabled="!selectedCustomerId || isSendingEmail"
                        >
                            {{ isSendingEmail ? 'Envoi en cours...' : 'Envoyer' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>


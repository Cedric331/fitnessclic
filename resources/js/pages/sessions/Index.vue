<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, XCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-vue-next';
import SessionList from './SessionList.vue';
import SessionDeleteDialog from './SessionDeleteDialog.vue';
import type { Session, SessionsProps } from './types';

const props = defineProps<SessionsProps>();
const page = usePage();

// Messages flash
const flashMessage = computed(() => {
    const flash = (page.props as any).flash;
    if (!flash) return null;
    return {
        success: flash.success || null,
        error: flash.error || null,
    };
});

// Fermer automatiquement les messages après 5 secondes
watch(flashMessage, (newValue) => {
    if (newValue?.success || newValue?.error) {
        setTimeout(() => {
            // Les messages flash sont automatiquement supprimés par Inertia
        }, 5000);
    }
});

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
            <!-- Messages Flash -->
            <Alert
                v-if="flashMessage?.success"
                class="bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200"
            >
                <CheckCircle2 class="size-4 text-green-600 dark:text-green-400" />
                <AlertTitle>Succès</AlertTitle>
                <AlertDescription>{{ flashMessage.success }}</AlertDescription>
            </Alert>
            <Alert
                v-if="flashMessage?.error"
                variant="destructive"
            >
                <XCircle class="size-4" />
                <AlertTitle>Erreur</AlertTitle>
                <AlertDescription>{{ flashMessage.error }}</AlertDescription>
            </Alert>

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
            />

            <!-- Modal de suppression -->
            <SessionDeleteDialog
                v-model:open="isDeleteDialogOpen"
                :session="sessionToDelete"
                :loading="isDeleteProcessing"
                @confirm="confirmDelete"
            />
        </div>
    </AppLayout>
</template>


<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch, nextTick } from 'vue';
import type { BreadcrumbItem } from '@/types';
import CustomerCreateDialog from './CustomerCreateDialog.vue';
import CustomerEditDialog from './CustomerEditDialog.vue';
import CustomerList from './CustomerList.vue';
import CustomerDeleteDialog from './CustomerDeleteDialog.vue';
import type { Customer, CustomersProps } from './types';
import { useNotifications } from '@/composables/useNotifications';

const props = defineProps<CustomersProps>();
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
        title: 'Mes Clients',
        href: '/customers',
    },
];

const searchTerm = ref(props.filters.search || '');

const searchInput = ref<HTMLInputElement | null>(null);
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const editingCustomer = ref<Customer | null>(null);
const isDeleteDialogOpen = ref(false);
const customerToDelete = ref<Customer | null>(null);
const isDeleteProcessing = ref(false);

// Synchroniser searchTerm avec les props
watch(() => props.filters.search, (value) => {
    searchTerm.value = value || '';
});

const handleSearch = () => {
    const query: Record<string, string> = {};
    if (searchTerm.value.trim()) {
        query.search = searchTerm.value.trim();
    }
    
    router.get('/customers', query, {
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
        handleSearch();
    }, 300); // 300ms de délai
});

const handleEditCustomer = (customer: Customer) => {
    editingCustomer.value = customer;
    isEditDialogOpen.value = true;
};

const handleDelete = (customer: Customer) => {
    customerToDelete.value = customer;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!customerToDelete.value || isDeleteProcessing.value) {
        return;
    }

    isDeleteProcessing.value = true;

    router.delete(`/customers/${customerToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            customerToDelete.value = null;
        },
        onFinish: () => {
            isDeleteProcessing.value = false;
        },
    });
};

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        customerToDelete.value = null;
        isDeleteProcessing.value = false;
    }
});
</script>

<template>
    <Head title="Mes Clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 mx-auto w-full px-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex flex-col gap-0.5">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                        Mes Clients
                    </h1>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Gérez vos clients et leurs programmes d'entraînement
                    </p>
                </div>
            
                <CustomerCreateDialog
                    v-model:open="isCreateDialogOpen"
                    trigger-label="Nouveau Client"
                    trigger-variant="sm"
                    :show-trigger="true"
                />
            </div>

            <!-- Search Bar -->
            <div class="relative">
                <Search
                    class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
                />
                <Input
                    ref="searchInput"
                    v-model="searchTerm"
                    type="text"
                    placeholder="Rechercher un client..."
                    class="w-full pl-10"
                />
            </div>

            <!-- Customer List -->
            <CustomerList
                :customers="customers.data"
                :has-search="!!props.filters.search"
                @edit="handleEditCustomer"
                @delete="handleDelete"
            />

            <!-- Modal d'édition -->
            <CustomerEditDialog
                v-model:open="isEditDialogOpen"
                :customer="editingCustomer"
            />
            <CustomerDeleteDialog
                v-model:open="isDeleteDialogOpen"
                :customer="customerToDelete"
                :loading="isDeleteProcessing"
                @confirm="confirmDelete"
            />
        </div>
    </AppLayout>
</template>

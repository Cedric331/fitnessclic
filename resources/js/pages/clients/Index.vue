<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, XCircle } from 'lucide-vue-next';
import CustomerCreateDialog from './CustomerCreateDialog.vue';
import CustomerEditDialog from './CustomerEditDialog.vue';
import CustomerList from './CustomerList.vue';
import CustomerDeleteDialog from './CustomerDeleteDialog.vue';
import type { Customer, CustomersProps } from './types';

const props = defineProps<CustomersProps>();
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
        title: 'Mes Clients',
        href: '/customers',
    },
];

const searchForm = useForm({
    search: props.filters.search || '',
});

const searchInput = ref<HTMLInputElement | null>(null);
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const editingCustomer = ref<Customer | null>(null);
const isDeleteDialogOpen = ref(false);
const customerToDelete = ref<Customer | null>(null);
const isDeleteProcessing = ref(false);

const handleSearch = () => {
    searchForm.get('/customers', {
        preserveState: true,
        preserveScroll: true,
    });
};

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
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 max-w-7xl mx-auto w-full px-6">
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
                <form @submit.prevent="handleSearch">
                    <Input
                        ref="searchInput"
                        v-model="searchForm.search"
                        type="text"
                        placeholder="Rechercher un client..."
                        class="w-full pl-10"
                        @keyup.enter="handleSearch"
                    />
                </form>
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

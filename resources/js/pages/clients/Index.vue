<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { 
    Search, 
    Plus, 
    Eye, 
    Pencil, 
    Trash2,
    User 
} from 'lucide-vue-next';
import { useInitials } from '@/composables/useInitials';
import { computed, ref, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, XCircle } from 'lucide-vue-next';

interface Customer {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
    is_active: boolean;
    training_sessions_count: number;
}

interface Props {
    customers: {
        data: Customer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const { getInitials } = useInitials();

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
const isDialogOpen = ref(false);

const customerForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    is_active: true,
});

const handleSearch = () => {
    searchForm.get('/customers', {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleCreateCustomer = () => {
    customerForm.post('/customers', {
        preserveScroll: true,
        onSuccess: () => {
            isDialogOpen.value = false;
            customerForm.reset();
            customerForm.is_active = true;
        },
    });
};

const handleDelete = (customerId: number) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce client ?')) {
        router.delete(`/customers/${customerId}`, {
            preserveScroll: true,
            onSuccess: () => {
                // La redirection est gérée par le backend
            },
        });
    }
};

const getCustomerName = (customer: Customer): string => {
    return `${customer.first_name} ${customer.last_name.charAt(0).toUpperCase()}.`;
};
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
                <Dialog v-model:open="isDialogOpen">
                    <DialogTrigger as-child>
                        <Button size="sm" class="bg-blue-600 hover:bg-blue-700 text-white">
                            <Plus class="size-4" />
                            <span>Nouveau Client</span>
                        </Button>
                    </DialogTrigger>
                    <DialogContent 
                        class="sm:max-w-[550px] !z-[60] p-0 overflow-hidden"
                    >
                        <DialogHeader class="px-6 pt-6 pb-4">
                            <DialogTitle class="text-xl font-semibold">Nouveau Client</DialogTitle>
                            <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                Ajoutez un nouveau client à votre liste. Remplissez les informations ci-dessous.
                            </DialogDescription>
                        </DialogHeader>
                        <Separator />
                        <form id="customer-form" @submit.prevent="handleCreateCustomer" class="px-6 py-4 space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="first_name" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Prénom <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="first_name"
                                        v-model="customerForm.first_name"
                                        type="text"
                                        placeholder="Prénom"
                                        required
                                        class="h-10"
                                        :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.first_name }"
                                    />
                                    <p v-if="customerForm.errors.first_name" class="text-xs text-red-500 mt-1">
                                        {{ customerForm.errors.first_name }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="last_name" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Nom <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="last_name"
                                        v-model="customerForm.last_name"
                                        type="text"
                                        placeholder="Nom"
                                        required
                                        class="h-10"
                                        :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.last_name }"
                                    />
                                    <p v-if="customerForm.errors.last_name" class="text-xs text-red-500 mt-1">
                                        {{ customerForm.errors.last_name }}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label for="email" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Email
                                </Label>
                                <Input
                                    id="email"
                                    v-model="customerForm.email"
                                    type="email"
                                    placeholder="email@example.com"
                                    class="h-10"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.email }"
                                />
                                <p v-if="customerForm.errors.email" class="text-xs text-red-500 mt-1">
                                    {{ customerForm.errors.email }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="phone" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Téléphone
                                </Label>
                                <Input
                                    id="phone"
                                    v-model="customerForm.phone"
                                    type="tel"
                                    placeholder="+33 6 12 34 56 78"
                                    class="h-10"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.phone }"
                                />
                                <p v-if="customerForm.errors.phone" class="text-xs text-red-500 mt-1">
                                    {{ customerForm.errors.phone }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2 pt-2">
                                <Checkbox
                                    id="is_active"
                                    :checked="customerForm.is_active"
                                    @update:checked="(checked: boolean) => customerForm.is_active = checked"
                                />
                                <Label
                                    for="is_active"
                                    class="text-sm font-normal cursor-pointer text-slate-700 dark:text-slate-300"
                                >
                                    Client actif
                                </Label>
                            </div>
                        </form>
                        <Separator />
                            <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 dark:hover:border-slate-600 transition-all duration-200"
                                    @click="isDialogOpen = false"
                                >
                                    Annuler
                                </Button>
                                <Button
                                    type="submit"
                                    form="customer-form"
                                    class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md"
                                    :disabled="customerForm.processing"
                                >
                                    {{ customerForm.processing ? 'Création...' : 'Créer le client' }}
                                </Button>
                            </DialogFooter>
                    </DialogContent>
                </Dialog>
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

            <!-- Clients Grid -->
            <div
                v-if="customers.data.length > 0"
                class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3"
            >
                <Card
                    v-for="customer in customers.data"
                    :key="customer.id"
                    class="hover:shadow-md transition-shadow"
                >
                    <CardContent class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3 flex-1">
                                <Avatar class="size-10 bg-slate-200 dark:bg-slate-700">
                                    <AvatarFallback class="text-sm text-slate-700 dark:text-slate-300">
                                        {{ getInitials(`${customer.first_name} ${customer.last_name}`) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                        {{ getCustomerName(customer) }}
                                    </h3>
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            v-if="customer.is_active"
                                            variant="default"
                                            class="bg-blue-600 text-white border-blue-600 text-xs px-1.5 py-0"
                                        >
                                            Actif
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="secondary"
                                            class="bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 text-xs px-1.5 py-0"
                                        >
                                            Inactif
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">
                                        {{ customer.training_sessions_count }} programme{{
                                            customer.training_sessions_count > 1 ? 's' : ''
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-1.5 mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                            <Button
                                variant="ghost"
                                size="icon"
                                :as-child="true"
                                class="h-7 w-7"
                                :title="'Voir'"
                            >
                                <Link :href="`/customers/${customer.id}`">
                                    <Eye class="size-3.5 text-slate-600 dark:text-slate-400" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                :as-child="true"
                                class="h-7 w-7"
                                :title="'Modifier'"
                            >
                                <Link :href="`/customers/${customer.id}/edit`">
                                    <Pencil class="size-3.5 text-slate-600 dark:text-slate-400" />
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-7 w-7 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                :title="'Supprimer'"
                                @click="handleDelete(customer.id)"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-col items-center justify-center py-8 text-center"
            >
                <User class="size-10 text-slate-400 mb-3" />
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-1.5">
                    Aucun client trouvé
                </h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mb-4">
                    {{
                        props.filters.search
                            ? 'Aucun client ne correspond à votre recherche.'
                            : 'Commencez par ajouter votre premier client.'
                    }}
                </p>
                <Dialog v-model:open="isDialogOpen">
                    <DialogTrigger as-child>
                        <Button class="bg-blue-600 hover:bg-blue-700 text-white">
                            <Plus class="size-4" />
                            <span>Ajouter un client</span>
                        </Button>
                    </DialogTrigger>
                    <DialogContent 
                        class="sm:max-w-[550px] !z-[60] p-0 overflow-hidden"
                    >
                        <DialogHeader class="px-6 pt-6 pb-4">
                            <DialogTitle class="text-xl font-semibold">Nouveau Client</DialogTitle>
                            <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                Ajoutez un nouveau client à votre liste. Remplissez les informations ci-dessous.
                            </DialogDescription>
                        </DialogHeader>
                        <Separator />
                        <form id="customer-form-empty" @submit.prevent="handleCreateCustomer" class="px-6 py-4 space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="first_name_empty" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Prénom <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="first_name_empty"
                                        v-model="customerForm.first_name"
                                        type="text"
                                        placeholder="Prénom"
                                        required
                                        class="h-10"
                                        :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.first_name }"
                                    />
                                    <p v-if="customerForm.errors.first_name" class="text-xs text-red-500 mt-1">
                                        {{ customerForm.errors.first_name }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="last_name_empty" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Nom <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="last_name_empty"
                                        v-model="customerForm.last_name"
                                        type="text"
                                        placeholder="Nom"
                                        required
                                        class="h-10"
                                        :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.last_name }"
                                    />
                                    <p v-if="customerForm.errors.last_name" class="text-xs text-red-500 mt-1">
                                        {{ customerForm.errors.last_name }}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label for="email_empty" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Email
                                </Label>
                                <Input
                                    id="email_empty"
                                    v-model="customerForm.email"
                                    type="email"
                                    placeholder="email@example.com"
                                    class="h-10"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.email }"
                                />
                                <p v-if="customerForm.errors.email" class="text-xs text-red-500 mt-1">
                                    {{ customerForm.errors.email }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="phone_empty" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Téléphone
                                </Label>
                                <Input
                                    id="phone_empty"
                                    v-model="customerForm.phone"
                                    type="tel"
                                    placeholder="+33 6 12 34 56 78"
                                    class="h-10"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.phone }"
                                />
                                <p v-if="customerForm.errors.phone" class="text-xs text-red-500 mt-1">
                                    {{ customerForm.errors.phone }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2 pt-2">
                                <Checkbox
                                    id="is_active_empty"
                                    :checked="customerForm.is_active"
                                    @update:checked="(checked: boolean) => customerForm.is_active = checked"
                                />
                                <Label
                                    for="is_active_empty"
                                    class="text-sm font-normal cursor-pointer text-slate-700 dark:text-slate-300"
                                >
                                    Client actif
                                </Label>
                            </div>
                        </form>
                        <Separator />
                        <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                            <Button
                                type="button"
                                variant="outline"
                                class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 dark:hover:border-slate-600 transition-all duration-200"
                                @click="isDialogOpen = false"
                            >
                                Annuler
                            </Button>
                            <Button
                                type="submit"
                                form="customer-form-empty"
                                class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md"
                                :disabled="customerForm.processing"
                            >
                                {{ customerForm.processing ? 'Création...' : 'Créer le client' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>


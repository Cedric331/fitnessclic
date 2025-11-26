<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, XCircle, Plus, Search, Pencil, Trash2 } from 'lucide-vue-next';
import type { BreadcrumbItemType } from '@/types';
import { computed, ref, watch } from 'vue';

interface Category {
    id: number;
    name: string;
    type: 'private' | 'public';
}

interface Filters {
    search?: string | null;
    show_private: boolean;
    show_public: boolean;
}

const props = defineProps<{
    privateCategories: Category[];
    publicCategories: Category[];
    filters: Filters;
}>();

const breadcrumbs: BreadcrumbItemType[] = [
    {
        title: 'Catégories',
        href: '/categories',
    },
];

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

const searchForm = useForm({
    search: props.filters?.search ?? '',
});

const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const editingCategory = ref<Category | null>(null);
const isDeleteDialogOpen = ref(false);
const deletingCategory = ref<Category | null>(null);
const deleteProcessing = ref(false);
const createForm = useForm({
    name: '',
});
const editForm = useForm({
    name: '',
});
const createFormId = `category-create-${Math.random()
    .toString(36)
    .slice(2, 8)}`;
const editFormId = `category-edit-${Math.random()
    .toString(36)
    .slice(2, 8)}`;

watch(
    () => props.filters,
    (filters) => {
        searchForm.search = filters?.search ?? '';
    },
    { immediate: true, deep: true },
);

watch(isEditDialogOpen, (open) => {
    if (!open) {
        editingCategory.value = null;
        editForm.reset();
    }
});

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        deletingCategory.value = null;
        deleteProcessing.value = false;
    }
});

const applyFilters = () => {
    const trimmedSearch = searchForm.search?.trim() ?? '';
    const query: Record<string, string> = {};

    if (trimmedSearch.length) {
        query.search = trimmedSearch;
    }

    router.get('/categories', query, {
        preserveScroll: true,
        preserveState: true,
    });
};

const handleCreateCategory = () => {
    createForm.post('/categories', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
        },
    });
};

const startEditCategory = (category: Category) => {
    editingCategory.value = category;
    editForm.reset();
    editForm.name = category.name;
    isEditDialogOpen.value = true;
};

const handleUpdateCategory = () => {
    if (!editingCategory.value) {
        return;
    }

    editForm.patch(`/categories/${editingCategory.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
            editingCategory.value = null;
            editForm.reset();
        },
    });
};

const startDeleteCategory = (category: Category) => {
    deletingCategory.value = category;
    isDeleteDialogOpen.value = true;
};

const handleDeleteCategory = () => {
    if (!deletingCategory.value) {
        return;
    }

    deleteProcessing.value = true;

    router.delete(`/categories/${deletingCategory.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            deletingCategory.value = null;
        },
        onFinish: () => {
            deleteProcessing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Gérer les catégories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 rounded-xl px-6 py-5">
            <div class="flex flex-col gap-1">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">
                    Gestion
                </p>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Gérer les catégories</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Classez vos types de séances privés et découvrez les catégories publiques validées par l’administration.
                </p>
            </div>

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

            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <Dialog v-model:open="isCreateDialogOpen">
                    <DialogTrigger as-child>
                        <Button
                            size="lg"
                            class="flex items-center gap-2 bg-blue-600 text-white hover:bg-blue-700 focus-visible:ring-blue-400"
                        >
                            <Plus class="size-4" />
                            <span>Nouvelle catégorie</span>
                        </Button>
                    </DialogTrigger>
                </Dialog>
            </div>

            <form @submit.prevent="applyFilters" class="relative">
                <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-300" />
                <Input
                    v-model="searchForm.search"
                    id="category-search"
                    type="text"
                    placeholder="Rechercher une catégorie..."
                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-slate-900 shadow-sm transition focus:border-blue-500 focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                    @keyup.enter="applyFilters"
                />
            </form>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                    <CardHeader class="flex items-center justify-between gap-4">
                        <div>
                            <CardTitle class="text-slate-900 dark:text-white">Catégories privées</CardTitle>
                            <CardDescription class="text-slate-500 dark:text-slate-400 mt-2">
                                Vos catégories privées.
                            </CardDescription>
                        </div>
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                            {{ privateCategories.length }} catégorie<span v-if="privateCategories.length > 1">s</span>
                        </span>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <template v-if="privateCategories.length">
                            <article
                                v-for="category in privateCategories"
                                :key="category.id"
                                class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 text-slate-900 transition hover:border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 dark:text-white"
                            >
                                <div>
                                    <p class="text-base font-semibold">{{ category.name }}</p>
                                    <p class="text-[0.65rem] uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                                        Privée
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
                                        aria-label="Modifier la catégorie"
                                        @click="startEditCategory(category)"
                                    >
                                        <Pencil class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-red-300 hover:bg-red-50 focus-visible:ring-2 focus-visible:ring-red-500 dark:border-slate-700 dark:text-slate-200 dark:hover:border-red-500 dark:hover:bg-red-900"
                                        aria-label="Supprimer la catégorie"
                                        @click="startDeleteCategory(category)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </div>
                            </article>
                        </template>
                        <p v-else class="text-sm text-slate-500 dark:text-slate-400">
                            Vous n’avez pas encore de catégories privées.
                        </p>
                    </CardContent>
                </Card>

                <Card class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                    <CardHeader class="flex items-center justify-between gap-4">
                        <div>
                            <CardTitle class="text-slate-900 dark:text-white">Catégories publiques</CardTitle>
                            <CardDescription class="text-slate-500 dark:text-slate-400 mt-2">
                                Validées par l’administration, elles sont accessibles à tous.
                            </CardDescription>
                        </div>
                        <span class="text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                            {{ publicCategories.length }} catégorie<span v-if="publicCategories.length > 1">s</span>
                        </span>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <template v-if="publicCategories.length">
                            <article
                                v-for="category in publicCategories"
                                :key="category.id"
                                class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 text-slate-900 dark:border-slate-800 dark:bg-slate-900/50 dark:text-white"
                            >
                                <div>
                                    <p class="text-base font-semibold">{{ category.name }}</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Publique</p>
                                </div>
                                <Badge variant="outline" class="text-xs uppercase tracking-[0.3em]">
                                    Publique
                                </Badge>
                            </article>
                        </template>
                        <p v-else class="text-sm text-slate-500 dark:text-slate-400">
                            Aucune catégorie publique ne répond au filtre.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="isEditDialogOpen">
            <DialogContent class="sm:max-w-[500px] !z-[60] p-0 overflow-hidden">
                <DialogHeader class="px-6 pt-6 pb-4">
                    <DialogTitle class="text-xl font-semibold">Modifier une catégorie</DialogTitle>
                    <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        Ajustez le nom de la catégorie privée.
                    </DialogDescription>
                </DialogHeader>
                <form :id="editFormId" @submit.prevent="handleUpdateCategory" class="px-6 py-4 space-y-4">
                    <div class="space-y-2">
                        <label :for="`${editFormId}-name`" class="text-sm font-medium text-slate-700">
                            Nom de la catégorie <span class="text-red-500">*</span>
                        </label>
                        <Input
                            :id="`${editFormId}-name`"
                            v-model="editForm.name"
                            type="text"
                            placeholder="Nom de la catégorie"
                            required
                            class="h-10"
                            :class="{
                                'border-destructive focus-visible:ring-destructive': editForm.errors.name,
                            }"
                        />
                        <p v-if="editForm.errors.name" class="text-xs text-red-500">
                            {{ editForm.errors.name }}
                        </p>
                    </div>
                </form>
                <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                    <Button
                        type="button"
                        variant="outline"
                        class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 transition-all duration-200"
                        @click="isEditDialogOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        :form="editFormId"
                        class="bg-blue-600 hover:bg-blue-700 text-white"
                        :disabled="editForm.processing"
                    >
                        {{ editForm.processing ? 'Enregistrement...' : 'Sauvegarder' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent class="sm:max-w-[450px] !z-[60] p-0 overflow-hidden">
                <DialogHeader class="px-6 pt-6 pb-4">
                    <DialogTitle class="text-xl font-semibold">Supprimer la catégorie</DialogTitle>
                    <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        Voulez-vous vraiment supprimer
                        <strong>{{ deletingCategory?.name ?? 'cette catégorie' }}</strong> ?
                        Cette action est irréversible.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                    <Button
                        type="button"
                        variant="outline"
                        class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 transition-all duration-200"
                        @click="isDeleteDialogOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        class="text-white"
                        :disabled="deleteProcessing"
                        @click="handleDeleteCategory"
                    >
                        {{ deleteProcessing ? 'Suppression...' : 'Supprimer' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>


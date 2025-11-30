<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Search, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { BreadcrumbItemType } from '@/types';
import CategoryCreateDialog from './CategoryCreateDialog.vue';
import CategoryEditDialog from './CategoryEditDialog.vue';
import CategoryDeleteDialog from './CategoryDeleteDialog.vue';
import CategorySection from './CategorySection.vue';
import type { Category, Filters } from './types';

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
    }
});

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        deletingCategory.value = null;
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

const startEditCategory = (category: Category) => {
    editingCategory.value = category;
    isEditDialogOpen.value = true;
};

const startDeleteCategory = (category: Category) => {
    deletingCategory.value = category;
    isDeleteDialogOpen.value = true;
};
</script>

<template>
    <Head title="Gérer les catégories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 rounded-xl px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex flex-col gap-0.5">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">
                        Gestion
                    </p>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Gérer les catégories</h1>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Classez vos types de séances privés et découvrez les catégories publiques validées par
                        l’administration.
                    </p>
                </div>
                <CategoryCreateDialog v-model:open="isCreateDialogOpen" triggerLabel="Nouvelle catégorie" />
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
                <CategorySection
                    title="Catégories privées"
                    description="Vos catégories privées."
                    type="private"
                    :categories="privateCategories"
                    @edit="startEditCategory"
                    @delete="startDeleteCategory"
                />
                <CategorySection
                    title="Catégories publiques"
                    description="Validées par l’administration, elles sont accessibles à tous."
                    type="public"
                    :categories="publicCategories"
                />
            </div>

            <CategoryEditDialog v-model:open="isEditDialogOpen" :category="editingCategory" />
            <CategoryDeleteDialog v-model:open="isDeleteDialogOpen" :category="deletingCategory" />
        </div>
    </AppLayout>
</template>


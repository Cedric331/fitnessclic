<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch, nextTick } from 'vue';
import type { BreadcrumbItemType } from '@/types';
import CategoryCreateDialog from './CategoryCreateDialog.vue';
import CategoryEditDialog from './CategoryEditDialog.vue';
import CategoryDeleteDialog from './CategoryDeleteDialog.vue';
import CategorySection from './CategorySection.vue';
import UpgradeModal from '@/components/UpgradeModal.vue';
import type { Category, Filters } from './types';
import { useNotifications } from '@/composables/useNotifications';

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
const { success: notifySuccess, error: notifyError } = useNotifications();

// Vérifier si l'utilisateur est Pro
const isPro = computed(() => (page.props.auth as any)?.user?.isPro ?? false);
const isUpgradeModalOpen = ref(false);

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

const searchTerm = ref(props.filters?.search ?? '');

const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const editingCategory = ref<Category | null>(null);
const isDeleteDialogOpen = ref(false);
const deletingCategory = ref<Category | null>(null);

// Synchroniser searchTerm avec les props
watch(() => props.filters?.search, (value) => {
    searchTerm.value = value ?? '';
});

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
    const trimmedSearch = searchTerm.value?.trim() ?? '';
    const query: Record<string, string> = {};

    if (trimmedSearch.length) {
        query.search = trimmedSearch;
    }

    router.get('/categories', query, {
        preserveScroll: true,
        preserveState: true,
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

const startEditCategory = (category: Category) => {
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }
    editingCategory.value = category;
    isEditDialogOpen.value = true;
};

const startDeleteCategory = (category: Category) => {
    if (!isPro.value) {
        isUpgradeModalOpen.value = true;
        return;
    }
    deletingCategory.value = category;
    isDeleteDialogOpen.value = true;
};
</script>

<template>
    <Head title="Gérer les catégories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-full w-full flex-1 flex-col gap-6 rounded-xl px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex flex-col gap-0.5">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">
                        Gestion
                    </p>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Gérer les catégories</h1>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Gérez vos catégories pour organiser vos exercices
                    </p>
                </div>
                <CategoryCreateDialog
                    v-if="isPro"
                    v-model:open="isCreateDialogOpen"
                    triggerLabel="Nouvelle catégorie"
                />
                <Button
                    v-else
                    @click="isUpgradeModalOpen = true"
                    class="bg-blue-600 hover:bg-blue-700 text-white"
                >
                    Nouvelle catégorie
                </Button>
            </div>

            <div class="relative">
                <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-300" />
                <Input
                    v-model="searchTerm"
                    id="category-search"
                    type="text"
                    placeholder="Rechercher une catégorie..."
                    class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-slate-900 shadow-sm transition focus:border-blue-500 focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                />
            </div>

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
            <UpgradeModal
                v-model:open="isUpgradeModalOpen"
                feature="La gestion des catégories (création, modification, suppression) est réservée aux abonnés Pro."
            />
        </div>
    </AppLayout>
</template>


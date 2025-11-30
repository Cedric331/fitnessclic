<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { LayoutGrid, List, Search } from 'lucide-vue-next';
import { computed, ref, watch, watchEffect } from 'vue';

const props = defineProps<{
    search?: string | null;
    categoryId?: number | null;
    sortOrder?: 'newest' | 'oldest';
    viewMode?: 'grid-1' | 'grid-2' | 'grid-4' | 'list';
    categories: Array<{
        id: number;
        name: string;
    }>;
}>();

const emit = defineEmits<{
    (e: 'update:search', value: string): void;
    (e: 'update:categoryId', value: number | null): void;
    (e: 'update:sortOrder', value: 'newest' | 'oldest'): void;
    (e: 'update:viewMode', value: 'grid-1' | 'grid-2' | 'grid-4' | 'list'): void;
    (e: 'apply'): void;
}>();

const localSearch = ref(props.search ?? '');
const localCategory = ref(props.categoryId !== undefined && props.categoryId !== null ? String(props.categoryId) : '');
const localSort = ref(props.sortOrder ?? 'newest');
const localView = ref(props.viewMode ?? 'grid-4');

// Synchroniser avec les props
watch(() => props.search, (value) => {
    const newValue = value ?? '';
    if (localSearch.value !== newValue) {
        localSearch.value = newValue;
    }
});

watch(() => props.categoryId, (value) => {
    const newValue = value !== undefined && value !== null ? String(value) : '';
    // Toujours mettre à jour pour garantir la synchronisation
    localCategory.value = newValue;
}, { immediate: true, flush: 'post' });

watch(() => props.sortOrder, (value) => {
    const newValue = value ?? 'newest';
    if (localSort.value !== newValue) {
        localSort.value = newValue;
    }
}, { immediate: true });

watch(() => props.viewMode, (value) => {
    if (value && localView.value !== value) {
        localView.value = value;
    }
}, { immediate: true });

const isGrid1 = computed(() => localView.value === 'grid-1');
const isGrid2 = computed(() => localView.value === 'grid-2');
const isGrid4 = computed(() => localView.value === 'grid-4');
const isList = computed(() => localView.value === 'list');

// Debounce pour la recherche
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const handleSearchInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    localSearch.value = target.value;
    
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    searchTimeout = setTimeout(() => {
        emit('update:search', localSearch.value.trim());
        emit('apply');
    }, 500);
};

const handleCategoryChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    localCategory.value = target.value;
    const parsedCategory = target.value ? Number(target.value) : null;
    emit('update:categoryId', parsedCategory);
    emit('apply');
};

const handleSortChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    localSort.value = target.value as 'newest' | 'oldest';
    emit('update:sortOrder', localSort.value);
    emit('apply');
};

const handleViewChange = (mode: 'grid-1' | 'grid-2' | 'grid-4' | 'list') => {
    localView.value = mode;
    emit('update:viewMode', mode);
    // Le mode d'affichage ne nécessite pas de rechargement
};
</script>

<template>
    <div class="w-full space-y-4">
        <!-- Barre de recherche - toujours pleine largeur -->
        <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                Rechercher
            </label>
            <div class="relative">
                <Search class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 size-5 text-slate-400 dark:text-slate-300" />
                <Input
                    :model-value="localSearch"
                    type="text"
                    placeholder="Rechercher un exercice..."
                    class="h-10 w-full rounded-2xl border border-slate-200 bg-white pl-10 pr-4 text-slate-900 shadow-sm transition focus:border-blue-500 focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                    @input="handleSearchInput"
                />
            </div>
        </div>

        <!-- Filtres - disposition responsive -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4 lg:grid-cols-5 lg:items-end">
            <!-- Catégorie -->
            <div class="flex flex-col gap-1.5 sm:col-span-4 lg:col-span-2">
                <label class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                    Catégorie
                </label>
                <select
                    :key="`category-${props.categoryId ?? 'all'}`"
                    :value="localCategory"
                    class="h-10 w-full rounded-2xl border border-slate-200 bg-white px-3 text-sm text-slate-700 transition focus:border-blue-500 focus:outline-none focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                    @change="handleCategoryChange"
                >
                    <option value="">Toutes les catégories</option>
                    <option v-for="category in props.categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <!-- Trier -->
            <div class="flex flex-col gap-1.5 sm:col-span-2 lg:col-span-2">
                <label class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                    Trier
                </label>
                <select
                    :value="localSort"
                    class="h-10 w-full rounded-2xl border border-slate-200 bg-white px-3 text-sm text-slate-700 transition focus:border-blue-500 focus:outline-none focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                    @change="handleSortChange"
                >
                    <option value="newest">Plus récents</option>
                    <option value="oldest">Plus anciens</option>
                </select>
            </div>

            <!-- Affichage - caché sur mobile, visible à partir de sm -->
            <div class="hidden flex-col gap-1.5 sm:flex sm:w-auto sm:justify-self-end lg:justify-self-start">
                <label class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                    Affichage
                </label>
                <div class="flex items-center gap-1.5 rounded-2xl border border-slate-200 bg-white py-1.5 px-2.5 dark:border-slate-800 dark:bg-slate-900/70">
                    <button
                        type="button"
                        aria-label="2 exercices par ligne"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition"
                        :class="{
                            'bg-slate-900 text-white dark:bg-white dark:text-slate-900': isGrid2,
                            'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white': !isGrid2,
                        }"
                        @click="handleViewChange('grid-2')"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        aria-label="4 exercices par ligne"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition"
                        :class="{
                            'bg-slate-900 text-white dark:bg-white dark:text-slate-900': isGrid4,
                            'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white': !isGrid4,
                        }"
                        @click="handleViewChange('grid-4')"
                    >
                        <LayoutGrid class="size-4" />
                    </button>
                    <button
                        type="button"
                        aria-label="Vue liste"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition"
                        :class="{
                            'bg-slate-900 text-white dark:bg-white dark:text-slate-900': isList,
                            'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white': !isList,
                        }"
                        @click="handleViewChange('list')"
                    >
                        <List class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

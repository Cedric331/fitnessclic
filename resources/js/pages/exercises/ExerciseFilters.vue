<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { LayoutGrid, Search } from 'lucide-vue-next';
import { computed, ref, watch, watchEffect } from 'vue';

const props = defineProps<{
    search?: string | null;
    categoryId?: number | null;
    sortOrder?: 'newest' | 'oldest';
    viewMode?: 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8';
    categories: Array<{
        id: number;
        name: string;
    }>;
}>();

const emit = defineEmits<{
    (e: 'update:search', value: string): void;
    (e: 'update:categoryId', value: number | null): void;
    (e: 'update:sortOrder', value: 'newest' | 'oldest'): void;
    (e: 'update:viewMode', value: 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8'): void;
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

const isGrid2 = computed(() => localView.value === 'grid-2');
const isGrid4 = computed(() => localView.value === 'grid-4');
const isGrid6 = computed(() => localView.value === 'grid-6');
const isGrid8 = computed(() => localView.value === 'grid-8');

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

const handleViewChange = (mode: 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8') => {
    localView.value = mode;
    emit('update:viewMode', mode);
    // Le mode d'affichage ne nécessite pas de rechargement
};
</script>

<template>
    <div class="w-full space-y-4 lg:space-y-0">
        <!-- Filtres - disposition responsive : empilés sur mobile/tablette, sur une ligne en lg -->
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:gap-3">
            <!-- Barre de recherche -->
            <div class="flex flex-col gap-1.5 lg:flex-1">
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

            <!-- Catégorie -->
            <div class="flex flex-col gap-1.5 lg:flex-1">
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
            <div class="flex flex-col gap-1.5 lg:flex-1">
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
            <div class="hidden flex-col gap-1.5 md:flex w-auto">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h8v8H4V4zm8 0h8v8h-8V4zM4 12h8v8H4v-8zm8 0h8v8h-8v-8z" />
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
                        aria-label="6 exercices par ligne"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition"
                        :class="{
                            'bg-slate-900 text-white dark:bg-white dark:text-slate-900': isGrid6,
                            'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white': !isGrid6,
                        }"
                        @click="handleViewChange('grid-6')"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4zM4 16h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4z" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        aria-label="8 exercices par ligne"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition"
                        :class="{
                            'bg-slate-900 text-white dark:bg-white dark:text-slate-900': isGrid8,
                            'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white': !isGrid8,
                        }"
                        @click="handleViewChange('grid-8')"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h4v4H3V3zm5 0h4v4H8V3zm5 0h4v4h-4V3zm5 0h4v4h-4V3zM3 8h4v4H3V8zm5 0h4v4H8V8zm5 0h4v4h-4V8zm5 0h4v4h-4V8zM3 13h4v4H3v-4zm5 0h4v4H8v-4zm5 0h4v4h-4v-4zm5 0h4v4h-4v-4zM3 18h4v4H3v-4zm5 0h4v4H8v-4zm5 0h4v4h-4v-4zm5 0h4v4h-4v-4z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Pencil, Trash2 } from 'lucide-vue-next';
import type { Category } from './types';

const props = defineProps<{
    title: string;
    description: string;
    categories: Category[];
    type: 'private' | 'public';
}>();

const emit = defineEmits<{
    edit: (category: Category) => void;
    delete: (category: Category) => void;
}>();
</script>

<template>
    <Card class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
        <CardHeader class="flex items-center justify-between gap-4">
            <div>
                <CardTitle class="text-slate-900 dark:text-white">{{ title }}</CardTitle>
                <CardDescription class="text-slate-500 dark:text-slate-400 mt-2">
                    {{ description }}
                </CardDescription>
            </div>
            <span class="text-xs uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                {{ categories.length }} catégorie<span v-if="categories.length > 1">s</span>
            </span>
        </CardHeader>
        <CardContent class="space-y-3">
            <div class="max-h-[500px] overflow-y-auto pr-2 space-y-3">
                <template v-if="categories.length">
                    <article
                        v-for="category in categories"
                        :key="category.id"
                        class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 text-slate-900 transition hover:border-slate-200 dark:border-slate-800 dark:bg-slate-900/50 dark:text-white"
                    >
                        <div>
                            <p class="text-base font-semibold">{{ category.name }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="type === 'private'"
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
                                aria-label="Modifier la catégorie"
                                @click="emit('edit', category)"
                            >
                                <Pencil class="size-4" />
                            </button>
                            <button
                                v-if="type === 'private'"
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-red-300 hover:bg-red-50 focus-visible:ring-2 focus-visible:ring-red-500 dark:border-slate-700 dark:text-slate-200 dark:hover:border-red-500 dark:hover:bg-red-900"
                                aria-label="Supprimer la catégorie"
                                @click="emit('delete', category)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                            <Badge
                                v-else
                                variant="outline"
                                class="text-xs uppercase tracking-[0.3em]"
                            >
                                Publique
                            </Badge>
                        </div>
                    </article>
                </template>
                <p v-else class="text-sm text-slate-500 dark:text-slate-400">
                    <span v-if="type === 'private'">Vous n'avez pas encore de catégories privées.</span>
                    <span v-else> Aucune catégorie publique ne répond au filtre.</span>
                </p>
            </div>
        </CardContent>
    </Card>
</template>


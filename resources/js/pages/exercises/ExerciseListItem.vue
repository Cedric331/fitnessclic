<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { Exercise } from './types';
import { computed } from 'vue';

const props = defineProps<{
    exercise: Exercise;
}>();

const formattedDate = computed(() => {
    if (!props.exercise.created_at) {
        return '';
    }

    return new Date(props.exercise.created_at).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
});
</script>

<template>
    <article
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all hover:border-slate-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/60 dark:hover:border-slate-700"
    >
        <!-- Image -->
        <div class="relative aspect-video w-full overflow-hidden">
            <img
                :src="exercise.image_url"
                :alt="exercise.name"
                class="h-full w-full object-cover transition-transform group-hover:scale-105"
            />
        </div>

        <!-- Contenu principal -->
        <div class="flex flex-1 flex-col gap-2 p-3 sm:gap-2.5 sm:p-4">
            <!-- Titre et badge -->
            <div class="flex flex-col gap-1.5">
                <h3
                    class="line-clamp-2 text-sm font-semibold leading-tight text-slate-900 dark:text-white sm:text-base"
                >
                    {{ exercise.name }}
                </h3>
                <Badge
                    variant="outline"
                    class="w-fit text-xs uppercase tracking-[0.3em]"
                >
                    {{ exercise.category_name }}
                </Badge>
            </div>

            <!-- Date et action -->
            <div class="mt-auto flex items-center justify-between gap-2">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ formattedDate }}
                </p>
                
                <button
                    type="button"
                    class="shrink-0 text-xs font-medium text-slate-600 transition-colors hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                >
                    Voir →
                </button>
            </div>
        </div>
    </article>
</template>


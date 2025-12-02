<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Exercise } from './types';
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Edit, Eye, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    exercise: Exercise;
    onEdit?: (exercise: Exercise) => void;
}>();

const emit = defineEmits<{
    edit: [exercise: Exercise];
    delete: [exercise: Exercise];
}>();

const page = usePage();
const canEdit = computed(() => {
    const user = (page.props as any).auth?.user;
    return user && (user.id === props.exercise.user_id || user.role === 'admin');
});

const canDelete = computed(() => {
    const user = (page.props as any).auth?.user;
    return user && (user.id === props.exercise.user_id || user.role === 'admin');
});

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

const handleView = () => {
    router.visit(`/exercises/${props.exercise.id}`);
};

const handleEdit = (event: Event) => {
    event.stopPropagation();
    if (props.onEdit) {
        props.onEdit(props.exercise);
    } else {
        emit('edit', props.exercise);
    }
};

const handleDelete = (event: Event) => {
    event.stopPropagation();
    emit('delete', props.exercise);
};
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
                <div class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="category in (exercise.categories || [])"
                        :key="category.id"
                        variant="outline"
                        class="text-xs uppercase tracking-[0.3em]"
                    >
                        {{ category.name }}
                    </Badge>
                    <Badge
                        v-if="!exercise.categories || exercise.categories.length === 0"
                        variant="outline"
                        class="text-xs uppercase tracking-[0.3em]"
                    >
                        {{ exercise.category_name || 'Sans catégorie' }}
                    </Badge>
                </div>
            </div>

            <!-- Date et actions -->
            <div class="mt-auto flex items-center justify-between gap-2">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ formattedDate }}
                </p>
                
                <div class="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs"
                        @click="handleView"
                    >
                        <Eye class="size-4 mr-1" />
                    </Button>
                    <Button
                        v-if="canEdit"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs"
                        @click="handleEdit"
                    >
                        <Edit class="size-4 mr-1" />
                    </Button>
                    <Button
                        v-if="canDelete"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                        @click="handleDelete"
                    >
                        <Trash2 class="size-4 mr-1" />
                    </Button>
                </div>
            </div>
        </div>
    </article>
</template>


<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Trash2 } from 'lucide-vue-next';
import type { Exercise } from './types';

interface Props {
    open?: boolean;
    exercise: Exercise | null;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    exercise: null,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = ref(props.open);
const deleteProcessing = ref(false);

watch(() => props.open, (newValue) => {
    isOpen.value = newValue;
}, { immediate: true });

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
    if (!newValue) {
        deleteProcessing.value = false;
    }
});

const handleDeleteExercise = () => {
    if (!props.exercise || deleteProcessing.value) {
        return;
    }

    deleteProcessing.value = true;

    router.delete(`/exercises/${props.exercise.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
        },
        onFinish: () => {
            deleteProcessing.value = false;
        },
    });
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[480px] !z-[60] p-0 overflow-hidden">
            <DialogHeader class="px-6 pt-6 pb-4">
                <DialogTitle class="text-xl font-semibold">Supprimer l'exercice</DialogTitle>
                <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Cette action est irréversible. L'exercice et toutes ses données associées seront définitivement supprimés.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <div class="px-6 py-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <p>
                    Souhaitez-vous vraiment supprimer l'exercice
                    <span class="font-semibold text-slate-900 dark:text-white">
                        {{ props.exercise?.name ?? 'cet exercice' }}
                    </span>
                    ?
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Cette action supprimera également l'exercice de toutes les sessions d'entraînement où il est utilisé.
                </p>
            </div>
            <Separator />
            <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="deleteProcessing"
                    class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 dark:hover:border-slate-600 transition-all duration-200"
                    @click="isOpen = false"
                >
                    Annuler
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    class="flex items-center justify-center gap-2"
                    :disabled="deleteProcessing"
                    @click="handleDeleteExercise"
                >
                    <Trash2 class="size-4" />
                    <span>{{ deleteProcessing ? 'Suppression...' : 'Supprimer' }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


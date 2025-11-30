<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { Category } from './types';

const props = withDefaults(
    defineProps<{
        open?: boolean;
        category: Category | null;
    }>(),
    {
        open: false,
    },
);

const emit = defineEmits<{
    'update:open': (value: boolean) => void;
}>();

const isOpen = ref(props.open);
const deleteProcessing = ref(false);

watch(
    () => props.open,
    (newValue) => {
        isOpen.value = newValue;
    },
);

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
    if (!newValue) {
        deleteProcessing.value = false;
    }
});

const handleDeleteCategory = () => {
    if (!props.category || deleteProcessing.value) {
        return;
    }

    deleteProcessing.value = true;

    router.delete(`/categories/${props.category.id}`, {
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
        <DialogContent class="sm:max-w-[450px] !z-[60] p-0 overflow-hidden">
            <DialogHeader class="px-6 pt-6 pb-4">
                <DialogTitle class="text-xl font-semibold">Supprimer la catégorie</DialogTitle>
                <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                    Voulez-vous vraiment supprimer
                    <strong class="text-slate-900 dark:text-white">{{ props.category?.name ?? 'cette catégorie' }}</strong> ?
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                    Cette action est irréversible.
                </p>
            </DialogHeader>
            <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                <Button
                    type="button"
                    variant="outline"
                    class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 transition-all duration-200"
                    @click="isOpen = false"
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
</template>


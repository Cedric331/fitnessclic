<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useForm } from '@inertiajs/vue3';
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

watch(
    () => props.open,
    (newValue) => {
        isOpen.value = newValue;
    },
);

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
});

const editForm = useForm({
    name: '',
});

const formId = `category-edit-${Math.random().toString(36).slice(2, 7)}`;

watch(
    () => props.category,
    (category) => {
        editForm.name = category?.name ?? '';
    },
    { immediate: true },
);

watch(isOpen, (open) => {
    if (!open) {
        editForm.reset();
    }
});

const handleUpdateCategory = () => {
    if (!props.category) {
        return;
    }

    editForm.patch(`/categories/${props.category.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            editForm.reset();
        },
    });
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[500px] !z-[60] p-0 overflow-hidden">
            <DialogHeader class="px-6 pt-6 pb-4">
                <DialogTitle class="text-xl font-semibold">Modifier une catégorie</DialogTitle>
            </DialogHeader>
            <form :id="formId" @submit.prevent="handleUpdateCategory" class="px-6 py-4 space-y-4">
                <div class="space-y-2">
                    <label :for="`${formId}-name`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Nom de la catégorie <span class="text-red-500">*</span>
                    </label>
                    <Input
                        :id="`${formId}-name`"
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
                    @click="isOpen = false"
                >
                    Annuler
                </Button>
                <Button
                    type="submit"
                    :form="formId"
                    class="bg-blue-600 hover:bg-blue-700 text-white"
                    :disabled="editForm.processing || !props.category"
                >
                    {{ editForm.processing ? 'Enregistrement...' : 'Sauvegarder' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


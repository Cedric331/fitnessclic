<script setup lang="ts">
import { computed, ref, watch } from 'vue';
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
import { Trash2 } from 'lucide-vue-next';
import type { Customer } from './types';

interface Props {
    open: boolean;
    customer: Customer | null;
    loading?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();

const isOpen = ref(props.open);

watch(() => props.open, (value) => {
    isOpen.value = value;
});

watch(isOpen, (value) => {
    emit('update:open', value);
});

const customerName = computed(() => {
    if (!props.customer) {
        return 'ce client';
    }

    return `${props.customer.first_name} ${props.customer.last_name}`;
});

const isLoading = computed(() => Boolean(props.loading));

const handleCancel = () => {
    if (isLoading.value) {
        return;
    }

    isOpen.value = false;
};

const handleConfirm = () => {
    if (isLoading.value) {
        return;
    }

    emit('confirm');
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[480px] !z-[60] p-0 overflow-hidden">
            <DialogHeader class="px-6 pt-6 pb-4">
                <DialogTitle class="text-xl font-semibold">Supprimer le client</DialogTitle>
                <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Cette action est irréversible. Toutes les données liées seront perdues.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <div class="px-6 py-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <p>
                    Souhaitez-vous vraiment supprimer
                    <span class="font-semibold text-slate-900 dark:text-white">
                        {{ customerName }}
                    </span>
                    ?
                </p>
            </div>
            <Separator />
            <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="isLoading"
                    @click="handleCancel"
                >
                    Annuler
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    class="flex items-center justify-center gap-2"
                    :disabled="isLoading"
                    @click="handleConfirm"
                >
                    <Trash2 class="size-4" />
                    <span>{{ isLoading ? 'Suppression...' : 'Supprimer' }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


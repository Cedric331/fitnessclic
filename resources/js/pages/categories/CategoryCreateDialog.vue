<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Plus } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useNotifications } from '@/composables/useNotifications';

const { error: notifyError } = useNotifications();

const props = withDefaults(
    defineProps<{
        open?: boolean;
        triggerLabel?: string;
    }>(),
    {
        open: false,
        triggerLabel: 'Nouvelle catégorie',
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

const createForm = useForm({
    name: '',
});

const formId = `category-create-${Math.random().toString(36).slice(2, 7)}`;

watch(isOpen, (open) => {
    if (!open) {
        createForm.reset();
    }
});

const handleCreateCategory = () => {
    createForm.post('/categories', {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            createForm.reset();
        },
        onError: (errors) => {
            // Afficher la première erreur via notification
            const firstError = Object.values(errors)[0];
            if (firstError && typeof firstError === 'string') {
                notifyError(firstError);
            } else if (Object.keys(errors).length > 0) {
                notifyError('Une erreur est survenue lors de la création de la catégorie.');
            }
        },
    });
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger as-child>
            <Button
                size="lg"
                class="flex items-center gap-2 bg-blue-600 text-white hover:bg-blue-700 focus-visible:ring-blue-400"
            >
                <Plus class="size-4" />
                <span>{{ triggerLabel }}</span>
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[500px] !z-[60] p-0 overflow-hidden">
            <DialogHeader class="px-6 pt-6 pb-4">
                <DialogTitle class="text-xl font-semibold">Nouvelle catégorie</DialogTitle>
                <DialogDescription class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                    Créez une nouvelle catégorie privée pour organiser vos séances.
                </DialogDescription>
            </DialogHeader>
            <form :id="formId" @submit.prevent="handleCreateCategory" class="px-6 py-4 space-y-4">
                <div class="space-y-2">
                    <label :for="`${formId}-name`" class="text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">
                        Nom de la catégorie <span class="text-red-500">*</span>
                    </label>
                    <Input
                        :id="`${formId}-name`"
                        v-model="createForm.name"
                        type="text"
                        placeholder="Nom de la catégorie"
                        required
                        class="h-10"
                        :class="{
                            'border-destructive focus-visible:ring-destructive': createForm.errors.name,
                        }"
                    />
                    <p v-if="createForm.errors.name" class="text-xs text-red-500">
                        {{ createForm.errors.name }}
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
                    :disabled="createForm.processing"
                >
                    {{ createForm.processing ? 'Création...' : 'Créer' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


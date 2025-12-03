<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
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
import { ref, watch } from 'vue';
import { useNotifications } from '@/composables/useNotifications';

const { error: notifyError } = useNotifications();

interface Props {
    open?: boolean;
    triggerLabel?: string;
    triggerVariant?: 'default' | 'sm';
    showTrigger?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    triggerLabel: 'Nouveau Client',
    triggerVariant: 'default',
    showTrigger: true,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = ref(props.open);

watch(() => props.open, (newValue) => {
    isOpen.value = newValue;
});

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
});

const customerForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    internal_note: '',
    is_active: true,
});

const handleCreateCustomer = () => {
    customerForm.post('/customers', {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            customerForm.reset();
            customerForm.is_active = true;
        },
        onError: (errors) => {
            // Afficher la première erreur via notification
            const firstError = Object.values(errors)[0];
            if (firstError && typeof firstError === 'string') {
                notifyError(firstError);
            } else if (Object.keys(errors).length > 0) {
                notifyError('Une erreur est survenue lors de la création du client.');
            }
        },
    });
};

const formId = `customer-form-${Math.random().toString(36).substr(2, 9)}`;
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogTrigger v-if="showTrigger" as-child>
            <Button 
                :size="triggerVariant === 'sm' ? 'sm' : 'default'"
                class="bg-blue-600 hover:bg-blue-700 text-white"
            >
                <Plus class="size-4" />
                <span>{{ triggerLabel }}</span>
            </Button>
        </DialogTrigger>
        <DialogContent 
            class="sm:max-w-[550px] !z-[60] p-0 overflow-hidden"
        >
            <DialogHeader class="px-6 pt-6 pb-4">
                <DialogTitle class="text-xl font-semibold">Nouveau Client</DialogTitle>
                <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Ajoutez un nouveau client à votre liste. Remplissez les informations ci-dessous.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <form :id="formId" @submit.prevent="handleCreateCustomer" class="px-6 py-4 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label :for="`first_name_${formId}`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Prénom <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            :id="`first_name_${formId}`"
                            v-model="customerForm.first_name"
                            type="text"
                            placeholder="Prénom"
                            required
                            class="h-10"
                            :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.first_name }"
                        />
                        <p v-if="customerForm.errors.first_name" class="text-xs text-red-500 mt-1">
                            {{ customerForm.errors.first_name }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label :for="`last_name_${formId}`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Nom <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            :id="`last_name_${formId}`"
                            v-model="customerForm.last_name"
                            type="text"
                            placeholder="Nom"
                            required
                            class="h-10"
                            :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.last_name }"
                        />
                        <p v-if="customerForm.errors.last_name" class="text-xs text-red-500 mt-1">
                            {{ customerForm.errors.last_name }}
                        </p>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label :for="`email_${formId}`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Email
                    </Label>
                    <Input
                        :id="`email_${formId}`"
                        v-model="customerForm.email"
                        type="email"
                        placeholder="email@example.com"
                        class="h-10"
                        :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.email }"
                    />
                    <p v-if="customerForm.errors.email" class="text-xs text-red-500 mt-1">
                        {{ customerForm.errors.email }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label :for="`phone_${formId}`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Téléphone
                    </Label>
                    <Input
                        :id="`phone_${formId}`"
                        v-model="customerForm.phone"
                        type="tel"
                        placeholder="+33 6 12 34 56 78"
                        class="h-10"
                        :class="{ 'border-red-500 focus-visible:ring-red-500': customerForm.errors.phone }"
                    />
                    <p v-if="customerForm.errors.phone" class="text-xs text-red-500 mt-1">
                        {{ customerForm.errors.phone }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label :for="`internal_note_${formId}`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Note interne
                    </Label>
                    <textarea
                        :id="`internal_note_${formId}`"
                        v-model="customerForm.internal_note"
                        rows="4"
                        placeholder="Ex : préfère les séances matinales, attention à la mobilité des épaules..."
                        class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition duration-150 ease-in-out placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white dark:placeholder:text-slate-500"
                    />
                    <p v-if="customerForm.errors.internal_note" class="text-xs text-red-500 mt-1">
                        {{ customerForm.errors.internal_note }}
                    </p>
                </div>
                <div class="flex items-center space-x-2 pt-2">
                    <Checkbox
                        :id="`is_active_${formId}`"
                        :model-value="customerForm.is_active"
                        @update:model-value="(checked: boolean) => customerForm.is_active = checked"
                    />
                    <Label
                        :for="`is_active_${formId}`"
                        class="text-sm font-normal cursor-pointer text-slate-700 dark:text-slate-300"
                    >
                        Client actif
                    </Label>
                </div>
            </form>
            <Separator />
            <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                <Button
                    type="button"
                    variant="outline"
                    class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 dark:hover:border-slate-600 transition-all duration-200"
                    @click="isOpen = false"
                >
                    Annuler
                </Button>
                <Button
                    type="submit"
                    :form="formId"
                    class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md"
                    :disabled="customerForm.processing"
                >
                    {{ customerForm.processing ? 'Création...' : 'Créer le client' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


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
} from '@/components/ui/dialog';
import { ref, watch, watchEffect } from 'vue';
import type { Customer } from './types';

interface Props {
    open: boolean;
    customer: Customer | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = ref(props.open);
const editIsActive = ref(true);

watch(() => props.open, (newValue) => {
    isOpen.value = newValue;
    if (newValue && props.customer) {
        initializeForm();
    }
});

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
});

const editCustomerForm = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    internal_note: '',
    is_active: true,
});

const initializeForm = () => {
    if (!props.customer) return;
    
    const isActive = Boolean(props.customer.is_active);
    editIsActive.value = isActive;
    
    editCustomerForm.reset();
    editCustomerForm.first_name = props.customer.first_name;
    editCustomerForm.last_name = props.customer.last_name;
    editCustomerForm.email = props.customer.email || '';
    editCustomerForm.phone = props.customer.phone || '';
    editCustomerForm.internal_note = props.customer.internal_note || '';
    editCustomerForm.is_active = isActive;
};

const handleUpdateCustomer = () => {
    if (!props.customer) return;
    
    editCustomerForm.put(`/customers/${props.customer.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            editCustomerForm.reset();
            editIsActive.value = true;
        },
    });
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent 
            class="sm:max-w-[550px] !z-[60] p-0 overflow-hidden"
        >
            <DialogHeader class="px-6 pt-6 pb-4">
                <DialogTitle class="text-xl font-semibold">Modifier le client</DialogTitle>
                <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Modifiez les informations du client ci-dessous.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            <form id="edit-customer-form" @submit.prevent="handleUpdateCustomer" class="px-6 py-4 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="edit_first_name" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Prénom <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="edit_first_name"
                            v-model="editCustomerForm.first_name"
                            type="text"
                            placeholder="Prénom"
                            required
                            class="h-10"
                            :class="{ 'border-red-500 focus-visible:ring-red-500': editCustomerForm.errors.first_name }"
                        />
                        <p v-if="editCustomerForm.errors.first_name" class="text-xs text-red-500 mt-1">
                            {{ editCustomerForm.errors.first_name }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_last_name" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Nom <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="edit_last_name"
                            v-model="editCustomerForm.last_name"
                            type="text"
                            placeholder="Nom"
                            required
                            class="h-10"
                            :class="{ 'border-red-500 focus-visible:ring-red-500': editCustomerForm.errors.last_name }"
                        />
                        <p v-if="editCustomerForm.errors.last_name" class="text-xs text-red-500 mt-1">
                            {{ editCustomerForm.errors.last_name }}
                        </p>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="edit_email" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Email
                    </Label>
                    <Input
                        id="edit_email"
                        v-model="editCustomerForm.email"
                        type="email"
                        placeholder="email@example.com"
                        class="h-10"
                        :class="{ 'border-red-500 focus-visible:ring-red-500': editCustomerForm.errors.email }"
                    />
                    <p v-if="editCustomerForm.errors.email" class="text-xs text-red-500 mt-1">
                        {{ editCustomerForm.errors.email }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label for="edit_phone" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Téléphone
                    </Label>
                    <Input
                        id="edit_phone"
                        v-model="editCustomerForm.phone"
                        type="tel"
                        placeholder="+33 6 12 34 56 78"
                        class="h-10"
                        :class="{ 'border-red-500 focus-visible:ring-red-500': editCustomerForm.errors.phone }"
                    />
                    <p v-if="editCustomerForm.errors.phone" class="text-xs text-red-500 mt-1">
                        {{ editCustomerForm.errors.phone }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label for="edit_internal_note" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Note interne
                    </Label>
                    <textarea
                        id="edit_internal_note"
                        v-model="editCustomerForm.internal_note"
                        rows="4"
                        placeholder="Ajoutez un commentaire que vous seuls verrez..."
                        class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition duration-150 ease-in-out placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white dark:placeholder:text-slate-500"
                    />
                    <p v-if="editCustomerForm.errors.internal_note" class="text-xs text-red-500 mt-1">
                        {{ editCustomerForm.errors.internal_note }}
                    </p>
                </div>
                <div class="flex items-center space-x-2 pt-2">
                    <Checkbox
                        id="edit_is_active"
                        :model-value="editIsActive"
                        @update:model-value="(checked: boolean) => {
                            editIsActive = checked;
                            editCustomerForm.is_active = checked;
                        }"
                    />
                    <Label
                        for="edit_is_active"
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
                    form="edit-customer-form"
                    class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md"
                    :disabled="editCustomerForm.processing"
                >
                    {{ editCustomerForm.processing ? 'Modification...' : 'Modifier le client' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


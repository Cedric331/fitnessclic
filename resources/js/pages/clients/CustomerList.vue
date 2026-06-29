<script setup lang="ts">
import { ref } from 'vue';
import CustomerCard from './CustomerCard.vue';
import CustomerCreateDialog from './CustomerCreateDialog.vue';
import { User } from 'lucide-vue-next';
import type { Customer } from './types';

interface Props {
    customers: Customer[];
    hasSearch: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    edit: [customer: Customer];
    delete: [customer: Customer];
}>();

const isCreateDialogOpen = ref(false);

const handleEdit = (customer: Customer) => {
    emit('edit', customer);
};

const handleDelete = (customer: Customer) => {
    emit('delete', customer);
};
</script>

<template>
    <!-- Clients Grid -->
    <div
        v-if="customers.length > 0"
        class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3"
    >
        <CustomerCard
            v-for="customer in customers"
            :key="customer.id"
            :customer="customer"
            @edit="handleEdit"
            @delete="handleDelete"
        />
    </div>

    <!-- Empty State -->
    <div
        v-else
        class="flex flex-col items-center justify-center py-8 text-center"
    >
        <User class="size-10 text-slate-400 mb-3" />
        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-1.5">
            Aucun client trouvé
        </h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 mb-4">
            {{
                hasSearch
                    ? 'Aucun client ne correspond à votre recherche.'
                    : 'Commencez par ajouter votre premier client.'
            }}
        </p>
        <CustomerCreateDialog
            v-model:open="isCreateDialogOpen"
            trigger-label="Ajouter un client"
            :show-trigger="true"
        />
    </div>
</template>


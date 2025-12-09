<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import CustomerCard from './CustomerCard.vue';
import CustomerCreateDialog from './CustomerCreateDialog.vue';
import UpgradeModal from '@/components/UpgradeModal.vue';
import { Button } from '@/components/ui/button';
import { User, Plus } from 'lucide-vue-next';
import type { Customer } from './types';

interface Props {
    customers: Customer[];
    hasSearch: boolean;
}

const props = defineProps<Props>();
const page = usePage();

// Vérifier si l'utilisateur est Pro
const isPro = computed(() => (page.props.auth as any)?.user?.isPro ?? false);
const isUpgradeModalOpen = ref(false);

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
            v-if="isPro"
            v-model:open="isCreateDialogOpen"
            trigger-label="Ajouter un client"
            :show-trigger="true"
        />
        <Button
            v-else
            class="bg-blue-600 hover:bg-blue-700 text-white"
            @click="isUpgradeModalOpen = true"
        >
            <Plus class="h-4 w-4 mr-2" />
            Ajouter un client
        </Button>
        <UpgradeModal
            v-model:open="isUpgradeModalOpen"
            feature="La création de clients est réservée aux abonnés Pro. Passez à Pro pour créer des clients illimités."
        />
    </div>
</template>


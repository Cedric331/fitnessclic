<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Eye, Pencil, Trash2 } from 'lucide-vue-next';
import { useInitials } from '@/composables/useInitials';
import type { Customer } from './types';

interface Props {
    customer: Customer;
}

const props = defineProps<Props>();
const { getInitials } = useInitials();

const emit = defineEmits<{
    edit: [customer: Customer];
    delete: [customer: Customer];
}>();

const getCustomerName = (customer: Customer): string => {
    return `${customer.first_name} ${customer.last_name.charAt(0).toUpperCase()}.`;
};

const handleEdit = () => {
    emit('edit', props.customer);
};

const handleDelete = () => {
    emit('delete', props.customer);
};
</script>

<template>
    <Card class="hover:shadow-md transition-shadow">
        <CardContent class="p-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3 flex-1">
                    <Avatar class="size-10 bg-slate-200 dark:bg-slate-700">
                        <AvatarFallback class="text-sm text-slate-700 dark:text-slate-300">
                            {{ getInitials(`${customer.first_name} ${customer.last_name}`) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                            {{ getCustomerName(customer) }}
                        </h3>
                        <div class="flex items-center gap-2">
                            <Badge
                                v-if="customer.is_active"
                                variant="default"
                                class="bg-blue-600 text-white border-blue-600 text-xs px-1.5 py-0"
                            >
                                Actif
                            </Badge>
                            <Badge
                                v-else
                                variant="secondary"
                                class="bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 text-xs px-1.5 py-0"
                            >
                                Inactif
                            </Badge>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            {{ customer.training_sessions_count }} programme{{
                                customer.training_sessions_count > 1 ? 's' : ''
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-1.5 mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                <Button
                    variant="ghost"
                    size="icon"
                    :as-child="true"
                    class="h-7 w-7"
                    :title="'Voir'"
                >
                    <Link :href="`/customers/${customer.id}`">
                        <Eye class="size-3.5 text-slate-600 dark:text-slate-400" />
                    </Link>
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7"
                    :title="'Modifier'"
                    @click="handleEdit"
                >
                    <Pencil class="size-3.5 text-slate-600 dark:text-slate-400" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                    :title="'Supprimer'"
                    @click="handleDelete"
                >
                    <Trash2 class="size-3.5" />
                </Button>
            </div>
        </CardContent>
    </Card>
</template>


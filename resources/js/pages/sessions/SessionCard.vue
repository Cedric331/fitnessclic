<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Eye, Pencil, Trash2, Send, Layout } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import type { Session } from './types';

interface Props {
    session: Session;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    delete: [session: Session];
    sendEmail: [session: Session];
}>();

const formatDate = (dateString?: string | null) => {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const handleEdit = () => {
    // Si la séance a un layout personnalisé OU si c'est une séance libre (sans exercices), ouvrir directement l'éditeur
    const isFreeSession = (props.session.exercises_count || 0) === 0;
    if (props.session.has_custom_layout || isFreeSession) {
        router.visit(`/sessions/${props.session.id}/edit?editor=true`);
    } else {
        router.visit(`/sessions/${props.session.id}/edit`);
    }
};

const handleDelete = () => {
    emit('delete', props.session);
};

const handleView = () => {
    router.visit(`/sessions/${props.session.id}`);
};

const handleSendEmail = () => {
    emit('sendEmail', props.session);
};

// Obtenir les premières images d'exercices
const exerciseImages = computed(() => {
    if (!props.session.exercises || props.session.exercises.length === 0) {
        return [];
    }
    return props.session.exercises
        .slice(0, 4)
        .map(ex => ex.image_url)
        .filter(Boolean);
});

const remainingExercises = computed(() => {
    const total = props.session.exercises_count || 0;
    return Math.max(0, total - 4);
});
</script>

<template>
    <Card class="hover:shadow-md transition-shadow">
        <CardContent class="p-4">
            <div class="flex flex-col gap-3">
                <!-- Header -->
                <div class="flex items-start justify-between">
                    <div class="flex flex-col gap-1 flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white truncate">
                            {{ session.name || 'Séance sans titre' }}
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            {{ formatDate(session.session_date || session.created_at) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Badge
                            v-if="session.has_custom_layout"
                            variant="default"
                            class="bg-blue-600 text-white text-xs px-2 py-0.5 flex items-center gap-1"
                            title="Séance libre"
                        >
                            <Layout class="size-3" />
                            Libre
                        </Badge>
                        <Badge
                            variant="secondary"
                            class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 text-xs px-2 py-0.5"
                        >
                            {{ session.exercises_count || 0 }} exercice{{ (session.exercises_count || 0) > 1 ? 's' : '' }}
                        </Badge>
                    </div>
                </div>

                <!-- Clients -->
                <div v-if="session.customers && session.customers.length > 0" class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="customer in session.customers"
                        :key="customer.id"
                        variant="outline"
                        class="text-xs"
                    >
                        {{ customer.full_name }}
                    </Badge>
                </div>

                <!-- Exercise Images Preview -->
                <div v-if="exerciseImages.length > 0" class="flex items-center gap-1.5">
                    <div
                        v-for="(image, index) in exerciseImages"
                        :key="index"
                        class="size-12 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800"
                    >
                        <img
                            v-if="image"
                            :src="image"
                            :alt="`Exercice ${index + 1}`"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    <div
                        v-if="remainingExercises > 0"
                        class="size-12 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 flex items-center justify-center"
                    >
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                            +{{ remainingExercises }}
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-1.5 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7"
                        :title="'Voir'"
                        @click="handleView"
                    >
                        <Eye class="size-3.5 text-slate-600 dark:text-slate-400" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7"
                        :title="'Envoyer par email'"
                        @click="handleSendEmail"
                    >
                        <Send class="size-3.5 text-slate-600 dark:text-slate-400" />
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
            </div>
        </CardContent>
    </Card>
</template>


<script setup lang="ts">
import SessionCard from './SessionCard.vue';
import { Calendar } from 'lucide-vue-next';
import type { Session } from './types';

interface Props {
    sessions: Session[];
    hasSearch: boolean;
    source?: 'my_sessions' | 'public_sessions';
}

const props = defineProps<Props>();

const emit = defineEmits<{
    delete: [session: Session];
    sendEmail: [session: Session];
    duplicate: [session: Session];
}>();

const handleDelete = (session: Session) => {
    emit('delete', session);
};

const handleSendEmail = (session: Session) => {
    emit('sendEmail', session);
};

const handleDuplicate = (session: Session) => {
    emit('duplicate', session);
};
</script>

<template>
    <!-- Sessions Grid -->
    <div
        v-if="sessions.length > 0"
        class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3"
    >
        <SessionCard
            v-for="session in sessions"
            :key="session.id"
            :session="session"
            :source="props.source"
            @delete="handleDelete"
            @send-email="handleSendEmail"
            @duplicate="handleDuplicate"
        />
    </div>

    <!-- Empty State -->
    <div
        v-else
        class="flex flex-col items-center justify-center py-12 text-center"
    >
        <Calendar class="size-12 text-slate-400 mb-4" />
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
            Aucune séance trouvée
        </h3>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 max-w-md">
            {{
                hasSearch
                    ? 'Aucune séance ne correspond à votre recherche.'
                    : 'Commencez par créer votre première séance d\'entraînement.'
            }}
        </p>
        <a
            v-if="!hasSearch"
            href="/sessions/create"
            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
        >
            Créer une séance
        </a>
    </div>
</template>


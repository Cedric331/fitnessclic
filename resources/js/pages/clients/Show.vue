<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import type { Customer, TrainingSessionHistory } from './types';

interface Props {
    customer: Customer;
    training_sessions: TrainingSessionHistory[];
}

const props = defineProps<Props>();

const pageTitle = computed(() => `${props.customer.first_name} ${props.customer.last_name}`);
const breadcrumbs = computed(() => [
    { title: 'Mes Clients', href: '/customers' },
    { title: pageTitle.value, href: `/customers/${props.customer.id}` },
]);

const sessionHistory = computed(() => props.training_sessions ?? []);
const totalSessions = computed(() => sessionHistory.value.length);
const lastSessionDate = computed(() => {
    const firstSession = sessionHistory.value[0];
    return firstSession?.session_date ?? firstSession?.created_at ?? null;
});

const formatDate = (value?: string | null) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatSessionDate = (session: TrainingSessionHistory) => {
    const dateValue = session.session_date ?? session.created_at;
    if (!dateValue) {
        return 'Date inconnue';
    }

    return formatDate(dateValue);
};
</script>

<template>
    <Head :title="`${pageTitle} • Client`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 pt-6 pb-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Profil client
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">
                            {{ pageTitle }}
                        </h1>
                        <Badge
                            :variant="props.customer.is_active ? 'default' : 'secondary'"
                            class="text-xs"
                        >
                            {{ props.customer.is_active ? 'Actif' : 'Inactif' }}
                        </Badge>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ props.customer.email || 'Email non renseigné' }}
                        <span v-if="props.customer.phone" class="mx-1">·</span>
                        {{ props.customer.phone || 'Téléphone non renseigné' }}
                    </p>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    class="inline-flex items-center gap-2"
                    @click="router.back()"
                >
                    <ArrowLeft class="size-4" />
                    <span>Retour</span>
                </Button>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardContent class="space-y-3">
                        <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">
                            Informations générales
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase text-slate-500">Email</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ props.customer.email || '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-slate-500">Téléphone</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ props.customer.phone || '—' }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs uppercase text-slate-500">Note interne</p>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ props.customer.internal_note || 'Aucune note enregistrée.' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="space-y-4">
                        <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">
                            Statistiques
                        </p>
                        <div class="grid gap-4">
                            <div>
                                <p class="text-xs text-slate-500">Séances enregistrées</p>
                                <p class="text-2xl font-semibold text-slate-900 dark:text-white">
                                    {{ totalSessions }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Dernière séance</p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    {{ lastSessionDate ? formatDate(lastSessionDate) : 'Pas encore de séance' }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                Historique des séances
                            </p>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ totalSessions }} séance{{ totalSessions > 1 ? 's' : '' }}
                            </h2>
                        </div>
                        <Badge variant="outline" class="text-xs text-slate-500">
                            {{ props.customer.is_active ? 'Profil actif' : 'Profil inactif' }}
                        </Badge>
                    </div>

                    <Separator />

                    <div class="space-y-3">
                        <div
                            v-for="session in sessionHistory"
                            :key="session.id"
                            class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm transition-colors hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/70"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        {{ formatSessionDate(session) }}
                                    </p>
                                    <p class="text-base font-semibold text-slate-900 dark:text-white">
                                        {{ session.name || 'Séance sans titre' }}
                                    </p>
                                </div>
                                <Badge variant="outline" class="text-xs text-slate-500">
                                    {{ (session.exercises_count ?? 0) }} exercice{{ (session.exercises_count ?? 0) > 1 ? 's' : '' }}
                                </Badge>
                            </div>
                            <p v-if="session.notes" class="text-sm text-slate-600 dark:text-slate-300 mt-2">
                                {{ session.notes }}
                            </p>
                            <p v-else class="text-xs text-slate-400 mt-2">
                                Aucune note sur cette séance.
                            </p>
                        </div>

                        <p v-if="sessionHistory.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
                            Aucun entraînement enregistré pour l’instant. Commencez par créer une session depuis l’accueil.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>


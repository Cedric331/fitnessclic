<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface InvitationData {
    coach_name?: string | null;
    email?: string | null;
    expires_at?: string | null;
    token?: string | null;
}

const props = defineProps<{
    status: 'valid' | 'expired' | 'used' | 'not_found';
    invitation: InvitationData | null;
    canAccept: boolean;
    isAuthenticated: boolean;
    acceptUrl: string | null;
    registerUrl: string | null;
    loginUrl: string;
}>();

const title = computed(() => {
    switch (props.status) {
        case 'valid':
            return 'Invitation de votre coach';
        case 'used':
            return 'Invitation déjà utilisée';
        case 'expired':
            return 'Invitation expirée';
        default:
            return 'Invitation introuvable';
    }
});

const description = computed(() => {
    if (props.status === 'valid' && props.invitation) {
        return `${props.invitation.coach_name ?? 'Votre coach'} vous invite à créer votre compte client sur FitnessClic.`;
    }
    if (props.status === 'used') {
        return 'Cette invitation a déjà été utilisée.';
    }
    if (props.status === 'expired') {
        return 'Cette invitation a expiré. Demandez à votre coach de vous inviter à nouveau.';
    }
    return 'Le lien d’invitation est invalide ou a été supprimé.';
});

const formatDate = (value?: string | null) => {
    if (!value) return '—';
    const date = new Date(value);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const acceptInvitation = () => {
    if (!props.acceptUrl) return;
    router.post(props.acceptUrl);
};
</script>

<template>
    <Head :title="title" />

    <AuthLayout title="Invitation de votre coach" description="Acceptez l’invitation pour rejoindre votre coach.">
        <div class="mx-auto flex h-full w-full flex-1 flex-col gap-6 px-2 py-10 max-w-2xl">
            <Card>
                <CardHeader>
                    <CardTitle>{{ title }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        {{ description }}
                    </p>

                    <div v-if="props.status === 'valid' && props.invitation" class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <p><strong>Email invité :</strong> {{ props.invitation.email }}</p>
                        <p><strong>Coach :</strong> {{ props.invitation.coach_name }}</p>
                        <p><strong>Expire le :</strong> {{ formatDate(props.invitation.expires_at) }}</p>
                    </div>

                    <div v-if="props.status === 'valid'">
                        <div v-if="props.canAccept" class="flex flex-col gap-3 sm:flex-row">
                            <Button @click="acceptInvitation">
                                Rejoindre mon coach
                            </Button>
                        </div>

                        <div v-else class="space-y-3">
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Pour accepter l’invitation, créez votre compte ou connectez-vous.
                            </p>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <Button v-if="props.registerUrl" :as-child="true">
                                    <a :href="props.registerUrl">Créer mon compte</a>
                                </Button>
                                <Button variant="outline" :as-child="true">
                                    <a :href="props.loginUrl">J'ai déjà un compte</a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, nextTick } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useNotifications } from '@/composables/useNotifications';

interface TeamMember {
    id: number;
    name: string;
    email: string;
}

interface TeamInvitation {
    id: number;
    email: string;
    expires_at?: string | null;
    invited_by?: string | null;
}

interface TeamData {
    id: number;
    name: string;
    owner_id: number;
}

const props = defineProps<{
    team: TeamData | null;
    members: TeamMember[];
    pendingInvitations: TeamInvitation[];
}>();

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mon équipe', href: '/team' },
];

const createForm = useForm({
    name: '',
});

const inviteForm = useForm({
    email: '',
});

const isRemoveDialogOpen = ref(false);
const memberToRemove = ref<TeamMember | null>(null);
const removeForm = useForm({});

const isCancelInviteDialogOpen = ref(false);
const invitationToCancel = ref<TeamInvitation | null>(null);
const cancelInviteForm = useForm({});

const isOwner = computed(() => {
    const userId = (page.props as any).auth?.user?.id;
    return !!props.team && props.team.owner_id === userId;
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

const submitCreateTeam = () => {
    createForm.post('/team', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset('name');
        },
    });
};

const submitInvite = () => {
    inviteForm.post('/team/invitations', {
        preserveScroll: true,
        onSuccess: () => {
            inviteForm.reset('email');
        },
    });
};

const openRemoveDialog = (member: TeamMember) => {
    memberToRemove.value = member;
    isRemoveDialogOpen.value = true;
};

const confirmRemoveMember = () => {
    if (!memberToRemove.value || removeForm.processing) {
        return;
    }
    removeForm.delete(`/team/members/${memberToRemove.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isRemoveDialogOpen.value = false;
            memberToRemove.value = null;
        },
        onFinish: () => {
            removeForm.clearErrors();
        },
    });
};

const openCancelInviteDialog = (invitation: TeamInvitation) => {
    invitationToCancel.value = invitation;
    isCancelInviteDialogOpen.value = true;
};

const confirmCancelInvitation = () => {
    if (!invitationToCancel.value || cancelInviteForm.processing) {
        return;
    }
    cancelInviteForm.delete(`/team/invitations/${invitationToCancel.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isCancelInviteDialogOpen.value = false;
            invitationToCancel.value = null;
        },
        onFinish: () => {
            cancelInviteForm.clearErrors();
        },
    });
};

// Notifications flash
const shownFlashMessages = ref(new Set<string>());
watch(() => (page.props as any).flash, (flash) => {
    if (!flash) return;

    const successKey = flash.success ? `success-${flash.success}` : null;
    const errorKey = flash.error ? `error-${flash.error}` : null;

    if (successKey && !shownFlashMessages.value.has(successKey)) {
        shownFlashMessages.value.add(successKey);
        nextTick(() => notifySuccess(flash.success));
        setTimeout(() => shownFlashMessages.value.delete(successKey), 4000);
    }

    if (errorKey && !shownFlashMessages.value.has(errorKey)) {
        shownFlashMessages.value.add(errorKey);
        nextTick(() => notifyError(flash.error));
        setTimeout(() => shownFlashMessages.value.delete(errorKey), 6000);
    }
}, { immediate: true });
</script>

<template>
    <Head title="Mon équipe" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-full w-full flex-1 flex-col gap-6 rounded-xl px-6 py-5">
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Mon équipe</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Gérez vos coachs et partagez vos données d’équipe.
                </p>
            </div>

            <Card v-if="!team">
                <CardHeader>
                    <CardTitle>Créer votre équipe</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Créez une équipe pour inviter d’autres coachs et partager vos séances, clients et catégories.
                    </p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <Input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Nom de l'équipe"
                            class="w-full"
                        />
                        <Button
                            :disabled="createForm.processing || !createForm.name.trim()"
                            @click="submitCreateTeam"
                        >
                            Créer l’équipe
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <div v-else class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            {{ team.name }}
                            <Badge v-if="isOwner" variant="outline">Propriétaire</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400 mb-2">
                                Coachs membres
                            </p>
                            <div class="space-y-2">
                                <div
                                    v-for="member in members"
                                    :key="member.id"
                                    class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700"
                                >
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">{{ member.name }}</p>
                                        <p class="text-xs text-slate-500">{{ member.email }}</p>
                                    </div>
                                    <div v-if="isOwner && member.id !== team.owner_id">
                                        <Button variant="outline" size="sm" @click="openRemoveDialog(member)">
                                            Retirer
                                        </Button>
                                    </div>
                                </div>
                                <p v-if="members.length === 0" class="text-sm text-slate-500">
                                    Aucun membre pour le moment.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Inviter un coach</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Input
                                v-model="inviteForm.email"
                                type="email"
                                placeholder="email@exemple.com"
                                class="w-full"
                            />
                            <Button
                                class="w-full"
                                :disabled="inviteForm.processing || !inviteForm.email.trim()"
                                @click="submitInvite"
                            >
                                Envoyer l’invitation
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Invitations en attente</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-for="invitation in pendingInvitations"
                                :key="invitation.id"
                                class="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700"
                            >
                                <p class="font-medium text-slate-900 dark:text-white">{{ invitation.email }}</p>
                                <p class="text-xs text-slate-500">
                                    Expire le {{ formatDate(invitation.expires_at) }}
                                </p>
                                <p v-if="invitation.invited_by" class="text-xs text-slate-500">
                                    Invitée par {{ invitation.invited_by }}
                                </p>
                                <div v-if="isOwner" class="mt-3">
                                    <Button variant="outline" size="sm" @click="openCancelInviteDialog(invitation)">
                                        Annuler l’invitation
                                    </Button>
                                </div>
                            </div>
                            <p v-if="pendingInvitations.length === 0" class="text-sm text-slate-500">
                                Aucune invitation en attente.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Dialog v-model:open="isRemoveDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Retirer un coach</DialogTitle>
                        <DialogDescription>
                            Confirmez-vous le retrait de
                            <strong>{{ memberToRemove?.name }}</strong>
                            de l’équipe ?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="isRemoveDialogOpen = false" :disabled="removeForm.processing">
                            Annuler
                        </Button>
                        <Button @click="confirmRemoveMember" :disabled="removeForm.processing">
                            Confirmer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isCancelInviteDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Annuler l’invitation</DialogTitle>
                        <DialogDescription>
                            Voulez-vous annuler l’invitation envoyée à
                            <strong>{{ invitationToCancel?.email }}</strong> ?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="isCancelInviteDialogOpen = false" :disabled="cancelInviteForm.processing">
                            Annuler
                        </Button>
                        <Button @click="confirmCancelInvitation" :disabled="cancelInviteForm.processing">
                            Confirmer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>


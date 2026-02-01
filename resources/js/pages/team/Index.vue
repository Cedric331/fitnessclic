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
import { LogOut, Mail, Trash2, UserPlus } from 'lucide-vue-next';

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

interface UserInvitation {
    id: number;
    team_id?: number | null;
    team_name?: string | null;
    invited_by?: string | null;
    expires_at?: string | null;
}

interface TeamData {
    id: number;
    name: string;
    owner_id: number;
    is_owner: boolean;
    members: TeamMember[];
    pending_invitations: TeamInvitation[];
}

const props = defineProps<{
    teams: TeamData[];
    userInvitations?: UserInvitation[];
}>();

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();
const userInvitations = computed(() => props.userInvitations ?? []);
const teams = computed(() => props.teams ?? []);
const selectedTeamId = ref<number | null>(null);
const filteredTeams = computed(() => {
    if (!selectedTeamId.value) {
        return teams.value;
    }
    return teams.value.filter((team) => team.id === selectedTeamId.value);
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mon équipe', href: '/team' },
];

const createForm = useForm({
    name: '',
});

const inviteForm = useForm({
    email: '',
    team_id: null as number | null,
});
const inviteEmails = ref<Record<number, string>>({});

const isRemoveDialogOpen = ref(false);
const memberToRemove = ref<TeamMember | null>(null);
const teamForMemberAction = ref<TeamData | null>(null);
const removeForm = useForm({});

const isCancelInviteDialogOpen = ref(false);
const invitationToCancel = ref<TeamInvitation | null>(null);
const teamForInviteAction = ref<TeamData | null>(null);
const cancelInviteForm = useForm({});

const isInviteDialogOpen = ref(false);
const teamForInvite = ref<TeamData | null>(null);

const isPendingInvitesDialogOpen = ref(false);
const teamForPendingInvites = ref<TeamData | null>(null);

const isAcceptInviteDialogOpen = ref(false);
const invitationToAccept = ref<UserInvitation | null>(null);
const acceptInviteForm = useForm({});

const isDeclineInviteDialogOpen = ref(false);
const invitationToDecline = ref<UserInvitation | null>(null);
const declineInviteForm = useForm({});

const isLeaveDialogOpen = ref(false);
const teamToLeave = ref<TeamData | null>(null);
const leaveForm = useForm({});

const isTransferDialogOpen = ref(false);
const memberToTransfer = ref<TeamMember | null>(null);
const teamForTransfer = ref<TeamData | null>(null);
const transferForm = useForm({});

const isDeleteTeamDialogOpen = ref(false);
const teamToDelete = ref<TeamData | null>(null);
const deleteTeamForm = useForm({});

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

const submitInvite = (team: TeamData) => {
    inviteForm.email = inviteEmails.value[team.id] || '';
    inviteForm.team_id = team.id;
    inviteForm.post('/team/invitations', {
        preserveScroll: true,
        preserveState: true,
        only: ['teams', 'userInvitations', 'flash'],
        onSuccess: () => {
            inviteForm.reset('email', 'team_id');
            inviteEmails.value[team.id] = '';
            if (teamForInvite.value?.id === team.id) {
                isInviteDialogOpen.value = false;
                teamForInvite.value = null;
            }
        },
    });
};

const openInviteDialog = (team: TeamData) => {
    teamForInvite.value = team;
    isInviteDialogOpen.value = true;
};

const openPendingInvitesDialog = (team: TeamData) => {
    teamForPendingInvites.value = team;
    isPendingInvitesDialogOpen.value = true;
};

const openRemoveDialog = (team: TeamData, member: TeamMember) => {
    memberToRemove.value = member;
    teamForMemberAction.value = team;
    isRemoveDialogOpen.value = true;
};

const confirmRemoveMember = () => {
    if (!memberToRemove.value || !teamForMemberAction.value || removeForm.processing) {
        return;
    }
    removeForm.delete(`/team/${teamForMemberAction.value.id}/members/${memberToRemove.value.id}`, {
        preserveScroll: true,
        preserveState: true,
        only: ['teams', 'flash'],
        onSuccess: () => {
            isRemoveDialogOpen.value = false;
            memberToRemove.value = null;
            teamForMemberAction.value = null;
        },
        onFinish: () => {
            removeForm.clearErrors();
        },
    });
};

const openCancelInviteDialog = (team: TeamData, invitation: TeamInvitation) => {
    invitationToCancel.value = invitation;
    teamForInviteAction.value = team;
    isCancelInviteDialogOpen.value = true;
};

const confirmCancelInvitation = () => {
    if (!invitationToCancel.value || cancelInviteForm.processing) {
        return;
    }
    cancelInviteForm.delete(`/team/invitations/${invitationToCancel.value.id}`, {
        preserveScroll: true,
        preserveState: true,
        only: ['teams', 'flash'],
        onSuccess: () => {
            isCancelInviteDialogOpen.value = false;
            invitationToCancel.value = null;
            teamForInviteAction.value = null;
        },
        onFinish: () => {
            cancelInviteForm.clearErrors();
        },
    });
};

const openAcceptInviteDialog = (invitation: UserInvitation) => {
    invitationToAccept.value = invitation;
    isAcceptInviteDialogOpen.value = true;
};

const confirmAcceptInvitation = () => {
    if (!invitationToAccept.value || acceptInviteForm.processing) {
        return;
    }
    acceptInviteForm.post(`/team/invitations/${invitationToAccept.value.id}/accept`, {
        preserveScroll: true,
        preserveState: true,
        only: ['teams', 'userInvitations', 'flash'],
        onSuccess: () => {
            isAcceptInviteDialogOpen.value = false;
            invitationToAccept.value = null;
        },
        onFinish: () => {
            acceptInviteForm.clearErrors();
        },
    });
};

const openDeclineInviteDialog = (invitation: UserInvitation) => {
    invitationToDecline.value = invitation;
    isDeclineInviteDialogOpen.value = true;
};

const confirmDeclineInvitation = () => {
    if (!invitationToDecline.value || declineInviteForm.processing) {
        return;
    }
    declineInviteForm.post(`/team/invitations/${invitationToDecline.value.id}/decline`, {
        preserveScroll: true,
        preserveState: true,
        only: ['userInvitations', 'flash'],
        onSuccess: () => {
            isDeclineInviteDialogOpen.value = false;
            invitationToDecline.value = null;
        },
        onFinish: () => {
            declineInviteForm.clearErrors();
        },
    });
};

const openLeaveDialog = (team: TeamData) => {
    teamToLeave.value = team;
    isLeaveDialogOpen.value = true;
};

const confirmLeaveTeam = () => {
    if (leaveForm.processing) {
        return;
    }
    if (!teamToLeave.value) {
        return;
    }
    leaveForm.post(`/team/${teamToLeave.value.id}/leave`, {
        preserveScroll: true,
        preserveState: true,
        only: ['teams', 'userInvitations', 'flash'],
        onSuccess: () => {
            isLeaveDialogOpen.value = false;
            teamToLeave.value = null;
        },
        onFinish: () => {
            leaveForm.clearErrors();
        },
    });
};

const openTransferDialog = (team: TeamData, member: TeamMember) => {
    memberToTransfer.value = member;
    teamForTransfer.value = team;
    isTransferDialogOpen.value = true;
};

const confirmTransferOwnership = () => {
    if (!memberToTransfer.value || !teamForTransfer.value || transferForm.processing) {
        return;
    }
    transferForm.post(`/team/${teamForTransfer.value.id}/transfer-ownership/${memberToTransfer.value.id}`, {
        preserveScroll: true,
        preserveState: true,
        only: ['teams', 'flash'],
        onSuccess: () => {
            isTransferDialogOpen.value = false;
            memberToTransfer.value = null;
            teamForTransfer.value = null;
        },
        onFinish: () => {
            transferForm.clearErrors();
        },
    });
};

const openDeleteTeamDialog = (team: TeamData) => {
    teamToDelete.value = team;
    isDeleteTeamDialogOpen.value = true;
};

const confirmDeleteTeam = () => {
    if (deleteTeamForm.processing) {
        return;
    }
    if (!teamToDelete.value) {
        return;
    }
    deleteTeamForm.delete(`/team/${teamToDelete.value.id}`, {
        preserveScroll: true,
        preserveState: true,
        only: ['teams', 'userInvitations', 'flash'],
        onSuccess: () => {
            isDeleteTeamDialogOpen.value = false;
            teamToDelete.value = null;
        },
        onFinish: () => {
            deleteTeamForm.clearErrors();
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

watch(teams, (value) => {
    if (!selectedTeamId.value) {
        return;
    }
    const stillExists = value.some((team) => team.id === selectedTeamId.value);
    if (!stillExists) {
        selectedTeamId.value = null;
    }
});

const handleTeamFilterChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    selectedTeamId.value = target.value ? Number(target.value) : null;
};
</script>

<template>
    <Head title="Mon équipe" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex h-full w-full flex-1 flex-col gap-6 rounded-xl px-6 py-5">
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Mon équipe</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Créer un groupe de coachs et partager vos séances, clients et exercices.
                </p>
            </div>

            <div v-if="teams.length > 1" class="flex flex-col gap-1.5 sm:max-w-xs">
                <label class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                    Équipe affichée
                </label>
                <select
                    :value="selectedTeamId || ''"
                    class="h-10 w-full rounded-2xl border border-slate-200 bg-white px-3 text-sm text-slate-700 transition focus:border-blue-500 focus:outline-none focus:ring-0 dark:border-slate-800 dark:bg-slate-900/70 dark:text-white"
                    @change="handleTeamFilterChange"
                >
                    <option value="">Toutes les équipes</option>
                    <option v-for="team in teams" :key="team.id" :value="team.id">
                        {{ team.name }}
                    </option>
                </select>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="order-2 space-y-6 lg:order-1 lg:col-span-2">
                    <div v-if="teams.length === 0" class="rounded-lg border border-slate-200 p-4 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-400">
                        Aucune équipe pour le moment. Créez une équipe pour commencer.
                    </div>

                    <div
                        v-for="team in filteredTeams"
                        :key="team.id"
                        class="grid gap-6"
                    >
                        <Card>
                <CardHeader>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <CardTitle class="flex items-center gap-2">
                            {{ team.name }}
                            <Badge v-if="team.is_owner" variant="outline">Propriétaire</Badge>
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="team.is_owner"
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 gap-2 px-0 sm:h-9 sm:w-auto sm:px-3"
                                @click="openInviteDialog(team)"
                                title="Inviter un coach"
                            >
                                <UserPlus class="size-4" />
                                <span class="hidden sm:inline">Inviter</span>
                            </Button>
                            <Button
                                v-if="team.is_owner"
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 gap-2 px-0 sm:h-9 sm:w-auto sm:px-3"
                                @click="openPendingInvitesDialog(team)"
                                title="Invitations en attente"
                            >
                                <Mail class="size-4" />
                                <span class="hidden sm:inline">En attente</span>
                                <Badge
                                    v-if="team.pending_invitations.length"
                                    variant="outline"
                                    class="hidden sm:inline"
                                >
                                    {{ team.pending_invitations.length }}
                                </Badge>
                            </Button>
                            <Button
                                v-if="team.is_owner"
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 gap-2 px-0 text-red-600 hover:text-red-700 hover:bg-red-50 sm:h-9 sm:w-auto sm:px-3 dark:hover:bg-red-900/20"
                                @click="openDeleteTeamDialog(team)"
                                title="Supprimer l’équipe"
                            >
                                <Trash2 class="size-4" />
                                <span class="hidden sm:inline">Supprimer</span>
                            </Button>
                            <Button
                                v-if="!team.is_owner"
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 gap-2 px-0 sm:h-9 sm:w-auto sm:px-3"
                                @click="openLeaveDialog(team)"
                                title="Quitter l’équipe"
                            >
                                <LogOut class="size-4" />
                                <span class="hidden sm:inline">Quitter</span>
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs uppercase text-slate-500 dark:text-slate-400 mb-2">
                                Coachs membres
                            </p>
                            <div class="space-y-2">
                                <div
                                    v-for="member in team.members"
                                    :key="member.id"
                                    class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700"
                                >
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">
                                            {{ member.name }}
                                            <Badge v-if="member.id === team.owner_id" variant="outline" class="ml-2">
                                                Propriétaire
                                            </Badge>
                                        </p>
                                        <p class="text-xs text-slate-500">{{ member.email }}</p>
                                    </div>
                                    <div v-if="team.is_owner && member.id !== team.owner_id" class="flex items-center gap-2">
                                        <Button variant="outline" size="sm" @click="openTransferDialog(team, member)">
                                            Nommer propriétaire
                                        </Button>
                                        <Button variant="outline" size="sm" @click="openRemoveDialog(team, member)">
                                            Retirer
                                        </Button>
                                    </div>
                                </div>
                                <p v-if="team.members.length === 0" class="text-sm text-slate-500">
                                    Aucun membre pour le moment.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                        </Card>
                    </div>
                </div>

                <div class="order-1 space-y-6 lg:order-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Créer votre équipe</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Créer ici votre équipe et commencer à inviter les premiers coachs.
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

                    <Card>
                        <CardHeader>
                            <CardTitle>Mes invitations</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-for="invitation in userInvitations"
                                :key="invitation.id"
                                class="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700"
                            >
                                <p class="font-medium text-slate-900 dark:text-white">
                                    Équipe : {{ invitation.team_name || '—' }}
                                </p>
                                <p v-if="invitation.invited_by" class="text-xs text-slate-500">
                                    Invitée par {{ invitation.invited_by }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    Expire le {{ formatDate(invitation.expires_at) }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <Button size="sm" @click="openAcceptInviteDialog(invitation)">
                                        Accepter
                                    </Button>
                                    <Button variant="outline" size="sm" @click="openDeclineInviteDialog(invitation)">
                                        Refuser
                                    </Button>
                                </div>
                            </div>
                            <p v-if="userInvitations.length === 0" class="text-sm text-slate-500">
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

            <Dialog v-model:open="isInviteDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Inviter un coach</DialogTitle>
                        <DialogDescription>
                            Invitez un coach à rejoindre l’équipe
                            <strong>{{ teamForInvite?.name }}</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-3">
                        <Input
                            v-model="inviteEmails[teamForInvite?.id ?? 0]"
                            type="email"
                            placeholder="email@exemple.com"
                            class="w-full"
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isInviteDialogOpen = false" :disabled="inviteForm.processing">
                            Annuler
                        </Button>
                        <Button
                            @click="teamForInvite && submitInvite(teamForInvite)"
                            :disabled="inviteForm.processing || !inviteEmails[teamForInvite?.id ?? 0]?.trim()"
                        >
                            Envoyer l’invitation
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isPendingInvitesDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Invitations en attente</DialogTitle>
                        <DialogDescription>
                            Invitations envoyées pour l’équipe
                            <strong>{{ teamForPendingInvites?.name }}</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="space-y-3">
                        <div
                            v-for="invitation in teamForPendingInvites?.pending_invitations ?? []"
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
                            <div class="mt-3">
                                <Button variant="outline" size="sm" @click="teamForPendingInvites && openCancelInviteDialog(teamForPendingInvites, invitation)">
                                    Annuler l’invitation
                                </Button>
                            </div>
                        </div>
                        <p
                            v-if="(teamForPendingInvites?.pending_invitations ?? []).length === 0"
                            class="text-sm text-slate-500"
                        >
                            Aucune invitation en attente.
                        </p>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isPendingInvitesDialogOpen = false">
                            Fermer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isAcceptInviteDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Accepter l’invitation</DialogTitle>
                        <DialogDescription>
                            Voulez-vous rejoindre l’équipe
                            <strong>{{ invitationToAccept?.team_name }}</strong> ?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="isAcceptInviteDialogOpen = false" :disabled="acceptInviteForm.processing">
                            Annuler
                        </Button>
                        <Button @click="confirmAcceptInvitation" :disabled="acceptInviteForm.processing">
                            Confirmer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isDeclineInviteDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Refuser l’invitation</DialogTitle>
                        <DialogDescription>
                            Voulez-vous refuser l’invitation pour l’équipe
                            <strong>{{ invitationToDecline?.team_name }}</strong> ?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="isDeclineInviteDialogOpen = false" :disabled="declineInviteForm.processing">
                            Annuler
                        </Button>
                        <Button @click="confirmDeclineInvitation" :disabled="declineInviteForm.processing">
                            Confirmer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isLeaveDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Quitter l’équipe</DialogTitle>
                        <DialogDescription>
                            Confirmez-vous votre départ de l’équipe ?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="isLeaveDialogOpen = false" :disabled="leaveForm.processing">
                            Annuler
                        </Button>
                        <Button @click="confirmLeaveTeam" :disabled="leaveForm.processing">
                            Confirmer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isTransferDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nommer un propriétaire</DialogTitle>
                        <DialogDescription>
                            Voulez-vous nommer
                            <strong>{{ memberToTransfer?.name }}</strong>
                            comme nouveau propriétaire ?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="isTransferDialogOpen = false" :disabled="transferForm.processing">
                            Annuler
                        </Button>
                        <Button @click="confirmTransferOwnership" :disabled="transferForm.processing">
                            Confirmer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isDeleteTeamDialogOpen">
                <DialogContent class="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Supprimer l’équipe</DialogTitle>
                        <DialogDescription>
                            Cette action est définitive. Tous les membres seront informés par email.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="isDeleteTeamDialogOpen = false" :disabled="deleteTeamForm.processing">
                            Annuler
                        </Button>
                        <Button @click="confirmDeleteTeam" :disabled="deleteTeamForm.processing">
                            Confirmer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>


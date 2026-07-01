<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const inviteToken = ref<string | null>(null);
const customerInviteToken = ref<string | null>(null);
const inviteEmail = ref<string | null>(null);
const emailValue = ref('');
const selectedRole = ref<'coach' | 'client'>('coach');
const redirectTo = ref<string | null>(null);
const contactCoach = ref<string | null>(null);

if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    inviteToken.value = params.get('invite');
    customerInviteToken.value = params.get('customer_invite');
    inviteEmail.value = params.get('email');
    if (inviteEmail.value) {
        emailValue.value = inviteEmail.value;
    }
    // Une invitation client force le rôle « client ».
    if (customerInviteToken.value || params.get('role') === 'client') {
        selectedRole.value = 'client';
    }
    // Destination post-inscription (ex. retour sur la fiche du coach).
    const redirect = params.get('redirect');
    if (redirect && redirect.startsWith('/') && !redirect.startsWith('//')) {
        redirectTo.value = redirect;
    }
    // Coach à contacter → conversation ouverte automatiquement après vérification.
    contactCoach.value = params.get('contact');
}

// L'email est verrouillé lorsqu'il provient d'une invitation (équipe ou client).
const emailLocked = computed(() => !!inviteToken.value || !!customerInviteToken.value);

// Le lien « se connecter » conserve la destination pour un client déjà inscrit.
const loginHref = computed(() =>
    redirectTo.value
        ? `${login.url()}?redirect=${encodeURIComponent(redirectTo.value)}`
        : login.url(),
);
</script>

<template>
    <AuthBase
        title="Créer un compte"
        description="Entrez vos informations ci-dessous pour créer votre compte"
    >
        <Head title="Inscription" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <input
                v-if="inviteToken"
                type="hidden"
                name="invite_token"
                :value="inviteToken"
            />
            <input
                v-if="customerInviteToken"
                type="hidden"
                name="customer_invite"
                :value="customerInviteToken"
            />
            <!-- Le rôle est toujours envoyé ; côté serveur une invitation force le rôle. -->
            <input type="hidden" name="role" :value="selectedRole" />
            <!-- Destination post-inscription (ex. retour sur la fiche du coach). -->
            <input v-if="redirectTo" type="hidden" name="redirect" :value="redirectTo" />
            <!-- Coach à contacter automatiquement après vérification de l'e-mail. -->
            <input v-if="contactCoach" type="hidden" name="contact_coach" :value="contactCoach" />
            <div class="grid gap-6">
                <div v-if="!inviteToken && !customerInviteToken" class="grid gap-2">
                    <Label>Je suis…</Label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            :class="[
                                'rounded-lg border p-3 text-center text-sm font-medium transition',
                                selectedRole === 'coach'
                                    ? 'border-primary bg-primary/5 text-primary ring-1 ring-primary'
                                    : 'border-input text-muted-foreground hover:bg-accent',
                            ]"
                            @click="selectedRole = 'coach'"
                        >
                            Coach
                            <span class="mt-0.5 block text-xs font-normal opacity-70">
                                Je propose mes séances
                            </span>
                        </button>
                        <button
                            type="button"
                            :class="[
                                'rounded-lg border p-3 text-center text-sm font-medium transition',
                                selectedRole === 'client'
                                    ? 'border-primary bg-primary/5 text-primary ring-1 ring-primary'
                                    : 'border-input text-muted-foreground hover:bg-accent',
                            ]"
                            @click="selectedRole = 'client'"
                        >
                            Client
                            <span class="mt-0.5 block text-xs font-normal opacity-70">
                                Je cherche un coach
                            </span>
                        </button>
                    </div>
                    <InputError :message="errors.role" />
                </div>

                <div class="grid gap-2">
                    <Label for="name">Nom</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Nom complet"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Adresse e-mail</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@exemple.com"
                        v-model="emailValue"
                        :readonly="emailLocked"
                        :class="emailLocked ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''"
                    />
                    <InputError :message="errors.email" />
                    <p v-if="emailLocked" class="text-xs text-slate-500">
                        L’adresse email est verrouillée car l’inscription vient d’une invitation.
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="password">Mot de passe</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Mot de passe"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirmer le mot de passe</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirmer le mot de passe"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Créer le compte
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Vous avez déjà un compte ?
                <TextLink
                    :href="loginHref"
                    class="underline underline-offset-4"
                    :tabindex="6"
                    >Se connecter</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>

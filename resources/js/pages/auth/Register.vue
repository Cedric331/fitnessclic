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
import { ref } from 'vue';

const inviteToken = ref<string | null>(null);
const inviteEmail = ref<string | null>(null);
const emailValue = ref('');

if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    inviteToken.value = params.get('invite');
    inviteEmail.value = params.get('email');
    if (inviteEmail.value) {
        emailValue.value = inviteEmail.value;
    }
}
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
            <div class="grid gap-6">
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
                        :readonly="!!inviteToken"
                        :class="inviteToken ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''"
                    />
                    <InputError :message="errors.email" />
                    <p v-if="inviteToken" class="text-xs text-slate-500">
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
                    :href="login.url()"
                    class="underline underline-offset-4"
                    :tabindex="6"
                    >Se connecter</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>

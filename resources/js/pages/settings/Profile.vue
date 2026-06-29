<script setup lang="ts">
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    clientAvatarUrl?: string | null;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Paramètres du profil',
        href: edit.url(),
    },
];

const page = usePage();
const user = page.props.auth.user;
const isClient = computed(() => (user as any)?.role === 'client');

// Photo de profil — visible uniquement dans la messagerie.
const photoForm = useForm({ photo: null as File | null });
const localPreview = ref<string | null>(null);
const photoPreview = computed(() => localPreview.value ?? props.clientAvatarUrl ?? null);
const removingPhoto = ref(false);

const onPhotoChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    photoForm.photo = file;
    localPreview.value = file ? URL.createObjectURL(file) : null;
};

const submitPhoto = () => {
    photoForm.post('/settings/profile/photo', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            photoForm.reset('photo');
            localPreview.value = null;
        },
    });
};

const removePhoto = () => {
    removingPhoto.value = true;
    router.delete('/settings/profile/photo', {
        preserveScroll: true,
        onFinish: () => {
            removingPhoto.value = false;
            localPreview.value = null;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Paramètres du profil" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Informations du profil"
                    description="Mettez à jour votre nom et votre adresse e-mail"
                />

                <Form
                    :action="edit.url()"
                    method="patch"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Nom</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Nom complet"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Adresse e-mail</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Adresse e-mail"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 inline text-sm text-muted-foreground">
                            Votre adresse e-mail n'est pas vérifiée.
                            <Form
                                v-bind="send.form()"
                                class="inline"
                                v-slot="{ processing }"
                            >
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    size="sm"
                                    :disabled="processing"
                                    class="h-auto min-h-0 px-0 py-0 font-normal text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:bg-transparent hover:decoration-current! dark:decoration-neutral-500"
                                >
                                    Cliquez ici pour renvoyer l'e-mail de vérification.
                                </Button>
                            </Form>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Enregistrer</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Enregistré.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <div v-if="isClient" class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Photo de profil"
                    description="Cette photo s'affiche uniquement dans la messagerie avec vos coachs."
                />

                <form class="space-y-4" @submit.prevent="submitPhoto">
                    <div class="flex items-center gap-4">
                        <div class="size-20 shrink-0 overflow-hidden rounded-full border bg-muted">
                            <img
                                v-if="photoPreview"
                                :src="photoPreview"
                                alt="Aperçu de la photo de profil"
                                class="size-full object-cover"
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Input
                                type="file"
                                accept="image/*"
                                class="max-w-xs"
                                @change="onPhotoChange"
                            />
                            <InputError :message="photoForm.errors.photo" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            type="submit"
                            :disabled="photoForm.processing || !photoForm.photo"
                        >
                            Enregistrer la photo
                        </Button>
                        <Button
                            v-if="props.clientAvatarUrl"
                            type="button"
                            variant="outline"
                            :disabled="removingPhoto"
                            @click="removePhoto"
                        >
                            Supprimer
                        </Button>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>

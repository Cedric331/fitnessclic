<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Spinner } from '@/components/ui/spinner';
import { type BreadcrumbItem } from '@/types';
import { ExternalLink } from 'lucide-vue-next';

type Profile = {
    slug: string;
    headline: string | null;
    bio: string | null;
    hourly_rate: number | null;
    city: string | null;
    postal_code: string | null;
    specialties: string;
    is_published: boolean;
    avatar_url: string | null;
    public_url: string;
};

const props = defineProps<{ profile: Profile }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Mon profil coach', href: '/mon-profil-coach' },
];

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as { success?: string })?.success);

const form = useForm({
    headline: props.profile.headline ?? '',
    bio: props.profile.bio ?? '',
    hourly_rate: props.profile.hourly_rate ?? '',
    city: props.profile.city ?? '',
    postal_code: props.profile.postal_code ?? '',
    specialties: props.profile.specialties ?? '',
    is_published: props.profile.is_published,
    photo: null as File | null,
});

const previewUrl = computed(() => {
    if (form.photo) {
        return URL.createObjectURL(form.photo);
    }
    return props.profile.avatar_url;
});

const onFile = (e: Event) => {
    const target = e.target as HTMLInputElement;
    form.photo = target.files?.[0] ?? null;
};

const submit = () => {
    form.post('/mon-profil-coach', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.photo = null;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mon profil coach" />

        <div class="mx-auto w-full max-w-2xl space-y-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <HeadingSmall
                    title="Mon profil coach"
                    description="Ces informations s'affichent sur votre profil public dans l'annuaire."
                />
                <a
                    v-if="profile.is_published"
                    :href="profile.public_url"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm text-primary hover:underline"
                >
                    Voir mon profil <ExternalLink class="size-3.5" />
                </a>
            </div>

            <div
                v-if="flashSuccess"
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
            >
                {{ flashSuccess }}
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <!-- Photo -->
                <div class="grid gap-2">
                    <Label>Photo de profil</Label>
                    <div class="flex items-center gap-4">
                        <div class="size-20 overflow-hidden rounded-full border bg-muted">
                            <img
                                v-if="previewUrl"
                                :src="previewUrl"
                                alt="Aperçu"
                                class="size-full object-cover"
                            />
                        </div>
                        <Input type="file" accept="image/*" class="max-w-xs" @change="onFile" />
                    </div>
                    <InputError :message="form.errors.photo" />
                </div>

                <div class="grid gap-2">
                    <Label for="headline">Accroche</Label>
                    <Input
                        id="headline"
                        v-model="form.headline"
                        placeholder="Coach sportif spécialisé en remise en forme"
                    />
                    <InputError :message="form.errors.headline" />
                </div>

                <div class="grid gap-2">
                    <Label for="bio">Description</Label>
                    <Textarea
                        id="bio"
                        v-model="form.bio"
                        rows="6"
                        placeholder="Présentez votre parcours, vos méthodes, votre approche…"
                    />
                    <InputError :message="form.errors.bio" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="hourly_rate">Tarif horaire (€)</Label>
                        <Input
                            id="hourly_rate"
                            v-model="form.hourly_rate"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="40"
                        />
                        <InputError :message="form.errors.hourly_rate" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="city">Ville</Label>
                        <Input id="city" v-model="form.city" placeholder="Paris" />
                        <InputError :message="form.errors.city" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="postal_code">Code postal</Label>
                        <Input id="postal_code" v-model="form.postal_code" placeholder="75001" />
                        <InputError :message="form.errors.postal_code" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="specialties">Spécialités</Label>
                        <Input
                            id="specialties"
                            v-model="form.specialties"
                            placeholder="Musculation, Cardio, Yoga"
                        />
                        <p class="text-xs text-muted-foreground">Séparées par des virgules.</p>
                        <InputError :message="form.errors.specialties" />
                    </div>
                </div>

                <!-- Publication -->
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-4"
                >
                    <input
                        type="checkbox"
                        v-model="form.is_published"
                        class="mt-0.5 size-4"
                    />
                    <span class="text-sm">
                        <span class="font-medium">Publier mon profil dans l'annuaire</span>
                        <span class="mt-0.5 block text-muted-foreground">
                            Une ville est requise pour publier. Décochez pour masquer votre profil.
                        </span>
                    </span>
                </label>
                <InputError :message="form.errors.is_published" />

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        Enregistrer
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

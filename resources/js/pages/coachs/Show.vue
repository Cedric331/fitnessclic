<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SiteLayout from '@/components/SiteLayout.vue';
import { Button } from '@/components/ui/button';
import {
    MapPin,
    ArrowLeft,
    MessageCircle,
    BadgeCheck,
    Target,
    CalendarCheck,
    ShieldCheck,
    Clock,
} from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

type Coach = {
    slug: string;
    name: string | null;
    headline: string | null;
    bio: string | null;
    hourly_rate: number | null;
    city: string | null;
    specialties: string[];
    avatar_url: string | null;
};

type Meta = {
    title: string;
    description: string;
    canonical_url: string;
    image_url: string;
};

const props = defineProps<{ coach: Coach; meta: Meta }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Trouver un coach', href: '/coachs' },
    { title: props.coach.name ?? 'Coach', href: `/coachs/${props.coach.slug}` },
];

const page = usePage();
const user = computed(() => page.props.auth?.user as { isClient?: boolean } | null);

const formatRate = (rate: number | null) =>
    rate === null ? null : `${Number.isInteger(rate) ? rate : rate.toFixed(2)}`;

// Un client connecté ouvre (ou rouvre) une conversation avec ce coach ;
// un visiteur non connecté est redirigé vers la connexion.
const contact = () => {
    if (user.value?.isClient) {
        router.post('/messages/start', { coach_slug: props.coach.slug });
    } else {
        window.location.href = `/login?redirect=${encodeURIComponent(`/coachs/${props.coach.slug}`)}`;
    }
};

const initials = (name: string | null) =>
    (name ?? '?')
        .split(' ')
        .map((p) => p.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();

const steps = [
    { icon: MessageCircle, title: 'Contactez', text: 'Envoyez un message au coach pour vous présenter.' },
    { icon: Target, title: 'Échangez', text: 'Discutez de vos objectifs et de vos disponibilités.' },
    { icon: CalendarCheck, title: 'Démarrez', text: 'Planifiez votre première séance et progressez.' },
];
</script>

<template>
    <Head :title="meta.title">
        <meta name="description" :content="meta.description" />
        <link rel="canonical" :href="meta.canonical_url" />

        <meta property="og:type" content="profile" />
        <meta property="og:url" :content="meta.canonical_url" />
        <meta property="og:title" :content="meta.title" />
        <meta property="og:description" :content="meta.description" />
        <meta property="og:image" :content="meta.image_url" />
        <meta property="og:site_name" content="FitnessClic" />
        <meta property="og:locale" content="fr_FR" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="meta.title" />
        <meta name="twitter:description" :content="meta.description" />
        <meta name="twitter:image" :content="meta.image_url" />
    </Head>

    <SiteLayout :breadcrumbs="breadcrumbItems">
        <div class="mx-auto max-w-7xl px-4 py-6">
            <Link
                href="/coachs"
                class="mb-5 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="size-4" /> Retour à l'annuaire
            </Link>

            <!-- En-tête : dégradé sombre, texte blanc lisible -->
            <div
                class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-800 via-slate-900 to-slate-800 px-6 py-7 text-white shadow-sm sm:px-9 sm:py-9"
            >
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div
                        class="size-24 shrink-0 overflow-hidden rounded-2xl bg-white/10 ring-4 ring-white/15 sm:size-28"
                    >
                        <img
                            v-if="coach.avatar_url"
                            :src="coach.avatar_url"
                            :alt="coach.name ?? 'Coach'"
                            class="size-full object-cover"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center text-3xl font-bold text-white/90"
                        >
                            {{ initials(coach.name) }}
                        </div>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                                {{ coach.name }}
                            </h1>
                            <BadgeCheck class="size-5 text-sky-400" />
                        </div>
                        <p v-if="coach.headline" class="mt-1 text-white/80">
                            {{ coach.headline }}
                        </p>
                        <p
                            v-if="coach.city"
                            class="mt-2 flex items-center gap-1.5 text-sm text-white/70"
                        >
                            <MapPin class="size-4" /> {{ coach.city }}
                        </p>
                    </div>

                    <div class="hidden sm:block">
                        <Button
                            size="lg"
                            class="bg-white text-slate-900 hover:bg-white/90"
                            @click="contact"
                        >
                            <MessageCircle class="size-4" /> Contacter
                        </Button>
                    </div>
                </div>

                <div v-if="coach.specialties.length" class="mt-5 flex flex-wrap gap-2">
                    <span
                        v-for="s in coach.specialties"
                        :key="s"
                        class="rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-white/90 ring-1 ring-inset ring-white/15"
                    >
                        {{ s }}
                    </span>
                </div>
            </div>

            <!-- Corps : contenu + carte latérale -->
            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
                <div class="space-y-6">
                    <!-- À propos -->
                    <section class="rounded-2xl border bg-card p-6">
                        <h2 class="mb-3 text-lg font-semibold">À propos</h2>
                        <p
                            v-if="coach.bio"
                            class="whitespace-pre-line leading-relaxed text-muted-foreground"
                        >
                            {{ coach.bio }}
                        </p>
                        <p v-else class="text-sm italic text-muted-foreground">
                            Ce coach n'a pas encore rédigé de présentation. Contactez-le directement
                            pour en savoir plus sur son approche.
                        </p>
                    </section>

                    <!-- Comment ça se passe -->
                    <section class="rounded-2xl border bg-card p-6">
                        <h2 class="mb-4 text-lg font-semibold">Comment ça se passe&nbsp;?</h2>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div
                                v-for="(step, i) in steps"
                                :key="step.title"
                                class="rounded-xl border bg-muted/20 p-4"
                            >
                                <div
                                    class="mb-2 flex size-9 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-950/50"
                                >
                                    <component :is="step.icon" class="size-5" />
                                </div>
                                <p class="text-sm font-semibold">{{ i + 1 }}. {{ step.title }}</p>
                                <p class="mt-0.5 text-sm text-muted-foreground">{{ step.text }}</p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Carte de contact -->
                <aside class="lg:sticky lg:top-6 lg:self-start">
                    <div class="rounded-2xl border bg-card p-6">
                        <div v-if="formatRate(coach.hourly_rate)" class="mb-4">
                            <span class="text-3xl font-bold">{{ formatRate(coach.hourly_rate) }} €</span>
                            <span class="text-muted-foreground"> / heure</span>
                        </div>
                        <p v-else class="mb-4 text-sm text-muted-foreground">Tarif sur demande</p>

                        <Button class="w-full" size="lg" @click="contact">
                            <MessageCircle class="size-4" /> Contacter ce coach
                        </Button>

                        <ul class="mt-5 space-y-2.5 text-sm text-muted-foreground">
                            <li class="flex items-center gap-2">
                                <Clock class="size-4 text-blue-500" /> Réponse via la messagerie
                            </li>
                            <li v-if="coach.city" class="flex items-center gap-2">
                                <MapPin class="size-4 text-blue-500" /> {{ coach.city }}
                            </li>
                            <li class="flex items-center gap-2">
                                <ShieldCheck class="size-4 text-blue-500" /> Échanges sécurisés sur FitnessClic
                            </li>
                        </ul>
                    </div>
                </aside>
            </div>

            <!-- Barre de contact mobile -->
            <div class="mt-6 sm:hidden">
                <Button class="w-full" size="lg" @click="contact">
                    <MessageCircle class="size-4" /> Contacter ce coach
                </Button>
            </div>
        </div>
    </SiteLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import SiteLayout from '@/components/SiteLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { MapPin, Search } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Trouver un coach', href: '/coachs' }];

type Coach = {
    slug: string;
    name: string | null;
    headline: string | null;
    hourly_rate: number | null;
    city: string | null;
    specialties: string[];
    avatar_url: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    coaches: {
        data: Coach[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        q: string;
        city: string;
        specialty: string;
        min_rate: string;
        max_rate: string;
    };
}>();

const q = ref(props.filters.q ?? '');
const city = ref(props.filters.city ?? '');
const specialty = ref(props.filters.specialty ?? '');
const minRate = ref(props.filters.min_rate ?? '');
const maxRate = ref(props.filters.max_rate ?? '');

const submit = () => {
    router.get(
        '/coachs',
        {
            q: q.value,
            city: city.value,
            specialty: specialty.value,
            min_rate: minRate.value,
            max_rate: maxRate.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const reset = () => {
    q.value = '';
    city.value = '';
    specialty.value = '';
    minRate.value = '';
    maxRate.value = '';
    submit();
};

const formatRate = (rate: number | null) =>
    rate === null ? null : `${Number.isInteger(rate) ? rate : rate.toFixed(2)} €/h`;

const initials = (name: string | null) =>
    (name ?? '?')
        .split(' ')
        .map((p) => p.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();
</script>

<template>
    <Head title="Trouver un coach sportif">
        <meta
            name="description"
            content="Trouvez votre coach sportif sur FitnessClic : parcourez les profils, comparez les tarifs et contactez le coach qui vous correspond."
        />
    </Head>

    <SiteLayout :breadcrumbs="breadcrumbItems">
            <!-- Hero / recherche -->
            <section class="border-b bg-muted/30">
                <div class="mx-auto max-w-7xl px-4 py-10 sm:py-14">
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        Trouvez le coach sportif qu'il vous faut
                    </h1>
                    <p class="mt-2 max-w-2xl text-muted-foreground">
                        Parcourez les profils de coachs, comparez les tarifs et contactez celui qui
                        vous correspond.
                    </p>

                    <form class="mt-6 space-y-3" @submit.prevent="submit">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                />
                                <Input
                                    v-model="q"
                                    class="pl-9"
                                    placeholder="Nom, discipline, mot-clé…"
                                />
                            </div>
                            <div class="relative">
                                <MapPin
                                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                />
                                <Input v-model="city" class="pl-9" placeholder="Ville" />
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm text-muted-foreground">Tarif&nbsp;:</span>
                            <Input
                                v-model="minRate"
                                type="number"
                                min="0"
                                class="w-28"
                                placeholder="Min €"
                            />
                            <span class="text-muted-foreground">–</span>
                            <Input
                                v-model="maxRate"
                                type="number"
                                min="0"
                                class="w-28"
                                placeholder="Max €"
                            />
                            <div class="ml-auto flex gap-2">
                                <Button
                                    v-if="
                                        filters.q ||
                                        filters.city ||
                                        filters.specialty ||
                                        filters.min_rate ||
                                        filters.max_rate
                                    "
                                    type="button"
                                    variant="outline"
                                    @click="reset"
                                >
                                    Réinitialiser
                                </Button>
                                <Button type="submit">Rechercher</Button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Résultats -->
            <section class="mx-auto max-w-7xl px-4 py-8">
                <p class="mb-4 text-sm text-muted-foreground">
                    {{ coaches.total }} coach{{ coaches.total > 1 ? 's' : '' }} trouvé{{
                        coaches.total > 1 ? 's' : ''
                    }}
                </p>

                <div
                    v-if="coaches.data.length"
                    class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <Link
                        v-for="coach in coaches.data"
                        :key="coach.slug"
                        :href="`/coachs/${coach.slug}`"
                        class="group flex flex-col overflow-hidden rounded-xl border bg-card transition hover:shadow-lg"
                    >
                        <div class="aspect-[4/3] w-full overflow-hidden bg-muted">
                            <img
                                v-if="coach.avatar_url"
                                :src="coach.avatar_url"
                                :alt="coach.name ?? 'Coach'"
                                class="size-full object-cover transition group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex size-full items-center justify-center text-3xl font-bold text-muted-foreground"
                            >
                                {{ initials(coach.name) }}
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="font-semibold leading-tight">{{ coach.name }}</h2>
                                <span
                                    v-if="formatRate(coach.hourly_rate)"
                                    class="whitespace-nowrap text-sm font-semibold text-primary"
                                >
                                    {{ formatRate(coach.hourly_rate) }}
                                </span>
                            </div>
                            <p
                                v-if="coach.headline"
                                class="line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ coach.headline }}
                            </p>
                            <p
                                v-if="coach.city"
                                class="mt-auto flex items-center gap-1 text-sm text-muted-foreground"
                            >
                                <MapPin class="size-3.5" /> {{ coach.city }}
                            </p>
                            <div v-if="coach.specialties.length" class="flex flex-wrap gap-1.5">
                                <Badge
                                    v-for="s in coach.specialties"
                                    :key="s"
                                    variant="secondary"
                                >
                                    {{ s }}
                                </Badge>
                            </div>
                        </div>
                    </Link>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-dashed py-16 text-center text-muted-foreground"
                >
                    Aucun coach ne correspond à votre recherche.
                </div>

                <!-- Pagination -->
                <div
                    v-if="coaches.last_page > 1"
                    class="mt-8 flex flex-wrap justify-center gap-1"
                >
                    <Link
                        v-for="(link, i) in coaches.links"
                        :key="i"
                        :href="link.url ?? ''"
                        :class="[
                            'rounded-md border px-3 py-1.5 text-sm',
                            link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </section>
    </SiteLayout>
</template>

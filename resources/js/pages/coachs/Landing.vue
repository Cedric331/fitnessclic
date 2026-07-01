<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteLayout from '@/components/SiteLayout.vue';
import CoachCard from '@/components/CoachCard.vue';
import type { Coach } from '@/types/coach';
import { Dumbbell, MapPin, ArrowRight } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type RelatedLink = { label: string; url: string };

const props = defineProps<{
    coaches: {
        data: Coach[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        total: number;
    };
    landing: {
        heading: string;
        label: string;
        intro: string;
    };
    related: {
        disciplines: RelatedLink[];
        cities: RelatedLink[];
    };
    meta: {
        title: string;
        description: string;
        canonical_url: string;
        image_url: string;
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Trouver un coach', href: '/coachs' },
    { title: props.landing.label, href: props.meta.canonical_url },
];
</script>

<template>
    <Head :title="meta.title">
        <meta name="description" :content="meta.description" />
        <link rel="canonical" :href="meta.canonical_url" />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="meta.canonical_url" />
        <meta property="og:title" :content="meta.title" />
        <meta property="og:description" :content="meta.description" />
        <meta property="og:image" :content="meta.image_url" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="meta.title" />
        <meta name="twitter:description" :content="meta.description" />
        <meta name="twitter:image" :content="meta.image_url" />
    </Head>

    <SiteLayout :breadcrumbs="breadcrumbItems">
        <!-- Hero landing -->
        <section
            class="relative overflow-hidden border-b bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
        >
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
                <div class="absolute -left-32 -top-24 size-96 animate-pulse rounded-full bg-blue-400/20 blur-3xl"></div>
                <div class="absolute -bottom-32 -right-24 size-96 animate-pulse rounded-full bg-purple-400/20 blur-3xl" style="animation-delay: 1s"></div>
            </div>

            <div class="mx-auto max-w-7xl px-4 py-12 sm:py-16">
                <div class="max-w-2xl">
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/80 px-4 py-1.5 text-sm font-medium text-blue-700 shadow-sm backdrop-blur-md dark:border-blue-800 dark:bg-gray-800/80 dark:text-blue-300"
                    >
                        <Dumbbell class="size-4" />
                        Annuaire des coachs
                    </span>
                    <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                        {{ landing.heading }}
                    </h1>
                    <p class="mt-3 text-base text-gray-600 sm:text-lg dark:text-gray-300">
                        {{ landing.intro }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Résultats -->
        <section class="mx-auto max-w-7xl px-4 py-8">
            <p class="mb-4 text-sm text-muted-foreground">
                {{ coaches.total }} coach{{ coaches.total > 1 ? 's' : '' }} disponible{{ coaches.total > 1 ? 's' : '' }}
            </p>

            <div
                v-if="coaches.data.length"
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <CoachCard v-for="coach in coaches.data" :key="coach.slug" :coach="coach" />
            </div>

            <div
                v-else
                class="rounded-xl border border-dashed py-16 text-center text-muted-foreground"
            >
                Aucun coach n'est référencé ici pour le moment.
                <Link href="/coachs" class="font-medium text-blue-600 underline-offset-4 hover:underline dark:text-blue-400">
                    Voir tout l'annuaire
                </Link>
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
                    preserve-scroll
                >
                    <span v-html="link.label" />
                </Link>
            </div>
        </section>

        <!-- Maillage interne -->
        <section class="border-t bg-gray-50 py-14 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-4">
                <div class="grid gap-10 lg:grid-cols-2">
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                            <Dumbbell class="size-5 text-blue-600 dark:text-blue-400" />
                            Par discipline
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <Link
                                v-for="d in related.disciplines"
                                :key="d.url"
                                :href="d.url"
                                class="rounded-full border border-gray-200 bg-white px-3.5 py-1.5 text-sm font-medium text-gray-600 transition hover:border-blue-400 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-500 dark:hover:text-blue-400"
                            >
                                {{ d.label }}
                            </Link>
                        </div>
                    </div>

                    <div v-if="related.cities.length">
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                            <MapPin class="size-5 text-blue-600 dark:text-blue-400" />
                            Par ville
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <Link
                                v-for="c in related.cities"
                                :key="c.url"
                                :href="c.url"
                                class="rounded-full border border-gray-200 bg-white px-3.5 py-1.5 text-sm font-medium text-gray-600 transition hover:border-blue-400 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-500 dark:hover:text-blue-400"
                            >
                                {{ c.label }}
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="mt-10">
                    <Link
                        href="/coachs"
                        class="inline-flex items-center gap-1.5 font-semibold text-blue-600 transition hover:gap-2.5 dark:text-blue-400"
                    >
                        Parcourir tout l'annuaire
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </section>
    </SiteLayout>
</template>

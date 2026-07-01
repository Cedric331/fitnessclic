<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { Dumbbell, Calendar, ExternalLink, UserRound, Users, ClipboardList } from 'lucide-vue-next';

type Course = {
    id: number;
    name: string | null;
    session_date: string | null;
    coach_name: string | null;
    exercises_count: number;
    has_custom_layout: boolean;
    share_url: string;
};

type Coach = {
    id: number;
    name: string | null;
    avatar_url: string | null;
    profile_url: string | null;
};

const props = defineProps<{ courses: Course[]; coaches: Coach[] }>();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Mon espace', href: '/espace-client' }];

const formatDate = (date: string | null) =>
    date
        ? new Date(date).toLocaleDateString('fr-FR', {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          })
        : null;

const initials = (name: string | null) =>
    (name ?? '?')
        .split(' ')
        .map((p) => p.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase();

const coursesCount = computed(() => props.courses.length);
const coachesCount = computed(() => props.coaches.length);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mon espace" />

        <div class="w-full space-y-8 p-4 sm:p-6">
            <!-- Hero -->
            <section
                class="relative overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 p-6 sm:p-10 dark:border-gray-700 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
            >
                <!-- Halos décoratifs -->
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute -left-32 -top-24 size-96 animate-pulse rounded-full bg-blue-400/20 blur-3xl"></div>
                    <div
                        class="absolute -bottom-32 -right-24 size-96 animate-pulse rounded-full bg-purple-400/20 blur-3xl"
                        style="animation-delay: 1s"
                    ></div>
                </div>

                <div class="relative">
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/80 px-4 py-1.5 text-sm font-medium text-blue-700 shadow-sm backdrop-blur-md dark:border-blue-800 dark:bg-gray-800/80 dark:text-blue-300"
                    >
                        <Dumbbell class="size-4" />
                        Espace client
                    </span>
                    <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                        Bienvenue dans votre
                        <span
                            class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent dark:from-blue-400 dark:via-purple-400 dark:to-pink-400"
                        >
                            espace
                        </span>
                    </h1>
                    <p class="mt-3 max-w-xl text-base text-gray-600 sm:text-lg dark:text-gray-300">
                        Retrouvez toutes les séances préparées par vos coachs et gardez le lien avec eux.
                    </p>

                    <!-- Stats -->
                    <div class="mt-8 grid max-w-md gap-3 sm:grid-cols-2">
                        <div
                            class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div class="flex size-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-300">
                                <ClipboardList class="size-5" />
                            </div>
                            <div>
                                <div class="text-xl font-bold leading-none text-gray-900 dark:text-white">
                                    {{ coursesCount }}
                                </div>
                                <div class="mt-1 text-xs text-muted-foreground">
                                    séance{{ coursesCount > 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div class="flex size-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-950 dark:text-purple-300">
                                <Users class="size-5" />
                            </div>
                            <div>
                                <div class="text-xl font-bold leading-none text-gray-900 dark:text-white">
                                    {{ coachesCount }}
                                </div>
                                <div class="mt-1 text-xs text-muted-foreground">
                                    coach{{ coachesCount > 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Mes coachs -->
            <section v-if="coaches.length">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                    <Users class="size-5 text-primary" /> Mes coachs
                </h2>
                <div class="flex flex-wrap gap-3">
                    <component
                        :is="coach.profile_url ? 'a' : 'div'"
                        v-for="coach in coaches"
                        :key="coach.id"
                        :href="coach.profile_url ?? undefined"
                        :class="[
                            'flex items-center gap-3 rounded-full border bg-card py-1.5 pl-1.5 pr-4 text-sm shadow-sm',
                            coach.profile_url ? 'transition hover:bg-accent hover:shadow-md' : '',
                        ]"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-xs font-semibold text-white"
                        >
                            <img
                                v-if="coach.avatar_url"
                                :src="coach.avatar_url"
                                :alt="coach.name ?? 'Coach'"
                                class="size-full object-cover"
                            />
                            <template v-else>{{ initials(coach.name) }}</template>
                        </span>
                        <span class="font-medium">{{ coach.name }}</span>
                        <ExternalLink v-if="coach.profile_url" class="size-3.5 text-muted-foreground" />
                    </component>
                </div>
            </section>

            <!-- Mes séances -->
            <section>
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                    <ClipboardList class="size-5 text-primary" /> Mes séances
                </h2>

                <div v-if="courses.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <a
                        v-for="course in courses"
                        :key="course.id"
                        :href="course.share_url"
                        target="_blank"
                        class="group relative flex flex-col gap-3 overflow-hidden rounded-xl border bg-card p-5 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg"
                    >
                        <span
                            class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-500 opacity-0 transition group-hover:opacity-100"
                        ></span>
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Dumbbell class="size-5" />
                            </div>
                            <ExternalLink
                                class="size-4 shrink-0 text-muted-foreground opacity-0 transition group-hover:opacity-100"
                            />
                        </div>
                        <h3 class="font-semibold leading-tight">
                            {{ course.name || 'Séance' }}
                        </h3>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span v-if="course.coach_name" class="flex items-center gap-1">
                                <UserRound class="size-3.5" /> {{ course.coach_name }}
                            </span>
                            <span v-if="formatDate(course.session_date)" class="flex items-center gap-1">
                                <Calendar class="size-3.5" /> {{ formatDate(course.session_date) }}
                            </span>
                        </div>
                        <Badge v-if="!course.has_custom_layout" variant="secondary" class="mt-auto w-fit">
                            <Dumbbell class="mr-1 size-3" />
                            {{ course.exercises_count }} exercice{{ course.exercises_count > 1 ? 's' : '' }}
                        </Badge>
                    </a>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center gap-3 rounded-xl border border-dashed py-16 text-center"
                >
                    <div class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                        <ClipboardList class="size-6" />
                    </div>
                    <p class="text-muted-foreground">
                        Aucune séance pour le moment.<br />
                        Vos coachs vous en assigneront prochainement.
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

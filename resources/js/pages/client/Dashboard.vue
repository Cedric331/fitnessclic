<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { Dumbbell, Calendar, ExternalLink, UserRound } from 'lucide-vue-next';

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
    profile_url: string | null;
};

defineProps<{ courses: Course[]; coaches: Coach[] }>();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Mon espace', href: '/espace-client' }];

const formatDate = (date: string | null) =>
    date
        ? new Date(date).toLocaleDateString('fr-FR', {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          })
        : null;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mon espace" />

        <div class="mx-auto w-full max-w-5xl space-y-8 p-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Mon espace</h1>
                <p class="text-muted-foreground">
                    Retrouvez les séances préparées par vos coachs.
                </p>
            </div>

            <!-- Mes coachs -->
            <section v-if="coaches.length">
                <h2 class="mb-3 text-lg font-semibold">Mes coachs</h2>
                <div class="flex flex-wrap gap-3">
                    <component
                        :is="coach.profile_url ? 'a' : 'div'"
                        v-for="coach in coaches"
                        :key="coach.id"
                        :href="coach.profile_url ?? undefined"
                        :class="[
                            'flex items-center gap-2 rounded-full border bg-card px-4 py-2 text-sm',
                            coach.profile_url ? 'transition hover:bg-accent' : '',
                        ]"
                    >
                        <UserRound class="size-4 text-muted-foreground" />
                        <span class="font-medium">{{ coach.name }}</span>
                        <ExternalLink v-if="coach.profile_url" class="size-3.5 text-muted-foreground" />
                    </component>
                </div>
            </section>

            <!-- Mes cours -->
            <section>
                <h2 class="mb-3 text-lg font-semibold">Mes séances</h2>

                <div v-if="courses.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a
                        v-for="course in courses"
                        :key="course.id"
                        :href="course.share_url"
                        target="_blank"
                        class="group flex flex-col gap-3 rounded-xl border bg-card p-4 transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold leading-tight">
                                {{ course.name || 'Séance' }}
                            </h3>
                            <ExternalLink
                                class="size-4 shrink-0 text-muted-foreground opacity-0 transition group-hover:opacity-100"
                            />
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span v-if="course.coach_name" class="flex items-center gap-1">
                                <UserRound class="size-3.5" /> {{ course.coach_name }}
                            </span>
                            <span v-if="formatDate(course.session_date)" class="flex items-center gap-1">
                                <Calendar class="size-3.5" /> {{ formatDate(course.session_date) }}
                            </span>
                        </div>
                        <Badge v-if="!course.has_custom_layout" variant="secondary" class="w-fit">
                            <Dumbbell class="mr-1 size-3" />
                            {{ course.exercises_count }} exercice{{ course.exercises_count > 1 ? 's' : '' }}
                        </Badge>
                    </a>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-dashed py-16 text-center text-muted-foreground"
                >
                    Aucune séance pour le moment. Vos coachs vous en assigneront prochainement.
                </div>
            </section>
        </div>
    </AppLayout>
</template>

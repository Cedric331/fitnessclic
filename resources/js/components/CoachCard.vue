<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { MapPin, Crown } from 'lucide-vue-next';
import type { Coach } from '@/types/coach';

defineProps<{ coach: Coach }>();

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
    <Link
        :href="`/coachs/${coach.slug}`"
        class="group flex flex-col overflow-hidden rounded-xl border bg-card transition hover:shadow-lg"
    >
        <div class="relative aspect-[4/3] w-full overflow-hidden bg-muted">
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
            <span
                v-if="coach.is_founder"
                class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-amber-500 px-2.5 py-1 text-xs font-semibold text-white shadow-md ring-1 ring-amber-600/50"
            >
                <Crown class="size-3.5" /> Fondateur
            </span>
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
            <p v-if="coach.headline" class="line-clamp-2 text-sm text-muted-foreground">
                {{ coach.headline }}
            </p>
            <p
                v-if="coach.city"
                class="mt-auto flex items-center gap-1 text-sm text-muted-foreground"
            >
                <MapPin class="size-3.5" /> {{ coach.city }}
                <span v-if="coach.distance_km !== null" class="text-xs text-primary">
                    · à {{ coach.distance_km }} km
                </span>
            </p>
            <div v-if="coach.specialties.length" class="flex flex-wrap gap-1.5">
                <Badge v-for="s in coach.specialties" :key="s" variant="secondary">
                    {{ s }}
                </Badge>
            </div>
        </div>
    </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { register } from '@/routes';
import { Search, UserPlus } from 'lucide-vue-next';
</script>

<template>
    <section class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 py-16 sm:py-20">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-24 top-0 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -right-24 bottom-0 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-3xl px-6 text-center lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                Prêt à passer à l’action ?
            </h2>
            <p class="mx-auto mt-3 max-w-xl text-white/85">
                Que vous cherchiez le coach qui vous fera progresser ou que vous vouliez développer
                votre activité, FitnessClic vous met en relation.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Link
                    href="/coachs"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-7 py-4 font-semibold text-gray-900 shadow-lg transition-all hover:scale-105 sm:w-auto"
                >
                    <Search class="size-5" />
                    Trouver un coach
                </Link>
                <Link
                    :href="register.url()"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/60 bg-white/10 px-7 py-4 font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20 sm:w-auto"
                >
                    <UserPlus class="size-5" />
                    Devenir coach
                </Link>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { dashboard } from '@/routes';
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
</script>

<template>
    <footer class="bg-gray-900 text-gray-300">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-8 md:flex-row">
                <!-- Logo -->
                <Link :href="dashboard()" class="flex items-center">
                    <AppLogo />
                </Link>

                <!-- Liens légaux -->
                <nav class="flex flex-wrap items-center justify-center gap-6 text-sm">
                    <Link
                        href="/mentions-legales"
                        class="transition-colors hover:text-white"
                    >
                        Mentions légales
                    </Link>
                    <Link
                        href="/politique-confidentialite"
                        class="transition-colors hover:text-white"
                    >
                        Politique de confidentialité
                    </Link>
                    <Link
                        href="/conditions-utilisation"
                        class="transition-colors hover:text-white"
                    >
                        Conditions d'utilisation
                    </Link>
                    <Link
                        href="/politique-cookies"
                        class="transition-colors hover:text-white"
                    >
                        Politique des cookies
                    </Link>
                </nav>

                <!-- Copyright -->
                <p class="text-sm">
                    © {{ new Date().getFullYear() }} FitnessClic. Tous droits réservés.
                </p>
            </div>
        </div>
    </footer>
</template>


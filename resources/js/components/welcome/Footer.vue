<script setup lang="ts">
import { dashboard } from '@/routes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <footer class="bg-gray-900 text-gray-300">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-8 md:flex-row">
                <!-- Logo -->
                <Link :href="dashboard.url()" class="flex items-center gap-3 transition-opacity hover:opacity-80">
                    <img 
                        src="/assets/logo_fitnessclic.png" 
                        alt="FitnessClic Logo" 
                        class="h-10 w-auto"
                    />
                    <span class="text-lg font-bold text-white">FitnessClic</span>
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


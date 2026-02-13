<script setup lang="ts">
    import { computed } from 'vue'
    import { dashboard } from '@/routes'
    import { Link } from '@inertiajs/vue3'
    
    const year = computed(() => new Date().getFullYear())
    
    const legalLinks = [
        { label: 'Blog', href: '/blog' },
        { label: 'Mentions légales', href: '/mentions-legales' },
        { label: 'Politique de confidentialité', href: '/politique-confidentialite' },
        { label: "Conditions d'utilisation", href: '/conditions-utilisation' },
        { label: 'Politique des cookies', href: '/politique-cookies' },
    ]
    </script>
    
    <template>
        <footer class="bg-gray-900 text-gray-300">
            <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-8 md:flex-row">
    
                    <!-- Logo -->
                    <Link
                        :href="dashboard.url()"
                        aria-label="FitnessClic - Accueil"
                        class="flex items-center gap-3 transition-opacity hover:opacity-80"
                    >
                        <img
                            src="/assets/logo_fitnessclic.png"
                            alt="FitnessClic - Logiciel pour coachs sportifs"
                            width="40"
                            height="40"
                            loading="lazy"
                            class="h-10 w-auto"
                        />
                        <span class="text-lg font-bold text-white">
                            FitnessClic
                        </span>
                    </Link>
    
                    <!-- Liens légaux -->
                    <nav
                        aria-label="Liens légaux"
                        class="flex flex-wrap items-center justify-center gap-6 text-sm"
                    >
                        <Link
                            v-for="link in legalLinks"
                            :key="link.href"
                            :href="link.href"
                            class="transition-colors hover:text-white"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>
    
                    <!-- Copyright -->
                    <div class="flex flex-col items-center gap-1 text-sm text-center">
                        <p>
                            © {{ year }} FitnessClic. Tous droits réservés.
                        </p>
                        <p>
                            Créé par
                            <a
                                href="https://web-discovery.fr"
                                target="_blank"
                                class="font-medium text-blue-500 hover:text-gray-300"
                                rel="noopener noreferrer"
                            >
                                Web Discovery
                            </a>
                        </p>
                    </div>
    
                </div>
            </div>
        </footer>
    </template>
    
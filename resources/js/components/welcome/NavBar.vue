<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { useAppearance } from '@/composables/useAppearance';
import { Moon, Sun, Menu, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const { appearance, updateAppearance } = useAppearance();
const systemPrefersDark = ref(false);
const mobileMenuOpen = ref(false);

// Mettre à jour systemPrefersDark
const updateSystemPreference = () => {
    if (typeof window !== 'undefined') {
        systemPrefersDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
};

const isDarkMode = computed(() => {
    if (appearance.value === 'system') {
        return systemPrefersDark.value;
    }
    return appearance.value === 'dark';
});

const toggleTheme = () => {
    const currentTheme = appearance.value;
    let newTheme: 'light' | 'dark';
    
    if (currentTheme === 'system') {
        // Si on est en mode system, on bascule vers light ou dark selon la préférence actuelle
        newTheme = systemPrefersDark.value ? 'light' : 'dark';
    } else {
        newTheme = currentTheme === 'light' ? 'dark' : 'light';
    }
    
    updateAppearance(newTheme);
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

// Initialiser et écouter les changements
onMounted(() => {
    updateSystemPreference();
    
    if (typeof window !== 'undefined') {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handleChange = () => {
            updateSystemPreference();
        };
        mediaQuery.addEventListener('change', handleChange);
    }
});

</script>

<template>
    <header class="sticky top-0 z-50 w-full border-b border-gray-200 bg-white/95 backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/95">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <Link :href="dashboard()" class="flex items-center">
                <AppLogo />
            </Link>
            
            <!-- Menu desktop -->
            <div class="hidden items-center gap-4 md:flex">
                <!-- Bouton de basculement de thème -->
                <button
                    @click="toggleTheme"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                    :aria-label="isDarkMode ? 'Passer en mode clair' : 'Passer en mode sombre'"
                    title="Changer le thème"
                >
                    <Sun
                        v-if="isDarkMode"
                        class="h-5 w-5"
                    />
                    <Moon
                        v-else
                        class="h-5 w-5"
                    />
                </button>

                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-lg border border-transparent px-5 py-2 text-sm font-medium text-gray-700 transition-colors hover:border-gray-300 dark:text-gray-200 dark:hover:border-gray-600"
                    >
                        Connexion
                    </Link>
                    <Link
                        :href="register()"
                        class="inline-block rounded-lg border border-blue-600 bg-blue-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600"
                    >
                        S'inscrire
                    </Link>
                </template>
            </div>

            <!-- Menu mobile - Bouton hamburger -->
            <div class="flex items-center gap-2 md:hidden">
                <!-- Bouton de basculement de thème (mobile) -->
                <button
                    @click="toggleTheme"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                    :aria-label="isDarkMode ? 'Passer en mode clair' : 'Passer en mode sombre'"
                    title="Changer le thème"
                >
                    <Sun
                        v-if="isDarkMode"
                        class="h-5 w-5"
                    />
                    <Moon
                        v-else
                        class="h-5 w-5"
                    />
                </button>

                <!-- Bouton hamburger -->
                <button
                    @click="toggleMobileMenu"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                    aria-label="Menu"
                    aria-expanded="false"
                >
                    <Menu
                        v-if="!mobileMenuOpen"
                        class="h-6 w-6"
                    />
                    <X
                        v-else
                        class="h-6 w-6"
                    />
                </button>
            </div>
        </nav>

        <!-- Menu mobile - Panneau déroulant -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-if="mobileMenuOpen"
                class="border-t border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 md:hidden"
            >
                <div class="space-y-1 px-6 pb-4 pt-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        @click="closeMobileMenu"
                        class="block rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            @click="closeMobileMenu"
                            class="block rounded-lg border border-transparent px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Connexion
                        </Link>
                        <Link
                            :href="register()"
                            @click="closeMobileMenu"
                            class="block rounded-lg border border-blue-600 bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600"
                        >
                            S'inscrire
                        </Link>
                    </template>
                </div>
            </div>
        </Transition>
    </header>
</template>


<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { X } from 'lucide-vue-next';

const showBanner = ref(false);
const cookiesAccepted = ref(false);

onMounted(() => {
    // Vérifier si l'utilisateur a déjà accepté les cookies
    const accepted = localStorage.getItem('cookies-accepted');
    if (!accepted) {
        // Afficher le banner après un court délai pour une meilleure UX
        setTimeout(() => {
            showBanner.value = true;
        }, 1000);
    } else {
        cookiesAccepted.value = accepted === 'true';
    }
});

const acceptCookies = () => {
    localStorage.setItem('cookies-accepted', 'true');
    cookiesAccepted.value = true;
    showBanner.value = false;
};

const rejectCookies = () => {
    localStorage.setItem('cookies-accepted', 'false');
    cookiesAccepted.value = false;
    showBanner.value = false;
};

const closeBanner = () => {
    showBanner.value = false;
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="showBanner"
            class="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex-1">
                        <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                            Gestion des cookies
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Nous utilisons des cookies pour améliorer votre expérience, analyser le trafic du site et personnaliser le contenu. 
                            En continuant à utiliser ce site, vous acceptez notre utilisation des cookies conformément à notre 
                            <a href="/politique-cookies" class="text-blue-600 underline hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                politique des cookies
                            </a>.
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-3">
                        <button
                            type="button"
                            @click="rejectCookies"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Refuser
                        </button>
                        <button
                            type="button"
                            @click="acceptCookies"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                        >
                            Accepter
                        </button>
                        <button
                            type="button"
                            @click="closeBanner"
                            class="rounded-lg p-2 text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-200"
                            aria-label="Fermer"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>


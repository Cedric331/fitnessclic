<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Check, Star, Zap, CreditCard, FileText, Users, Library, Tag, Mail, Settings, X } from 'lucide-vue-next';
import { computed, ref, watch, nextTick } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { useNotifications } from '@/composables/useNotifications';

interface Props {
    hasActiveSubscription: boolean;
    onTrial: boolean;
    trialEndsAt: string | null;
    daysLeftInTrial: number;
    subscriptionStatus: string | null;
    isCancelling: boolean;
    cancelsAt: string | null;
    daysUntilCancellation: number;
}

const props = withDefaults(defineProps<Props>(), {
    hasActiveSubscription: false,
    onTrial: false,
    trialEndsAt: null,
    daysLeftInTrial: 0,
    subscriptionStatus: null,
    isCancelling: false,
    cancelsAt: null,
    daysUntilCancellation: 0,
});

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();

// Écouter les messages flash
const shownFlashMessages = ref(new Set<string>());

watch(() => (page.props as any).flash, (flash) => {
    if (!flash) return;
    
    const successKey = flash.success ? `success-${flash.success}` : null;
    const errorKey = flash.error ? `error-${flash.error}` : null;
    
    if (successKey && !shownFlashMessages.value.has(successKey)) {
        shownFlashMessages.value.add(successKey);
        nextTick(() => {
            setTimeout(() => {
                notifySuccess(flash.success);
            }, 100);
        });
        setTimeout(() => {
            shownFlashMessages.value.delete(successKey);
        }, 4500);
    }
    
    if (errorKey && !shownFlashMessages.value.has(errorKey)) {
        shownFlashMessages.value.add(errorKey);
        nextTick(() => {
            setTimeout(() => {
                notifyError(flash.error);
            }, 100);
        });
        setTimeout(() => {
            shownFlashMessages.value.delete(errorKey);
        }, 6500);
    }
}, { immediate: true });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Abonnement',
        href: '/subscription',
    },
];

const handleCheckout = (e: Event) => {
    e.preventDefault();
    // Utiliser replace pour forcer une vraie navigation HTTP
    window.location.replace('/subscription/checkout');
};

const handlePortal = (e: Event) => {
    e.preventDefault();
    // Utiliser replace pour forcer une vraie navigation HTTP
    window.location.replace('/subscription/portal');
};

const freeFeatures = [
    { text: 'Création de séances illimitées (sans enregistrement)', icon: Check },
    { text: 'Accès à tous les exercices de la bibliothèque publique', icon: Check },
    { text: 'Impression des séances', icon: Check },
    { text: 'Support par email', icon: Check },
];

const freeLimitations = [
    { text: 'Pas de création de clients', icon: X },
    { text: 'Pas d\'import ou création d\'exercices personnalisés', icon: X },
    { text: 'Pas de création de catégories d\'exercices', icon: X },
    { text: 'Pas d\'enregistrement des séances', icon: X },
    { text: 'Pas d\'export PDF des séances', icon: X },
];

const proFeatures = [
    { text: 'Toutes les fonctionnalités du compte gratuit', icon: Check },
    { text: 'Clients illimités', icon: Check },
    { text: 'Export des séances en PDF', icon: Check },
    { text: 'Sauvegardes illimitées de toutes vos séances', icon: Check },
    { text: 'Import d\'exercices illimités dans la bibliothèque', icon: Check },
    { text: 'Création de nouvelles catégories d\'exercices illimités', icon: Check },
    { text: 'Support email prioritaire', icon: Check },
];
</script>

<template>
    <Head title="Abonnement FitnessClic Pro" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center justify-center overflow-x-auto rounded-xl p-4 md:p-6">
            <div class="w-full max-w-3xl space-y-6">
                <!-- Header -->
                <div class="space-y-3 text-center">
                    <h1 class="text-4xl font-bold tracking-tight">Abonnement FitnessClic Pro</h1>
                    <p class="text-lg text-muted-foreground">
                        Débloquez tout le potentiel de FitnessClic avec un abonnement Pro
                    </p>
                </div>

                <!-- Trial Banner -->
                <div
                    v-if="onTrial && !hasActiveSubscription"
                    class="rounded-xl border-2 border-blue-200 bg-gradient-to-r from-blue-50 to-blue-100 p-5 dark:border-blue-800 dark:from-blue-950 dark:to-blue-900"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500">
                            <Star class="h-5 w-5 text-white" />
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-blue-900 dark:text-blue-100">
                                Période d'essai active
                            </p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                Il vous reste {{ daysLeftInTrial }} jour{{ daysLeftInTrial > 1 ? 's' : '' }} pour profiter de toutes les fonctionnalités Pro.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Banner -->
                <div
                    v-if="isCancelling && hasActiveSubscription"
                    class="rounded-xl border-2 border-orange-200 bg-gradient-to-r from-orange-50 to-orange-100 p-5 dark:border-orange-800 dark:from-orange-950 dark:to-orange-900"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-500">
                            <X class="h-5 w-5 text-white" />
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-orange-900 dark:text-orange-100">
                                Abonnement en cours d'annulation
                            </p>
                            <p class="text-sm text-orange-700 dark:text-orange-300">
                                Votre abonnement prendra fin dans {{ daysUntilCancellation }} jour{{ daysUntilCancellation > 1 ? 's' : '' }}. Vous pouvez le réactiver depuis la page de gestion de votre abonnement.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Subscription Card -->
                <Card class="border-2 shadow-xl">
                    <CardHeader class="space-y-4 text-center pb-6">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-yellow-400 to-yellow-500 shadow-lg">
                            <Star class="h-10 w-10 text-white fill-white" />
                        </div>
                        <div>
                            <CardTitle class="text-3xl font-bold">FitnessClic Pro</CardTitle>
                            <p class="mt-2 text-sm text-muted-foreground">
                                L'abonnement parfait pour les professionnels du fitness
                            </p>
                        </div>
                        <div class="mt-6">
                            <div class="flex items-baseline justify-center gap-2">
                                <span class="text-5xl font-bold">5€</span>
                                <span class="text-xl text-muted-foreground">/mois</span>
                            </div>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Facturé mensuellement, annulable à tout moment
                            </p>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6 px-8 pb-8">
                        <!-- Features List -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold">Ce qui est inclus :</h3>
                            <ul class="space-y-3">
                                <li
                                    v-for="(feature, index) in proFeatures"
                                    :key="index"
                                    class="flex items-start gap-3"
                                >
                                    <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <component
                                            :is="feature.icon"
                                            class="h-4 w-4 text-primary"
                                        />
                                    </div>
                                    <span class="text-sm leading-relaxed">{{ feature.text }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Action Button -->
                        <div class="pt-4">
                            <Button
                                v-if="!hasActiveSubscription"
                                @click="handleCheckout"
                                class="w-full bg-gradient-to-r from-primary to-primary/90 text-base font-semibold shadow-lg transition-all hover:shadow-xl"
                                size="lg"
                            >
                                <Zap class="mr-2 h-5 w-5" />
                                Passer à Pro maintenant
                            </Button>
                            <Button
                                v-else
                                @click="handlePortal"
                                variant="outline"
                                class="w-full border-2 text-base font-semibold"
                                size="lg"
                            >
                                <Settings class="mr-2 h-5 w-5" />
                                Gérer mon abonnement
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Free Plan Info -->
                <Card v-if="!hasActiveSubscription" class="border-dashed bg-muted/30">
                    <CardHeader>
                        <CardTitle class="text-lg font-semibold">Plan Gratuit</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Profitez des fonctionnalités de base gratuitement
                        </p>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Fonctionnalités incluses -->
                        <div>
                            <h4 class="text-sm font-semibold mb-3">Fonctionnalités incluses :</h4>
                            <ul class="space-y-2.5">
                                <li
                                    v-for="(feature, index) in freeFeatures"
                                    :key="index"
                                    class="flex items-start gap-3"
                                >
                                    <component
                                        :is="feature.icon"
                                        class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500"
                                    />
                                    <span class="text-sm text-muted-foreground">{{ feature.text }}</span>
                                </li>
                            </ul>
                        </div>
                        <!-- Limitations -->
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                            <h4 class="text-xs font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Limitations :</h4>
                            <ul class="space-y-2">
                                <li
                                    v-for="(limitation, index) in freeLimitations"
                                    :key="index"
                                    class="flex items-start gap-2"
                                >
                                    <X class="h-4 w-4 shrink-0 text-yellow-600 dark:text-yellow-400 mt-0.5" />
                                    <span class="text-xs text-yellow-700 dark:text-yellow-300">{{ limitation.text }}</span>
                                </li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>


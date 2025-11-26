<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import NavBar from '@/components/welcome/NavBar.vue';
import HeroSection from '@/components/welcome/HeroSection.vue';
import FeaturesSection from '@/components/welcome/FeaturesSection.vue';
import ScreenshotsSection from '@/components/welcome/ScreenshotsSection.vue';
import BenefitsSection from '@/components/welcome/BenefitsSection.vue';
import PricingSection from '@/components/welcome/PricingSection.vue';
import CtaSection from '@/components/welcome/CtaSection.vue';
import Footer from '@/components/welcome/Footer.vue';
import CookieBanner from '@/components/welcome/CookieBanner.vue';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="FitnessClic - Créez vos séances d'entraînement en quelques clics">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col bg-white dark:bg-gray-900">
        <NavBar />
        <main class="flex-1">
            <HeroSection />
            <FeaturesSection />
            <ScreenshotsSection />
            <BenefitsSection />
            <PricingSection />
            <CtaSection />
        </main>
        <Footer />
        <CookieBanner />
    </div>
</template>

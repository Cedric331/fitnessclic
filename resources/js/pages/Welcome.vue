<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
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

// Configuration SEO
const siteName = 'FitnessClic';
const defaultSiteUrl = 'https://fitnessclic.com';
const siteUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin;
    }
    return defaultSiteUrl;
});
const currentUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.href;
    }
    return siteUrl.value;
});
const title = 'FitnessClic - Créez vos séances d\'entraînement en quelques clics';
const description = 'L\'outil professionnel pour les coachs sportifs et particuliers. Créez, organisez et partagez vos programmes d\'entraînement facilement. Bibliothèque d\'exercices, gestion de clients, export PDF. Compte gratuit disponible.';
const keywords = 'coach sportif, séance d\'entraînement, programme fitness, création séance, gestion clients, bibliothèque exercices, fitness, sport, entraînement personnalisé';
// URL absolue de l'image pour Open Graph (Facebook nécessite une URL absolue)
const imageUrl = computed(() => {
    const baseUrl = siteUrl.value;
    // S'assurer que l'URL est absolue
    if (baseUrl.startsWith('http://') || baseUrl.startsWith('https://')) {
        return `${baseUrl}/assets/logo_fitnessclic.png`;
    }
    return `${defaultSiteUrl}/assets/logo_fitnessclic.png`;
});
const twitterHandle = '@FitnessClic';

const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'WebApplication',
    name: siteName,
    applicationCategory: 'HealthApplication',
    operatingSystem: 'Web',
    offers: {
        '@type': 'Offer',
        price: '0',
        priceCurrency: 'EUR',
    },
    aggregateRating: {
        '@type': 'AggregateRating',
        ratingValue: '4.8',
        ratingCount: '150',
    },
    description: description,
    url: siteUrl.value,
    image: imageUrl.value,
    author: {
        '@type': 'Organization',
        name: siteName,
    },
}));

const organizationData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: siteName,
    url: siteUrl.value,
    logo: imageUrl.value,
    sameAs: [
        // 'https://www.facebook.com/fitnessclic',
        // 'https://twitter.com/fitnessclic',
        // 'https://www.instagram.com/fitnessclic',
    ],
}));

let structuredDataScripts: HTMLScriptElement[] = [];

onMounted(() => {
    const script1 = document.createElement('script');
    script1.type = 'application/ld+json';
    script1.id = 'structured-data-webapp';
    script1.textContent = JSON.stringify(structuredData.value);
    document.head.appendChild(script1);
    structuredDataScripts.push(script1);

    const script2 = document.createElement('script');
    script2.type = 'application/ld+json';
    script2.id = 'structured-data-org';
    script2.textContent = JSON.stringify(organizationData.value);
    document.head.appendChild(script2);
    structuredDataScripts.push(script2);
});

onUnmounted(() => {
    structuredDataScripts.forEach((script) => {
        if (script.parentNode) {
            script.parentNode.removeChild(script);
        }
    });
    structuredDataScripts = [];
});
</script>

<template>
    <Head :title="title">
        <!-- Meta Tags de base -->
        <meta name="description" :content="description" />
        <meta name="keywords" :content="keywords" />
        <meta name="author" :content="siteName" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <meta name="language" content="French" />
        <meta name="revisit-after" content="7 days" />
        <link rel="canonical" :href="currentUrl" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="currentUrl" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="imageUrl" />
        <meta property="og:image:secure_url" :content="imageUrl" />
        <meta property="og:image:type" content="image/png" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:image:alt" :content="`${siteName} - ${description}`" />
        <meta property="og:site_name" :content="siteName" />
        <meta property="og:locale" content="fr_FR" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" :content="currentUrl" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="imageUrl" />
        <meta name="twitter:image:alt" :content="`${siteName} - ${description}`" />
        <meta name="twitter:creator" :content="twitterHandle" />
        <meta name="twitter:site" :content="twitterHandle" />

        <!-- Mobile -->
        <meta name="theme-color" content="#2563eb" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="apple-mobile-web-app-title" :content="siteName" />

        <!-- Preconnect pour performance -->
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="dns-prefetch" href="https://rsms.me/" />
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link rel="dns-prefetch" href="https://fonts.bunny.net" />

        <!-- Stylesheet -->
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

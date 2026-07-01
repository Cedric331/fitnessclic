<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import NavBar from '@/components/welcome/NavBar.vue';
import MarketplaceHero from '@/components/welcome/MarketplaceHero.vue';
import FeaturedCoaches from '@/components/welcome/FeaturedCoaches.vue';
import DisciplinesSection from '@/components/welcome/DisciplinesSection.vue';
import HowItWorksSection from '@/components/welcome/HowItWorksSection.vue';
import BecomeCoachSection from '@/components/welcome/BecomeCoachSection.vue';
import CoachToolsSection from '@/components/welcome/CoachToolsSection.vue';
import FaqSection from '@/components/welcome/FaqSection.vue';
import FinalCtaSection from '@/components/welcome/FinalCtaSection.vue';
import Footer from '@/components/welcome/Footer.vue';
import CookieBanner from '@/components/welcome/CookieBanner.vue';
import PopinModal from '@/components/welcome/PopinModal.vue';
import type { Coach } from '@/types/coach';

withDefaults(
    defineProps<{
        canRegister: boolean;
        featuredCoaches?: Coach[];
    }>(),
    {
        canRegister: true,
        featuredCoaches: () => [],
    },
);

// Configuration SEO
const siteName = 'FitnessClic';
// URL de base en HTTPS (Facebook nécessite HTTPS pour og:image:secure_url)
const defaultSiteUrl = 'https://fitnessclic.com';
const siteUrl = computed(() => {
    if (typeof window !== 'undefined') {
        // Utiliser HTTPS même si la page est en HTTP
        const origin = window.location.origin;
        return origin.replace('http://', 'https://');
    }
    return defaultSiteUrl;
});
const currentUrl = computed(() => {
    if (typeof window !== 'undefined') {
        // Utiliser HTTPS même si la page est en HTTP
        const href = window.location.href;
        return href.replace('http://', 'https://');
    }
    return defaultSiteUrl;
});
const title = 'FitnessClic - Trouvez votre coach sportif près de chez vous ou en ligne';
const description = 'FitnessClic, la marketplace des coachs sportifs. Comparez les profils, les spécialités et les tarifs, puis contactez votre coach en quelques clics. Coachs vérifiés, près de chez vous ou en visio.';
const keywords = 'coach sportif, trouver un coach, coaching sportif, personal trainer, musculation, perte de poids, yoga, préparation physique, coach en ligne, coach à domicile';
// URL absolue de l'image pour Open Graph (Facebook nécessite une URL absolue en HTTPS)
// Valeur statique pour garantir l'accessibilité lors du scraping par Facebook
const imageUrl = `${defaultSiteUrl}/assets/logo_fitnessclic.png`;
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
    image: imageUrl,
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
    logo: imageUrl,
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
            <MarketplaceHero />
            <FeaturedCoaches :coaches="featuredCoaches" />
            <DisciplinesSection />
            <HowItWorksSection />
            <BecomeCoachSection />
            <CoachToolsSection />
            <FaqSection />
            <FinalCtaSection />
        </main>
        <Footer />
        <CookieBanner />
        <PopinModal />
    </div>
</template>

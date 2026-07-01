<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

type Faq = { q: string; a: string };

const clientFaqs: Faq[] = [
    {
        q: 'Combien coûte FitnessClic pour trouver un coach ?',
        a: 'Rien. La recherche d’un coach, la consultation des profils et la prise de contact via la messagerie sont entièrement gratuites pour les clients. Vous ne payez que les prestations convenues directement avec le coach.',
    },
    {
        q: 'Puis-je trouver un coach près de chez moi ou uniquement en ligne ?',
        a: 'Les deux. Vous pouvez rechercher par ville, activer « autour de moi » pour trouver des coachs de votre secteur, ou choisir un coach qui propose des séances en visio partout en France.',
    },
    {
        q: 'Comment contacter un coach ?',
        a: 'Depuis la fiche d’un coach, envoyez-lui un message via la messagerie intégrée. Il vous répond directement sur la plateforme : pas besoin d’échanger vos coordonnées avant d’être à l’aise.',
    },
    {
        q: 'Comment choisir le bon coach ?',
        a: 'Comparez les profils : spécialités, présentation, tarif horaire et localisation. Vous pouvez contacter plusieurs coachs pour échanger sur votre objectif avant de vous décider.',
    },
];

const coachFaqs: Faq[] = [
    {
        q: 'Créer mon profil coach est-il gratuit ?',
        a: 'Oui. La création de votre profil et sa présence dans l’annuaire sont gratuites et sans engagement. Les outils premium (clients illimités, export PDF des séances, etc.) sont disponibles dès 5 €/mois.',
    },
    {
        q: 'Quels outils sont inclus pour les coachs ?',
        a: 'Bibliothèque d’exercices, création de séances et de programmes, export PDF, gestion des clients et messagerie intégrée. Tout est réuni pour gérer votre activité au même endroit.',
    },
    {
        q: 'Y a-t-il un engagement de durée ?',
        a: 'Non. L’abonnement premium est mensuel et annulable à tout moment. Vous gardez votre profil gratuit même sans abonnement.',
    },
    {
        q: 'Comment reçois-je des demandes de clients ?',
        a: 'Une fois votre profil publié, vous apparaissez dans l’annuaire et les recherches locales. Les clients intéressés vous contactent directement via la messagerie.',
    },
];

const groups = [
    { title: 'Vous cherchez un coach', faqs: clientFaqs },
    { title: 'Vous êtes coach', faqs: coachFaqs },
];

// Un seul panneau ouvert à la fois, identifié par une clé "groupe-index".
const openKey = ref<string | null>('0-0');
const toggle = (key: string) => {
    openKey.value = openKey.value === key ? null : key;
};

// JSON-LD FAQPage pour le SEO (rich snippets Google).
let faqScript: HTMLScriptElement | null = null;
onMounted(() => {
    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: [...clientFaqs, ...coachFaqs].map((faq) => ({
            '@type': 'Question',
            name: faq.q,
            acceptedAnswer: { '@type': 'Answer', text: faq.a },
        })),
    };
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.id = 'structured-data-faq';
    script.textContent = JSON.stringify(structuredData);
    document.head.appendChild(script);
    faqScript = script;
});
onUnmounted(() => {
    if (faqScript?.parentNode) {
        faqScript.parentNode.removeChild(faqScript);
    }
    faqScript = null;
});
</script>

<template>
    <section class="bg-white py-20 sm:py-24 dark:bg-gray-900">
        <div class="mx-auto max-w-3xl px-6 lg:px-8">
            <div class="mb-10 text-center">
                <span class="text-sm font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                    Questions fréquentes
                </span>
                <h2 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                    Tout ce que vous voulez savoir
                </h2>
            </div>

            <div class="space-y-8">
                <div v-for="(group, gi) in groups" :key="group.title">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ group.title }}
                    </h3>
                    <div class="divide-y divide-gray-200 overflow-hidden rounded-2xl border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <div v-for="(faq, fi) in group.faqs" :key="faq.q">
                            <h4>
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                    :aria-expanded="openKey === `${gi}-${fi}`"
                                    @click="toggle(`${gi}-${fi}`)"
                                >
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ faq.q }}</span>
                                    <ChevronDown
                                        class="size-5 shrink-0 text-gray-400 transition-transform duration-200"
                                        :class="openKey === `${gi}-${fi}` ? 'rotate-180' : ''"
                                    />
                                </button>
                            </h4>
                            <div v-show="openKey === `${gi}-${fi}`" class="px-5 pb-5 text-gray-600 dark:text-gray-300">
                                {{ faq.a }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

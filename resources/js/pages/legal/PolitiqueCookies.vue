<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes';
import { ArrowLeft } from 'lucide-vue-next';
</script>

<template>
    <Head title="Politique des cookies" />
    <div class="min-h-screen bg-white dark:bg-gray-900">
        <div class="mx-auto max-w-4xl px-6 py-12 lg:px-8">
            <Link
                :href="home()"
                class="mb-8 inline-flex items-center gap-2 text-sm text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
                <ArrowLeft class="h-4 w-4" />
                Retour à l'accueil
            </Link>

            <div class="prose prose-lg max-w-none dark:prose-invert">
                <h1 class="mb-8 text-4xl font-bold text-gray-900 dark:text-white">
                    Politique des cookies
                </h1>

                <section class="mb-8">
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Cette politique des cookies explique ce que sont les cookies, comment FitnessClic les utilise, et comment vous pouvez contrôler leur utilisation.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        1. Qu'est-ce qu'un cookie ?
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Un cookie est un petit fichier texte stocké sur votre appareil (ordinateur, tablette, smartphone) lorsque vous visitez un site web. Les cookies permettent au site de mémoriser vos actions et préférences pendant une certaine période, afin que vous n'ayez pas à les ressaisir à chaque fois que vous revenez sur le site ou naviguez d'une page à l'autre.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        2. Comment utilisons-nous les cookies ?
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        FitnessClic utilise les cookies pour :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Assurer le bon fonctionnement du site</li>
                        <li>Mémoriser vos préférences (langue, thème, etc.)</li>
                        <li>Améliorer votre expérience utilisateur</li>
                        <li>Analyser l'utilisation du site pour l'améliorer</li>
                        <li>Assurer la sécurité de votre session</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        3. Types de cookies utilisés
                    </h2>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        3.1 Cookies strictement nécessaires
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Ces cookies sont essentiels au fonctionnement du site et ne peuvent pas être désactivés. Ils sont généralement définis en réponse à des actions que vous effectuez et qui équivalent à une demande de services, comme la définition de vos préférences de confidentialité, la connexion ou le remplissage de formulaires.
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li><strong>Cookies de session</strong> : nécessaires pour maintenir votre session active</li>
                        <li><strong>Cookies de sécurité</strong> : pour protéger contre les attaques CSRF</li>
                        <li><strong>Cookies de préférences</strong> : pour mémoriser vos choix (thème, langue)</li>
                    </ul>

                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        3.2 Cookies de performance
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Ces cookies nous permettent de compter les visites et les sources de trafic afin d'améliorer les performances de notre site. Ils nous aident à savoir quelles pages sont les plus et les moins populaires.
                    </p>

                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        3.3 Cookies de fonctionnalité
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Ces cookies permettent au site de fournir des fonctionnalités et une personnalisation améliorées. Ils peuvent être définis par nous ou par des fournisseurs tiers dont nous avons ajouté les services à nos pages.
                    </p>

                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        3.4 Cookies de ciblage/publicité
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Actuellement, FitnessClic n'utilise pas de cookies de ciblage ou publicitaires. Si cela change à l'avenir, nous mettrons à jour cette politique et vous en informerons.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        4. Cookies tiers
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Certains cookies sont placés par des services tiers qui apparaissent sur nos pages. Nous n'avons pas de contrôle sur ces cookies tiers. Nous vous encourageons à consulter les politiques de cookies de ces services tiers.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Exemples de services tiers pouvant utiliser des cookies :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Services d'analyse (Google Analytics, etc.)</li>
                        <li>Services de support client</li>
                        <li>Réseaux sociaux (si intégrés)</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        5. Durée de conservation des cookies
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Les cookies que nous utilisons ont différentes durées de vie :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li><strong>Cookies de session</strong> : supprimés à la fermeture du navigateur</li>
                        <li><strong>Cookies persistants</strong> : restent sur votre appareil pendant une période déterminée (généralement jusqu'à 12 mois maximum)</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        6. Gestion des cookies
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Vous avez le contrôle sur les cookies. Vous pouvez configurer votre navigateur pour refuser les cookies ou pour vous alerter lorsque des cookies sont envoyés. Cependant, si vous désactivez les cookies, certaines fonctionnalités du site peuvent ne pas fonctionner correctement.
                    </p>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        6.1 Paramètres du navigateur
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        La plupart des navigateurs vous permettent de :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Voir quels cookies vous avez et les supprimer</li>
                        <li>Bloquer les cookies de sites tiers</li>
                        <li>Bloquer tous les cookies</li>
                        <li>Supprimer tous les cookies lorsque vous fermez le navigateur</li>
                    </ul>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Voici comment gérer les cookies dans les principaux navigateurs :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li><strong>Chrome</strong> : Paramètres > Confidentialité et sécurité > Cookies</li>
                        <li><strong>Firefox</strong> : Options > Vie privée et sécurité > Cookies</li>
                        <li><strong>Safari</strong> : Préférences > Confidentialité > Cookies</li>
                        <li><strong>Edge</strong> : Paramètres > Confidentialité > Cookies</li>
                    </ul>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        6.2 Bannière de consentement
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Lors de votre première visite, une bannière vous permet de choisir quels cookies accepter. Vous pouvez modifier vos préférences à tout moment en supprimant les cookies de votre navigateur et en rafraîchissant la page.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        7. Cookies et données personnelles
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Certains cookies peuvent contenir des données personnelles. Pour plus d'informations sur la manière dont nous traitons vos données personnelles, consultez notre
                        <Link
                            href="/politique-confidentialite"
                            class="text-blue-600 underline hover:text-blue-700 dark:text-blue-400"
                        >
                            Politique de confidentialité
                        </Link>.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        8. Modifications de cette politique
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Nous pouvons mettre à jour cette politique des cookies de temps à autre pour refléter les changements dans les cookies que nous utilisons ou pour d'autres raisons opérationnelles, légales ou réglementaires. Nous vous encourageons à consulter régulièrement cette page.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        9. Contact
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Pour toute question concernant notre utilisation des cookies, contactez-nous à : [À compléter]
                    </p>
                </section>

                <section class="mb-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dernière mise à jour : {{ new Date().toLocaleDateString('fr-FR') }}
                    </p>
                </section>
            </div>
        </div>
    </div>
</template>


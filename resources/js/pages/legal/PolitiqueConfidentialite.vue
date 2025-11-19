<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes';
import { ArrowLeft } from 'lucide-vue-next';
</script>

<template>
    <Head title="Politique de confidentialité" />
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
                    Politique de confidentialité
                </h1>

                <section class="mb-8">
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        La présente politique de confidentialité décrit la manière dont FitnessClic collecte, utilise et protège vos informations personnelles lorsque vous utilisez notre service.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        En utilisant FitnessClic, vous acceptez la collecte et l'utilisation d'informations conformément à cette politique.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        1. Données collectées
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Nous collectons les types d'informations suivants :
                    </p>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        Données personnelles
                    </h3>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Nom et prénom</li>
                        <li>Adresse e-mail</li>
                        <li>Mot de passe (crypté)</li>
                        <li>Informations de profil (optionnelles)</li>
                    </ul>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        Données d'utilisation
                    </h3>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Adresse IP</li>
                        <li>Type de navigateur</li>
                        <li>Pages visitées</li>
                        <li>Date et heure de connexion</li>
                        <li>Données de navigation</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        2. Utilisation des données
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        FitnessClic utilise les données collectées pour :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Fournir et maintenir notre service</li>
                        <li>Vous notifier des changements concernant notre service</li>
                        <li>Vous permettre de participer aux fonctionnalités interactives</li>
                        <li>Fournir un support client</li>
                        <li>Collecter des données précieuses pour améliorer le service</li>
                        <li>Détecter, prévenir et résoudre les problèmes techniques</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        3. Base légale du traitement
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Conformément au RGPD, nous traitons vos données personnelles sur la base de :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li><strong>Votre consentement</strong> : lorsque vous acceptez explicitement le traitement de vos données</li>
                        <li><strong>L'exécution d'un contrat</strong> : pour fournir les services demandés</li>
                        <li><strong>Notre intérêt légitime</strong> : pour améliorer nos services et assurer la sécurité</li>
                        <li><strong>Une obligation légale</strong> : lorsque la loi l'exige</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        4. Conservation des données
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Nous conservons vos données personnelles uniquement aussi longtemps que nécessaire aux fins décrites dans cette politique, sauf si une période de conservation plus longue est requise ou permise par la loi.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Lorsque vous supprimez votre compte, nous supprimons vos données personnelles dans un délai de 30 jours, sauf si nous sommes tenus de les conserver pour des raisons légales.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        5. Partage des données
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Nous ne vendons, n'échangeons ni ne louons vos données personnelles à des tiers. Nous pouvons partager vos informations uniquement dans les cas suivants :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Avec votre consentement explicite</li>
                        <li>Pour se conformer à une obligation légale</li>
                        <li>Pour protéger nos droits et notre sécurité</li>
                        <li>Avec nos prestataires de services (hébergement, analytics) sous contrat de confidentialité</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        6. Vos droits
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Conformément au RGPD, vous disposez des droits suivants :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li><strong>Droit d'accès</strong> : vous pouvez demander une copie de vos données personnelles</li>
                        <li><strong>Droit de rectification</strong> : vous pouvez corriger vos données inexactes</li>
                        <li><strong>Droit à l'effacement</strong> : vous pouvez demander la suppression de vos données</li>
                        <li><strong>Droit à la limitation</strong> : vous pouvez demander la limitation du traitement</li>
                        <li><strong>Droit à la portabilité</strong> : vous pouvez récupérer vos données dans un format structuré</li>
                        <li><strong>Droit d'opposition</strong> : vous pouvez vous opposer au traitement de vos données</li>
                        <li><strong>Droit de retirer votre consentement</strong> : à tout moment</li>
                    </ul>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Pour exercer ces droits, contactez-nous à : [À compléter]
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        7. Sécurité des données
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Nous mettons en œuvre des mesures de sécurité techniques et organisationnelles appropriées pour protéger vos données personnelles contre la perte, l'utilisation abusive, l'accès non autorisé, la divulgation, l'altération ou la destruction.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Cependant, aucune méthode de transmission sur Internet ou de stockage électronique n'est totalement sécurisée. Bien que nous nous efforcions d'utiliser des moyens commercialement acceptables pour protéger vos données, nous ne pouvons garantir leur sécurité absolue.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        8. Cookies
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Notre site utilise des cookies pour améliorer votre expérience. Pour plus d'informations, consultez notre
                        <Link
                            href="/politique-cookies"
                            class="text-blue-600 underline hover:text-blue-700 dark:text-blue-400"
                        >
                            Politique des cookies
                        </Link>.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        9. Modifications de cette politique
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Nous pouvons mettre à jour cette politique de confidentialité de temps à autre. Nous vous informerons de tout changement en publiant la nouvelle politique sur cette page et en mettant à jour la date de "dernière mise à jour".
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        10. Contact
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Pour toute question concernant cette politique de confidentialité, contactez-nous à : [À compléter]
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


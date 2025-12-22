<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes';
import { ArrowLeft } from 'lucide-vue-next';
</script>

<template>
    <Head title="Conditions d'utilisation" />
    <div class="min-h-screen bg-white dark:bg-gray-900">
        <div class="mx-auto max-w-4xl px-6 py-12 lg:px-8">
            <Link
                :href="home.url()"
                class="mb-8 inline-flex items-center gap-2 text-sm text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
                <ArrowLeft class="h-4 w-4" />
                Retour à l'accueil
            </Link>

            <div class="prose prose-lg max-w-none dark:prose-invert">
                <h1 class="mb-8 text-4xl font-bold text-gray-900 dark:text-white">
                    Conditions d'utilisation
                </h1>

                <section class="mb-8">
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Les présentes conditions générales d'utilisation (ci-après les "CGU") régissent l'utilisation du site FitnessClic (ci-après le "Service") exploité par FitnessClic.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        En accédant et en utilisant ce Service, vous acceptez d'être lié par ces CGU. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser le Service.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        1. Acceptation des conditions
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        En utilisant FitnessClic, vous reconnaissez avoir lu, compris et accepté d'être lié par ces CGU ainsi que par notre
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
                        2. Description du service
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        FitnessClic est une plateforme en ligne permettant aux coachs sportifs et aux particuliers de créer, organiser et partager des programmes d'entraînement.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Les fonctionnalités principales incluent :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Création de séances d'entraînement</li>
                        <li>Gestion d'une bibliothèque d'exercices personnalisée</li>
                        <li>Organisation des séances par client</li>
                        <li>Export PDF et impression des séances</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        3. Inscription et compte utilisateur
                    </h2>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        3.1 Création de compte
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Pour utiliser certaines fonctionnalités du Service, vous devez créer un compte. Vous vous engagez à :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Fournir des informations exactes, complètes et à jour</li>
                        <li>Maintenir et mettre à jour vos informations</li>
                        <li>Protéger la confidentialité de votre mot de passe</li>
                        <li>Être responsable de toutes les activités sous votre compte</li>
                    </ul>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        3.2 Âge minimum
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Vous devez avoir au moins 18 ans pour utiliser ce Service. Si vous avez moins de 18 ans, vous devez obtenir l'autorisation d'un parent ou d'un tuteur légal.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        4. Utilisation du service
                    </h2>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        4.1 Utilisation autorisée
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Vous vous engagez à utiliser le Service uniquement à des fins légales et conformément à ces CGU. Vous acceptez de ne pas :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Violer les lois ou réglementations applicables</li>
                        <li>Enfreindre les droits de propriété intellectuelle</li>
                        <li>Transmettre des virus ou codes malveillants</li>
                        <li>Tenter d'accéder de manière non autorisée au Service</li>
                        <li>Utiliser le Service pour harceler, menacer ou nuire à autrui</li>
                        <li>Collecter des informations sur d'autres utilisateurs sans leur consentement</li>
                    </ul>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">
                        4.2 Contenu utilisateur
                    </h3>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Vous conservez tous les droits sur le contenu que vous créez et partagez via le Service. En utilisant le Service, vous accordez à FitnessClic une licence mondiale, non exclusive, gratuite et transférable pour utiliser, reproduire, modifier et afficher ce contenu dans le cadre de la fourniture du Service.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        5. Propriété intellectuelle
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Le Service et son contenu original, les fonctionnalités et la fonctionnalité sont et resteront la propriété exclusive de FitnessClic et de ses concédants de licence. Le Service est protégé par le droit d'auteur, les marques de commerce et autres lois.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        6. Disponibilité du service
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Nous nous efforçons d'assurer la disponibilité continue du Service, mais nous ne garantissons pas que le Service sera disponible en permanence, sans interruption ou sans erreur. Nous nous réservons le droit de suspendre ou d'interrompre le Service à tout moment, avec ou sans préavis, pour maintenance, mises à jour ou toute autre raison.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        7. Limitation de responsabilité
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Dans la mesure maximale permise par la loi, FitnessClic ne sera pas responsable des dommages directs, indirects, accessoires, spéciaux, consécutifs ou punitifs résultant de votre utilisation ou de votre incapacité à utiliser le Service.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        FitnessClic ne garantit pas l'exactitude, l'exhaustivité ou l'utilité des informations fournies par le Service. Vous êtes seul responsable de l'utilisation que vous faites des informations et des conseils fournis.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        8. Résiliation
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Nous nous réservons le droit de résilier ou de suspendre votre compte et votre accès au Service immédiatement, sans préavis ni responsabilité, pour quelque raison que ce soit, y compris en cas de violation des présentes CGU.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Vous pouvez résilier votre compte à tout moment en contactant notre support ou en utilisant la fonctionnalité de suppression de compte dans les paramètres.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        9. Modifications des CGU
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Nous nous réservons le droit de modifier ou de remplacer ces CGU à tout moment. Si une révision est importante, nous essaierons de fournir un préavis d'au moins 30 jours avant l'entrée en vigueur des nouvelles conditions.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        En continuant à accéder ou à utiliser le Service après l'entrée en vigueur des révisions, vous acceptez d'être lié par les CGU révisées.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        10. Droit applicable et juridiction
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Ces CGU sont régies et interprétées conformément aux lois françaises. Tout litige découlant de ces CGU sera soumis à la juridiction exclusive des tribunaux français.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        11. Contact
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Pour toute question concernant ces CGU, contactez-nous à : fitnessclic@gmail.com
                    </p>
                </section>

                <section class="mb-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dernière mise à jour : 22/12/2025
                    </p>
                </section>
            </div>
        </div>
    </div>
</template>


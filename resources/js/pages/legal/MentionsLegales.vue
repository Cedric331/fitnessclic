<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes';
import { ArrowLeft } from 'lucide-vue-next';
</script>

<template>
    <Head title="Mentions légales" />
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
                    Mentions légales
                </h1>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        1. Informations sur l'éditeur
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Le site <strong>FitnessClic</strong> est édité par :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Dénomination sociale : FitnessClic</li>
                        <li>Forme juridique : Entreprise individuelle</li>
                        <li>Siège social : 1, lieu-dit la rivière, 71990 Saint-Prix</li>
                        <li>SIRET : 482 969 284</li>
                        <li>Directeur de publication : Julien WIART</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        2. Hébergement
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Le site est hébergé par :
                    </p>
                    <ul class="mb-4 list-disc pl-6 text-gray-700 dark:text-gray-300">
                        <li>Hébergeur : Groupe CELESTE - Nuxit</li>
                        <li>Adresse : 20 rue Albert Einstein, 77420 Champs sur Marne</li>
                        <li>Mail : commercial@nuxit.com</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        3. Propriété intellectuelle
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        L'ensemble du contenu du site FitnessClic (textes, images, vidéos, logos, icônes, etc.) est la propriété exclusive de FitnessClic ou de ses partenaires, sauf mention contraire.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Toute reproduction, représentation, modification, publication, adaptation de tout ou partie des éléments du site, quel que soit le moyen ou le procédé utilisé, est interdite, sauf autorisation écrite préalable de FitnessClic.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        4. Protection des données personnelles
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Conformément à la loi « Informatique et Libertés » du 6 janvier 1978 modifiée et au Règlement Général sur la Protection des Données (RGPD), vous disposez d'un droit d'accès, de rectification, de suppression et d'opposition aux données personnelles vous concernant.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Pour exercer ces droits, vous pouvez nous contacter à l'adresse : fitnessclic@gmail.com
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Pour plus d'informations, consultez notre
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
                        5. Responsabilité
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        FitnessClic s'efforce d'assurer l'exactitude et la mise à jour des informations diffusées sur le site. Toutefois, FitnessClic ne peut garantir l'exactitude, la précision ou l'exhaustivité des informations mises à disposition sur le site.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        FitnessClic ne pourra être tenu responsable des dommages directs ou indirects causés au matériel de l'utilisateur, lors de l'accès au site, et résultant soit de l'utilisation d'un matériel ne répondant pas aux spécifications, soit de l'apparition d'un bug ou d'une incompatibilité.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        6. Liens hypertextes
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Le site peut contenir des liens hypertextes vers d'autres sites présents sur le réseau Internet. Les liens vers ces autres ressources vous font quitter le site FitnessClic.
                    </p>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Il est possible de créer un lien vers la page de présentation de ce site sans autorisation expresse de l'éditeur. Aucune autorisation ni demande d'information préalable ne peut être exigée par l'éditeur à l'égard d'un site qui souhaite établir un lien vers le site de l'éditeur.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        7. Contact
                    </h2>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        Pour toute question concernant les présentes mentions légales, vous pouvez nous contacter à l'adresse suivante : fitnessclic@gmail.com
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


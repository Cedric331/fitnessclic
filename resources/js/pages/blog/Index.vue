<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import NavBar from '@/components/welcome/NavBar.vue';
import Footer from '@/components/welcome/Footer.vue';
import CookieBanner from '@/components/welcome/CookieBanner.vue';

type BlogPost = {
    title: string;
    slug: string;
    excerpt: string;
    published_at: string | null;
    image_url: string | null;
    tags: string[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    posts: {
        data: BlogPost[];
        links: PaginationLink[];
        meta: {
            current_page: number;
            last_page: number;
            from: number | null;
            to: number | null;
            total: number;
        };
    };
}>();

const siteName = 'FitnessClic';
const defaultSiteUrl = 'https://fitnessclic.com';
const currentUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.href.replace('http://', 'https://');
    }
    return `${defaultSiteUrl}/blog`;
});

const title = 'Blog FitnessClic';
const description = 'Conseils, astuces et ressources pour les coachs sportifs et les passionnés de fitness. Articles, tendances et bonnes pratiques.';
const imageUrl = `${defaultSiteUrl}/assets/logo_fitnessclic.png`;

const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Blog',
    name: 'Blog FitnessClic',
    url: currentUrl.value,
    publisher: {
        '@type': 'Organization',
        name: siteName,
        logo: {
            '@type': 'ImageObject',
            url: imageUrl,
        },
    },
}));

let structuredDataScript: HTMLScriptElement | null = null;

onMounted(() => {
    structuredDataScript = document.createElement('script');
    structuredDataScript.type = 'application/ld+json';
    structuredDataScript.id = 'structured-data-blog';
    structuredDataScript.textContent = JSON.stringify(structuredData.value);
    document.head.appendChild(structuredDataScript);
});

onUnmounted(() => {
    if (structuredDataScript?.parentNode) {
        structuredDataScript.parentNode.removeChild(structuredDataScript);
    }
    structuredDataScript = null;
});

const formatDate = (date: string | null) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(date));
};

const fallbackImage = '/assets/logo_fitnessclic.png';
const baseShareUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin.replace('http://', 'https://');
    }
    return defaultSiteUrl;
});

const buildShareUrl = (slug: string) => `${baseShareUrl.value}/blog/${slug}`;
const shareLinks = (post: BlogPost) => {
    const url = encodeURIComponent(buildShareUrl(post.slug));
    const text = encodeURIComponent(post.title);
    return {
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
        x: `https://twitter.com/intent/tweet?url=${url}&text=${text}`,
        instagram: buildShareUrl(post.slug),
    };
};

const copyToClipboard = async (text: string) => {
    if (navigator?.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
};

const shareOnInstagram = async (post: BlogPost) => {
    const url = buildShareUrl(post.slug);
    await copyToClipboard(url);
    window.open('https://www.instagram.com/', '_blank', 'noopener,noreferrer');
};
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <link rel="canonical" :href="currentUrl" />

        <meta property="og:type" content="website" />
        <meta property="og:url" :content="currentUrl" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="imageUrl" />
        <meta property="og:site_name" :content="siteName" />
        <meta property="og:locale" content="fr_FR" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="imageUrl" />
        <meta name="twitter:site" content="@FitnessClic" />

    </Head>

    <div class="flex min-h-screen flex-col bg-white dark:bg-gray-900">
        <NavBar />
        <main class="flex-1">
            <section class="bg-gradient-to-br from-blue-50 via-white to-white py-16 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="max-w-3xl">
                        <p class="text-sm font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Blog</p>
                        <h1 class="mt-3 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
                            Actualités et conseils pour les coachs sportifs
                        </h1>
                        <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                            Découvrez nos articles pour optimiser vos programmes, gagner du temps et accompagner vos clients.
                        </p>
                    </div>
                </div>
            </section>

            <section class="py-12">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div v-if="props.posts.data.length === 0" class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">Aucun article publié pour le moment.</p>
                    </div>
                    <div v-else class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="post in props.posts.data"
                            :key="post.slug"
                            class="group flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900"
                        >
                            <Link :href="`/blog/${post.slug}`" class="block">
                                <img
                                    :src="post.image_url ?? fallbackImage"
                                    :alt="post.title"
                                    class="h-48 w-full object-cover"
                                    loading="lazy"
                                />
                            </Link>
                            <div class="flex flex-1 flex-col p-6">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="tag in post.tags"
                                        :key="tag"
                                        class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-200"
                                    >
                                        {{ tag }}
                                    </span>
                                </div>
                                <Link :href="`/blog/${post.slug}`" class="mt-4 block">
                                    <h2 class="text-xl font-semibold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                                        {{ post.title }}
                                    </h2>
                                </Link>
                                <p class="mt-3 line-clamp-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ post.excerpt }}
                                </p>
                                <div class="mt-6 flex flex-wrap items-center justify-between gap-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ formatDate(post.published_at) }}</span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-if="props.posts.links.length > 3" class="mt-12 flex flex-wrap items-center justify-center gap-2">
                        <template v-for="link in props.posts.links" :key="link.label">
                            <span
                                v-if="!link.url"
                                class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-400 dark:border-gray-800"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded-lg border px-3 py-2 text-sm transition"
                                :class="link.active
                                    ? 'border-blue-600 bg-blue-600 text-white'
                                    : 'border-gray-200 text-gray-700 hover:border-blue-600 hover:text-blue-600 dark:border-gray-800 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-400'"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </section>
        </main>
        <Footer />
        <CookieBanner />
    </div>
</template>


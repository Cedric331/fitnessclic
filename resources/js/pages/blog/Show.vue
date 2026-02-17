<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import NavBar from '@/components/welcome/NavBar.vue';
import Footer from '@/components/welcome/Footer.vue';
import CookieBanner from '@/components/welcome/CookieBanner.vue';

type BlogPost = {
    title: string;
    slug: string;
    content: string;
    excerpt: string;
    published_at: string | null;
    image_url: string | null;
    tags: string[];
};

const props = defineProps<{
    post: BlogPost;
}>();

const siteName = 'FitnessClic';
const defaultSiteUrl = 'https://fitnessclic.com';
const baseUrl = computed(() => (typeof window !== 'undefined' ? window.location.origin : defaultSiteUrl));
const currentUrl = computed(() => `${baseUrl.value}/blog/${props.post.slug}`);


const imageUrl = computed(() => {
  const img = props.post.image_url;
  if (!img) return `${baseUrl.value}/assets/logo_fitnessclic.png`;
  return img.startsWith('http') ? img : `${baseUrl.value}${img}`;
});
const title = computed(() => `${props.post.title} | Blog FitnessClic`);
const description = computed(() => props.post.excerpt);

const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: props.post.title,
    image: [imageUrl.value],
    datePublished: props.post.published_at,
    dateModified: props.post.published_at,
    author: {
        '@type': 'Organization',
        name: siteName,
    },
    publisher: {
        '@type': 'Organization',
        name: siteName,
        logo: {
            '@type': 'ImageObject',
            url: `${baseUrl.value}/assets/logo_fitnessclic.png`,
        },
    },
    mainEntityOfPage: {
        '@type': 'WebPage',
        '@id': currentUrl.value,
    },
}));

let structuredDataScript: HTMLScriptElement | null = null;

onMounted(() => {
    structuredDataScript = document.createElement('script');
    structuredDataScript.type = 'application/ld+json';
    structuredDataScript.id = 'structured-data-article';
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

const shareLinks = computed(() => {
    const url = encodeURIComponent(currentUrl.value);
    const text = encodeURIComponent(props.post.title);
    return {
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
        x: `https://twitter.com/intent/tweet?url=${url}&text=${text}`,
        instagram: currentUrl.value,
    };
});

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

const shareOnInstagram = async () => {
    await copyToClipboard(currentUrl.value);
    window.open('https://www.instagram.com/', '_blank', 'noopener,noreferrer');
};
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <link rel="canonical" :href="currentUrl" />

        <meta property="og:type" content="article" />
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
            <section class="bg-gradient-to-br from-blue-50 via-white to-white py-12 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900">
                <div class="mx-auto max-w-4xl px-6 lg:px-8">
                    <Link href="/blog" class="text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        ← Retour au blog
                    </Link>
                    <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
                        {{ props.post.title }}
                    </h1>
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                        <span>{{ formatDate(props.post.published_at) }}</span>
                        <span class="text-gray-300 dark:text-gray-700">•</span>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in props.post.tags"
                                :key="tag"
                                class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-200"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-10">
                <div class="mx-auto max-w-4xl px-6 lg:px-8">
                    <img
                        :src="imageUrl"
                        :alt="props.post.title"
                        class="h-64 w-full rounded-2xl object-cover shadow-md sm:h-80"
                        loading="lazy"
                    />

                    <div class="blog-content mt-10 text-base leading-7 text-gray-700 dark:text-gray-200" v-html="props.post.content" />

                    <div class="mt-10 flex flex-wrap items-center gap-3 border-t border-gray-200 pt-6 text-sm dark:border-gray-800">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">Partager :</span>
                        <a
                            :href="shareLinks.facebook"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-full border border-gray-200 px-3 py-1 text-sm font-medium text-gray-600 transition-colors hover:border-blue-600 hover:text-blue-600 dark:border-gray-700 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-400"
                        >
                            Facebook
                        </a>
                        <a
                            :href="shareLinks.x"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-full border border-gray-200 px-3 py-1 text-sm font-medium text-gray-600 transition-colors hover:border-blue-600 hover:text-blue-600 dark:border-gray-700 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-400"
                        >
                            X
                        </a>
                        <button
                            type="button"
                            class="rounded-full border border-gray-200 px-3 py-1 text-sm font-medium text-gray-600 transition-colors hover:border-blue-600 hover:text-blue-600 dark:border-gray-700 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-400"
                            @click="shareOnInstagram"
                        >
                            Instagram
                        </button>
                    </div>
                </div>
            </section>
        </main>
        <Footer />
        <CookieBanner />
    </div>
</template>

<style scoped>
.blog-content :deep(h2) {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: inherit;
}

.blog-content :deep(h3) {
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: inherit;
}

.blog-content :deep(p) {
    margin-top: 1rem;
}

.blog-content :deep(ul),
.blog-content :deep(ol) {
    margin-top: 1rem;
    padding-left: 1.25rem;
}

.blog-content :deep(a) {
    color: #2563eb;
    text-decoration: underline;
}
</style>


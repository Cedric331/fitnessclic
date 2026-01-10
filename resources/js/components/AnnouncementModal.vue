<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import {
    Dialog,
    DialogScrollContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Sparkles, PartyPopper, X } from 'lucide-vue-next';

interface Announcement {
    id: number;
    title: string;
    description: string;
    image_url: string | null;
    published_at: string | null;
}

const isOpen = ref(false);
const announcement = ref<Announcement | null>(null);
const isLoading = ref(true);
const isClosing = ref(false);

const fetchAnnouncement = async () => {
    try {
        const response = await axios.get('/announcements/current');
        if (response.data.announcement) {
            announcement.value = response.data.announcement;
            isOpen.value = true;
        }
    } catch (error) {
        console.error('Erreur lors de la récupération de l\'annonce:', error);
    } finally {
        isLoading.value = false;
    }
};

const closeModal = async () => {
    if (!announcement.value || isClosing.value) return;

    isClosing.value = true;

    try {
        await axios.post(`/announcements/${announcement.value.id}/seen`);
    } catch (error) {
        console.error('Erreur lors du marquage de l\'annonce comme vue:', error);
    } finally {
        isOpen.value = false;
        isClosing.value = false;
    }
};

const handleOpenChange = (open: boolean) => {
    if (!open) {
        closeModal();
    }
};

onMounted(() => {
    fetchAnnouncement();
});
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleOpenChange">
        <DialogScrollContent class="sm:max-w-[580px] p-0 gap-0 overflow-hidden border-0">
            <!-- Header décoratif avec gradient -->
            <div class="relative bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 px-6 pt-8 pb-12">
                <!-- Motif décoratif -->
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute top-4 left-8 w-20 h-20 rounded-full bg-white/30 blur-2xl"></div>
                    <div class="absolute bottom-2 right-12 w-32 h-32 rounded-full bg-white/20 blur-3xl"></div>
                    <div class="absolute top-8 right-8 w-12 h-12 rounded-full bg-yellow-300/40 blur-xl"></div>
                </div>

                <!-- Badge animé -->
                <div class="relative flex items-center gap-2 mb-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-white text-xs font-medium border border-white/30">
                        <Sparkles class="size-3.5 animate-pulse" />
                        Nouveauté
                    </span>
                </div>

                <!-- Titre -->
                <DialogHeader class="relative text-left">
                    <DialogTitle class="text-2xl sm:text-3xl font-bold text-white leading-tight pr-8">
                        {{ announcement?.title }}
                    </DialogTitle>
                    <DialogDescription class="text-white/80 text-sm mt-2">
                        Découvrez les dernières améliorations de FitnessClic
                    </DialogDescription>
                </DialogHeader>

                <!-- Icône festive -->
                <div class="absolute -bottom-6 right-6 flex items-center justify-center size-14 rounded-2xl bg-white shadow-lg shadow-emerald-500/30 rotate-6">
                    <PartyPopper class="size-7 text-emerald-500" />
                </div>
            </div>

            <!-- Contenu scrollable -->
            <div class="px-6 pt-10 pb-6 max-h-[50vh] overflow-y-auto bg-white dark:bg-slate-900">
                <!-- Image optionnelle -->
                <div v-if="announcement?.image_url" class="mb-6 -mx-2">
                    <div class="relative rounded-xl overflow-hidden shadow-lg ring-1 ring-slate-200 dark:ring-slate-700">
                        <img
                            :src="announcement.image_url"
                            :alt="announcement.title"
                            class="w-full h-auto max-h-[280px] object-cover"
                        />
                        <!-- Overlay gradient subtil -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent pointer-events-none"></div>
                    </div>
                </div>

                <!-- Description (HTML) -->
                <div class="announcement-content text-slate-700 dark:text-slate-300" v-html="announcement?.description" />
            </div>

            <!-- Footer sticky -->
            <div class="sticky bottom-0 px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                <Button
                    class="w-full h-12 text-base font-semibold bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 hover:from-emerald-600 hover:via-teal-600 hover:to-cyan-600 text-white shadow-lg shadow-emerald-500/25 transition-all duration-300 hover:shadow-xl hover:shadow-emerald-500/30 hover:-translate-y-0.5"
                    :disabled="isClosing"
                    @click="closeModal"
                >
                    <template v-if="isClosing">
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Fermeture...
                    </template>
                    <template v-else>
                        <Sparkles class="size-5 mr-2" />
                        C'est noté, merci !
                    </template>
                </Button>
            </div>
        </DialogScrollContent>
    </Dialog>
</template>

<style scoped>
/* Styles pour le contenu HTML de la description */
.announcement-content {
    line-height: 1.7;
    font-size: 0.9375rem;
}

:deep(.announcement-content h2) {
    font-size: 1.25rem;
    font-weight: 700;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: #0f172a;
}

.dark :deep(.announcement-content h2) {
    color: #f1f5f9;
}

:deep(.announcement-content h3) {
    font-size: 1.0625rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
    color: #1e293b;
}

.dark :deep(.announcement-content h3) {
    color: #e2e8f0;
}

:deep(.announcement-content p) {
    margin-bottom: 1rem;
}

:deep(.announcement-content ul) {
    list-style: none;
    padding-left: 0;
    margin-bottom: 1rem;
}

:deep(.announcement-content ul li) {
    position: relative;
    padding-left: 1.75rem;
    margin-bottom: 0.5rem;
}

:deep(.announcement-content ul li::before) {
    content: '';
    position: absolute;
    left: 0;
    top: 0.5rem;
    width: 0.5rem;
    height: 0.5rem;
    background: linear-gradient(135deg, #10b981, #06b6d4);
    border-radius: 50%;
}

:deep(.announcement-content ol) {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
    counter-reset: item;
    list-style: none;
}

:deep(.announcement-content ol li) {
    position: relative;
    padding-left: 0.5rem;
    margin-bottom: 0.5rem;
    counter-increment: item;
}

:deep(.announcement-content ol li::before) {
    content: counter(item);
    position: absolute;
    left: -1.5rem;
    top: 0;
    width: 1.25rem;
    height: 1.25rem;
    background: linear-gradient(135deg, #10b981, #06b6d4);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

:deep(.announcement-content a) {
    color: #0d9488;
    font-weight: 500;
    text-decoration: underline;
    text-underline-offset: 2px;
    transition: color 0.2s;
}

:deep(.announcement-content a:hover) {
    color: #0f766e;
}

.dark :deep(.announcement-content a) {
    color: #2dd4bf;
}

.dark :deep(.announcement-content a:hover) {
    color: #5eead4;
}

:deep(.announcement-content strong) {
    font-weight: 600;
    color: #0f172a;
}

.dark :deep(.announcement-content strong) {
    color: #f1f5f9;
}

:deep(.announcement-content code) {
    background: #f1f5f9;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
    font-family: ui-monospace, monospace;
}

.dark :deep(.announcement-content code) {
    background: #334155;
}

:deep(.announcement-content blockquote) {
    border-left: 4px solid #10b981;
    padding-left: 1rem;
    margin: 1rem 0;
    font-style: italic;
    color: #475569;
}

.dark :deep(.announcement-content blockquote) {
    color: #94a3b8;
}
</style>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Dialog,
    DialogClose,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CheckCircle2, Gift, Mail, X } from 'lucide-vue-next';

interface Popin {
    id: number;
    title: string;
    content: string;
    image_url: string | null;
    image_size?: 'small' | 'medium' | 'large' | null;
    delay_seconds: number;
    has_promo_code: boolean;
    register_url: string;
}

const page = usePage();
const isGuest = computed(() => !(page.props.auth as any)?.user);

const isOpen = ref(false);
const popin = ref<Popin | null>(null);
const email = ref('');
const isSubmitting = ref(false);
const formError = ref('');
const promoCode = ref<string | null>(null);
const registerUrl = ref('/register');
const submissionSuccess = ref(false);

const imageSizeClass = computed(() => {
    switch (popin.value?.image_size) {
        case 'small':
            return 'max-h-[150px]';
        case 'large':
            return 'max-h-[300px]';
        case 'medium':
        default:
            return 'max-h-[200px]';
    }
});

let openTimer: number | null = null;

const getStorageKey = (popinId: number) => `fitnessclic_popin_seen_${popinId}`;

const hasSeenPopin = (popinId: number) => {
    try {
        return window.localStorage.getItem(getStorageKey(popinId)) === '1';
    } catch {
        return false;
    }
};

const markPopinSeen = (popinId: number) => {
    try {
        window.localStorage.setItem(getStorageKey(popinId), '1');
    } catch {
        // Ignore storage errors (private mode, blocked storage, etc.)
    }
};

const openPopin = () => {
    if (!popin.value) return;
    isOpen.value = true;
    markPopinSeen(popin.value.id);
};

const fetchPopin = async () => {
    if (!isGuest.value) return;

    try {
        const response = await axios.get('/popins/active');
        if (response.data.popin) {
            popin.value = response.data.popin;
            registerUrl.value = response.data.popin.register_url || '/register';

            if (hasSeenPopin(response.data.popin.id)) {
                return;
            }

            const delay = Math.max(0, Number(response.data.popin.delay_seconds ?? 0));
            openTimer = window.setTimeout(() => {
                openPopin();
            }, delay * 1000);
        }
    } catch (error) {
        console.error('Erreur lors de la récupération de la popin:', error);
    }
};

const submitEmail = async () => {
    if (!popin.value || isSubmitting.value) return;

    formError.value = '';
    const trimmedEmail = email.value.trim();
    if (!trimmedEmail) {
        formError.value = 'Veuillez saisir un email valide.';
        return;
    }

    isSubmitting.value = true;
    try {
        const response = await axios.post(`/popins/${popin.value.id}/prospects`, {
            email: trimmedEmail,
        });

        promoCode.value = response.data.promo_code ?? null;
        registerUrl.value = response.data.register_url || registerUrl.value;
        submissionSuccess.value = true;
    } catch (error: any) {
        formError.value = error?.response?.data?.message || 'Une erreur est survenue. Veuillez réessayer.';
    } finally {
        isSubmitting.value = false;
    }
};

const handleOpenChange = (open: boolean) => {
    isOpen.value = open;
};

onMounted(fetchPopin);

onUnmounted(() => {
    if (openTimer) {
        window.clearTimeout(openTimer);
        openTimer = null;
    }
});
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleOpenChange">
        <DialogScrollContent class="sm:max-w-[640px] lg:max-w-[720px] p-0 gap-0 overflow-hidden border-0">
            <div class="relative bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 px-6 pt-5 pb-6">
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute top-4 left-6 w-16 h-16 rounded-full bg-white/30 blur-2xl"></div>
                    <div class="absolute bottom-2 right-8 w-24 h-24 rounded-full bg-white/20 blur-3xl"></div>
                </div>

 
                <DialogHeader class="relative text-left">
                    <DialogTitle class="text-2xl sm:text-3xl font-bold text-white leading-tight pr-10">
                        {{ popin?.title }}
                    </DialogTitle>
                    <DialogDescription class="text-white/80 text-sm mt-2">
                        Une offre pensée pour les coachs qui veulent aller plus loin
                    </DialogDescription>
                </DialogHeader>
            </div>

            <div class="px-6 pt-6 pb-4 max-h-[52vh] lg:max-h-[62vh] overflow-y-auto bg-white dark:bg-slate-900">
                <div v-if="popin?.image_url" class="mb-5 -mx-1">
                    <div class="relative rounded-xl overflow-hidden shadow-lg ring-1 ring-slate-200 dark:ring-slate-700">
                        <img
                            :src="popin.image_url"
                            :alt="popin.title"
                            class="w-full h-auto object-cover"
                            :class="imageSizeClass"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent pointer-events-none"></div>
                    </div>
                </div>

                <div class="popin-content text-slate-700 dark:text-slate-300" v-html="popin?.content" />

                <div class="mt-6">
                    <div
                        v-if="submissionSuccess"
                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-900 dark:border-emerald-700/40 dark:bg-emerald-950/40 dark:text-emerald-100"
                    >
                        <div class="flex items-start gap-3">
                            <CheckCircle2 class="size-5 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                            <div>
                                <p class="font-semibold">Merci ! Votre email est bien enregistré.</p>
                                <p class="text-sm text-emerald-800/80 dark:text-emerald-200/80 mt-1">
                                    <template v-if="promoCode">
                                        Un email vient de partir avec les détails de l'offre.
                                    </template>
                                    <template v-else>
                                        Nous reviendrons vers vous si une offre est disponible.
                                    </template>
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="promoCode"
                            class="mt-4 rounded-lg border border-emerald-200 bg-white/80 px-4 py-3 text-emerald-900 dark:border-emerald-700/40 dark:bg-slate-900/60"
                        >
                            <div class="flex items-center gap-2 text-sm font-semibold">
                                <Gift class="size-4 text-emerald-600 dark:text-emerald-400" />
                                Votre code promo
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1 text-sm font-semibold text-white">
                                    {{ promoCode }}
                                </span>
                                <a
                                    :href="registerUrl"
                                    class="text-sm font-medium text-emerald-700 hover:text-emerald-800 underline underline-offset-4 dark:text-emerald-300 dark:hover:text-emerald-200"
                                >
                                    Créer mon compte
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                            Recevez l'offre par email
                        </p>

                        <div class="mt-4 space-y-3">
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    v-model="email"
                                    type="email"
                                    placeholder="Votre email"
                                    class="pl-9"
                                    autocomplete="email"
                                />
                            </div>

                            <p v-if="formError" class="text-xs text-red-600">{{ formError }}</p>

                            <Button
                                class="w-full h-11 text-base font-semibold bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 hover:from-emerald-600 hover:via-teal-600 hover:to-cyan-600 text-white shadow-lg shadow-emerald-500/25 transition-all duration-300"
                                :disabled="isSubmitting"
                                @click="submitEmail"
                            >
                                <template v-if="isSubmitting">
                                    Envoi...
                                </template>
                                <template v-else>
                                    Recevoir l'offre
                                </template>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </DialogScrollContent>
    </Dialog>
</template>

<style scoped>
.popin-content {
    line-height: 1.7;
    font-size: 0.9375rem;
}

:deep(.popin-content h2) {
    font-size: 1.25rem;
    font-weight: 700;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: #0f172a;
}

.dark :deep(.popin-content h2) {
    color: #f1f5f9;
}

:deep(.popin-content h3) {
    font-size: 1.0625rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
    color: #1e293b;
}

.dark :deep(.popin-content h3) {
    color: #e2e8f0;
}

:deep(.popin-content p) {
    margin-bottom: 1rem;
}

:deep(.popin-content ul) {
    list-style: none;
    padding-left: 0;
    margin-bottom: 1rem;
}

:deep(.popin-content ul li) {
    position: relative;
    padding-left: 1.75rem;
    margin-bottom: 0.5rem;
}

:deep(.popin-content ul li::before) {
    content: '';
    position: absolute;
    left: 0;
    top: 0.5rem;
    width: 0.5rem;
    height: 0.5rem;
    background: linear-gradient(135deg, #10b981, #06b6d4);
    border-radius: 50%;
}

:deep(.popin-content a) {
    color: #0d9488;
    font-weight: 500;
    text-decoration: underline;
    text-underline-offset: 2px;
    transition: color 0.2s;
}

:deep(.popin-content a:hover) {
    color: #0f766e;
}

.dark :deep(.popin-content a) {
    color: #2dd4bf;
}

.dark :deep(.popin-content a:hover) {
    color: #5eead4;
}
</style>


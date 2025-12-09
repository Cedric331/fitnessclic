<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Star, Check } from 'lucide-vue-next';

interface Props {
    open: boolean;
    feature?: string; // Description de la fonctionnalité demandée
}

const props = withDefaults(defineProps<Props>(), {
    feature: '',
});

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const page = usePage();
const isPro = computed(() => (page.props.auth as any)?.user?.isPro ?? false);

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const handleUpgrade = () => {
    isOpen.value = false;
    router.visit('/subscription');
};

const proFeatures = [
    'Clients illimités',
    'Export des séances en PDF',
    'Sauvegardes illimitées de toutes vos séances',
    'Import d\'exercices illimités dans la bibliothèque',
    'Support email prioritaire',
    'Création de nouvelles catégories d\'exercices',
];
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex size-12 items-center justify-center rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600">
                        <Star class="size-6 text-white fill-white" />
                    </div>
                    <div>
                        <DialogTitle class="text-2xl font-bold text-slate-900 dark:text-white">
                            Passez à FitnessClicPro
                        </DialogTitle>
                        <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                            Débloquez toutes les fonctionnalités avancées
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="py-4">
                <p v-if="feature" class="text-sm text-slate-700 dark:text-slate-300 mb-4">
                    {{ feature }}
                </p>

                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/20 dark:to-indigo-950/20 rounded-lg p-4 mb-4">
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-3xl font-bold text-slate-900 dark:text-white">5€</span>
                        <span class="text-sm text-slate-600 dark:text-slate-400">/ mois</span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Facturé mensuellement, annulable à tout moment
                    </p>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white mb-2">
                        Avec l'abonnement FitnessClicPro, vous bénéficiez de :
                    </p>
                    <ul class="space-y-2">
                        <li
                            v-for="feature in proFeatures"
                            :key="feature"
                            class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300"
                        >
                            <Check class="size-4 text-green-600 dark:text-green-400 mt-0.5 flex-shrink-0" />
                            <span>{{ feature }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    variant="outline"
                    @click="isOpen = false"
                >
                    Plus tard
                </Button>
                <Button
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white ml-2"
                    @click="handleUpgrade"
                >
                    <Star class="size-4" />
                    Passer à FitnessClicPro
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


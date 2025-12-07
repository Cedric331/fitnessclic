<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Exercise } from './types';
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Edit, Eye, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    exercise: Exercise;
    onEdit?: (exercise: Exercise) => void;
    viewMode?: 'grid-2' | 'grid-4' | 'grid-6' | 'grid-8';
}>();

const emit = defineEmits<{
    edit: [exercise: Exercise];
    delete: [exercise: Exercise];
}>();

const page = usePage();
const canEdit = computed(() => {
    const user = (page.props as any).auth?.user;
    return user && (user.id === props.exercise.user_id || user.role === 'admin');
});

const canDelete = computed(() => {
    const user = (page.props as any).auth?.user;
    return user && (user.id === props.exercise.user_id || user.role === 'admin');
});

const formattedDate = computed(() => {
    if (!props.exercise.created_at) {
        return '';
    }

    return new Date(props.exercise.created_at).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
});

const handleView = () => {
    router.visit(`/exercises/${props.exercise.id}`);
};

const handleEdit = (event: Event) => {
    event.stopPropagation();
    if (props.onEdit) {
        props.onEdit(props.exercise);
    } else {
        emit('edit', props.exercise);
    }
};

const handleDelete = (event: Event) => {
    event.stopPropagation();
    emit('delete', props.exercise);
};

// Classes pour ajuster la taille des images selon le mode d'affichage
// Pour les modes avec plusieurs colonnes, on ne réduit pas l'image car object-cover remplit tout l'espace
const imageScaleClass = computed(() => {
    if (!props.viewMode) return '';
    
    // Seulement pour grid-2, on peut avoir besoin d'ajustements
    // Pour les autres modes, object-cover remplit tout l'espace sans besoin de scale
    return '';
});

// Afficher le contenu texte uniquement pour grid-2 (1-2 par ligne)
const showContent = computed(() => {
    return props.viewMode === 'grid-2';
});

// Afficher l'overlay au survol uniquement pour grid-2 (1-2 par ligne)
const showOverlay = computed(() => {
    return props.viewMode === 'grid-2';
});

// Rendre l'image cliquable pour ouvrir la page show (sauf pour grid-2 où on a déjà les boutons)
const handleImageClick = () => {
    if (props.viewMode !== 'grid-2') {
        handleView();
    }
};

// Classes pour l'object-fit selon le mode d'affichage
const imageObjectFit = computed(() => {
    if (!props.viewMode) return 'object-contain';
    
    // Pour grid-2, on garde object-contain pour voir l'image complète
    // Pour les autres modes, on utilise object-cover pour remplir tout l'espace
    return props.viewMode === 'grid-2' ? 'object-contain' : 'object-cover';
});
</script>

<template>
    <article
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all hover:border-slate-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/60 dark:hover:border-slate-700"
        :class="{
            'cursor-pointer': !showOverlay
        }"
    >
        <!-- Image qui remplit tout le cadre -->
        <div 
            class="relative aspect-square w-full overflow-hidden"
            :class="{
                'bg-neutral-100 dark:bg-neutral-800': showOverlay,
            }"
            @click="handleImageClick"
        >
            <img
                :src="exercise.image_url"
                :alt="exercise.name"
                :class="[
                    'absolute inset-0 h-full w-full',
                    imageObjectFit,
                    props.viewMode === 'grid-2' ? 'object-top' : 'object-center',
                    !showOverlay ? 'cursor-pointer' : ''
                ]"
            />
            
            <!-- Overlay au survol avec titre et catégories - uniquement pour grid-2 -->
            <div 
                v-if="showOverlay"
                class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 md:group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-6 gap-4"
            >
                <div class="flex flex-col items-center gap-3">
                    <h3 class="font-semibold text-base text-white text-center line-clamp-2">
                        {{ exercise.name && exercise.name.length > 10 ? exercise.name.substring(0, 10) + '...' : exercise.name }}
                    </h3>
                    <!-- Catégories -->
                    <div class="flex flex-wrap gap-2 justify-center">
                        <Badge
                            v-for="category in (exercise.categories || [])"
                            :key="category.id"
                            variant="secondary"
                            class="text-xs bg-white/20 text-white border-white/30"
                        >
                            {{ category.name }}
                        </Badge>
                        <Badge
                            v-if="!exercise.categories || exercise.categories.length === 0"
                            variant="secondary"
                            class="text-xs bg-white/20 text-white border-white/30"
                        >
                            {{ exercise.category_name || 'Sans catégorie' }}
                        </Badge>
                    </div>
                </div>
            </div>
            
            <!-- Boutons d'action en bas au survol - uniquement pour grid-2 -->
            <div 
                v-if="showOverlay"
                class="absolute bottom-0 left-0 right-0 bg-black/70 opacity-0 group-hover:opacity-100 md:group-hover:opacity-100 transition-opacity flex items-center justify-between p-3"
            >
                <p class="text-xs text-white">
                    {{ formattedDate }}
                </p>
                <div class="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs text-white hover:bg-white/20"
                        @click.stop="handleView"
                    >
                        <Eye class="size-4" />
                    </Button>
                    <Button
                        v-if="canEdit"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs text-white hover:bg-white/20"
                        @click.stop="handleEdit"
                    >
                        <Edit class="size-4" />
                    </Button>
                    <Button
                        v-if="canDelete"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs text-red-300 hover:text-red-200 hover:bg-red-500/30"
                        @click.stop="handleDelete"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Contenu visible uniquement pour grid-2 (1-2 par ligne) -->
        <div v-if="showContent" class="flex flex-1 flex-col gap-2 p-3 sm:gap-2.5 sm:p-4 md:hidden">
            <!-- Titre et badge -->
            <div class="flex flex-col gap-1.5">
                <h3
                    class="line-clamp-2 text-sm font-semibold leading-tight text-slate-900 dark:text-white sm:text-base"
                >
                    {{ exercise.name }}
                </h3>
                <div class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="category in (exercise.categories || [])"
                        :key="category.id"
                        variant="outline"
                        class="text-xs uppercase tracking-[0.3em]"
                    >
                        {{ category.name }}
                    </Badge>
                    <Badge
                        v-if="!exercise.categories || exercise.categories.length === 0"
                        variant="outline"
                        class="text-xs uppercase tracking-[0.3em]"
                    >
                        {{ exercise.category_name || 'Sans catégorie' }}
                    </Badge>
                </div>
            </div>

            <!-- Date et actions -->
            <div class="mt-auto flex items-center justify-between gap-2">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ formattedDate }}
                </p>
                
                <div class="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs"
                        @click="handleView"
                    >
                        <Eye class="size-4 mr-1" />
                    </Button>
                    <Button
                        v-if="canEdit"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs"
                        @click="handleEdit"
                    >
                        <Edit class="size-4 mr-1" />
                    </Button>
                    <Button
                        v-if="canDelete"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-red-900/20"
                        @click="handleDelete"
                    >
                        <Trash2 class="size-4 mr-1" />
                    </Button>
                </div>
            </div>
        </div>
    </article>
</template>


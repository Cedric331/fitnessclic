<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { X } from 'lucide-vue-next';
import type { SessionExercise } from './types';

const props = defineProps<{
    sessionExercise: SessionExercise;
    index: number;
    blockId: number;
}>();

const emit = defineEmits<{
    update: [updates: Partial<SessionExercise>];
    remove: [];
}>();

const exercise = computed(() => props.sessionExercise.exercise);
</script>

<template>
    <div class="relative group border border-neutral-200 rounded-lg p-2 bg-white hover:shadow-md transition-shadow">
        <!-- Image compacte -->
        <div class="aspect-square w-full rounded overflow-hidden bg-neutral-100 mb-2">
            <img
                v-if="exercise?.image_url"
                :src="exercise.image_url"
                :alt="exercise?.title"
                class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-neutral-400 text-xs">
                Pas d'image
            </div>
        </div>
        
        <!-- Titre -->
        <h4 class="text-xs font-semibold mb-1 line-clamp-2">
            {{ exercise?.title || 'Exercice' }}
        </h4>
        
        <!-- Bouton supprimer -->
        <Button
            variant="ghost"
            size="sm"
            class="absolute top-1 right-1 h-6 w-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity text-red-600 hover:text-red-700 hover:bg-red-50"
            @click="emit('remove')"
        >
            <X class="h-3 w-3" />
        </Button>
    </div>
</template>


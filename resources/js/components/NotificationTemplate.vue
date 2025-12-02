<template>
    <div
        :class="[
            'flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur-sm transition-all duration-300',
            type === 'success' && 'border-emerald-200/70 bg-emerald-50/90 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
            type === 'error' && 'border-red-200/70 bg-red-50/90 text-red-800 dark:border-red-800 dark:bg-red-900/40 dark:text-red-200',
            type === 'warning' && 'border-orange-200/70 bg-orange-50/90 text-orange-800 dark:border-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
            type === 'info' && 'border-blue-200/70 bg-blue-50/90 text-blue-800 dark:border-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
        ]"
    >
        <!-- Icône -->
        <div
            :class="[
                'flex-shrink-0 rounded-full p-1.5',
                type === 'success' && 'bg-emerald-100 dark:bg-emerald-900/50',
                type === 'error' && 'bg-red-100 dark:bg-red-900/50',
                type === 'warning' && 'bg-orange-100 dark:bg-orange-900/50',
                type === 'info' && 'bg-blue-100 dark:bg-blue-900/50',
            ]"
        >
            <CheckCircle2
                v-if="type === 'success'"
                class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
            />
            <XCircle
                v-else-if="type === 'error'"
                class="h-5 w-5 text-red-600 dark:text-red-400"
            />
            <AlertTriangle
                v-else-if="type === 'warning'"
                class="h-5 w-5 text-orange-600 dark:text-orange-400"
            />
            <Info
                v-else
                class="h-5 w-5 text-blue-600 dark:text-blue-400"
            />
        </div>

        <!-- Contenu -->
        <div class="flex-1 min-w-0">
            <div
                v-if="title"
                :class="[
                    'font-semibold text-sm mb-0.5',
                    type === 'success' && 'text-emerald-900 dark:text-emerald-100',
                    type === 'error' && 'text-red-900 dark:text-red-100',
                    type === 'warning' && 'text-orange-900 dark:text-orange-100',
                    type === 'info' && 'text-blue-900 dark:text-blue-100',
                ]"
            >
                {{ title }}
            </div>
            <div
                :class="[
                    'text-sm',
                    type === 'success' && 'text-emerald-700 dark:text-emerald-300',
                    type === 'error' && 'text-red-700 dark:text-red-300',
                    type === 'warning' && 'text-orange-700 dark:text-orange-300',
                    type === 'info' && 'text-blue-700 dark:text-blue-300',
                ]"
            >
                {{ text }}
            </div>
        </div>

        <!-- Bouton de fermeture -->
        <button
            @click="$emit('close')"
            :class="[
                'flex-shrink-0 rounded-md p-1 transition-colors hover:bg-black/5 dark:hover:bg-white/10',
                type === 'success' && 'text-emerald-600 dark:text-emerald-400',
                type === 'error' && 'text-red-600 dark:text-red-400',
                type === 'warning' && 'text-orange-600 dark:text-orange-400',
                type === 'info' && 'text-blue-600 dark:text-blue-400',
            ]"
        >
            <X class="h-4 w-4" />
        </button>
    </div>
</template>

<script setup lang="ts">
import { CheckCircle2, XCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

defineProps<{
    type: 'success' | 'error' | 'warning' | 'info';
    title?: string;
    text: string;
}>();

defineEmits<{
    close: [];
}>();
</script>


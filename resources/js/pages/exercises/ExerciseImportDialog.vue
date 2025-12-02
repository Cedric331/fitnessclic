<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Upload, X } from 'lucide-vue-next';

interface Props {
    open?: boolean;
    categories: Array<{
        id: number;
        name: string;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    categories: () => [],
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    imported: [];
}>();

const isOpen = ref(props.open);
const selectedFiles = ref<File[]>([]);
const selectedCategoryIds = ref<number[]>([]);
const isUploading = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

watch(() => props.open, (newValue) => {
    isOpen.value = newValue;
    if (!newValue) {
        selectedFiles.value = [];
        selectedCategoryIds.value = [];
    }
});

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
    if (!newValue) {
        selectedFiles.value = [];
        selectedCategoryIds.value = [];
    }
});

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = Array.from(target.files || []);
    
    // Add new files to the list (allow multiple selections)
    selectedFiles.value = [...selectedFiles.value, ...files];
    
    // Reset the input to allow selecting the same file again
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const removeFile = (index: number) => {
    selectedFiles.value = selectedFiles.value.filter((_, i) => i !== index);
};

const handleUpload = () => {
    if (selectedFiles.value.length === 0) {
        return;
    }

    if (selectedCategoryIds.value.length === 0) {
        alert('Veuillez sélectionner au moins une catégorie.');
        return;
    }

    isUploading.value = true;

    const formData = new FormData();
    selectedFiles.value.forEach((file) => {
        formData.append('files[]', file);
    });
    
    // Ajouter les catégories
    selectedCategoryIds.value.forEach((categoryId) => {
        formData.append('category_ids[]', categoryId.toString());
    });

    router.post('/exercises/upload-files', formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            isOpen.value = false;
            selectedFiles.value = [];
            selectedCategoryIds.value = [];
            emit('imported');
        },
        onFinish: () => {
            isUploading.value = false;
        },
    });
};

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent 
            class="sm:max-w-[800px] !z-[60] p-0 overflow-hidden max-h-[90vh] overflow-y-auto"
        >
            <DialogHeader class="px-6 pt-6 pb-4">
                <DialogTitle class="text-xl font-semibold">Importer des exercices</DialogTitle>
                <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Sélectionnez un ou plusieurs fichiers images. Chaque fichier créera un exercice avec le nom du fichier (sans extension) comme titre.
                </DialogDescription>
            </DialogHeader>
            <Separator />
            
            <div class="px-6 py-4 space-y-4">
                <!-- Zone de sélection de fichiers -->
                <div class="space-y-2">
                    <label
                        for="file-input"
                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-300 rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900/50 dark:hover:bg-slate-800 transition-colors"
                    >
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <Upload class="w-8 h-8 mb-2 text-slate-500 dark:text-slate-400" />
                            <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">
                                <span class="font-semibold">Cliquez pour sélectionner</span> ou glissez-déposez
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                PNG, JPG, GIF jusqu'à 5MB par fichier
                            </p>
                        </div>
                        <input
                            id="file-input"
                            ref="fileInputRef"
                            type="file"
                            multiple
                            accept="image/*"
                            class="hidden"
                            @change="handleFileSelect"
                        />
                    </label>
                </div>

                <!-- Liste des fichiers sélectionnés -->
                <div v-if="selectedFiles.length > 0" class="space-y-2">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Fichiers sélectionnés ({{ selectedFiles.length }})
                    </p>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <div
                            v-for="(file, index) in selectedFiles"
                            :key="index"
                            class="flex items-center justify-between p-3 rounded-lg border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900/70"
                        >
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="flex-shrink-0 w-10 h-10 rounded bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400">
                                        {{ file.name.split('.').pop()?.toUpperCase() || 'IMG' }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                        {{ file.name }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ formatFileSize(file.size) }}
                                    </p>
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="ml-2 flex-shrink-0"
                                @click="removeFile(index)"
                            >
                                <X class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Sélection des catégories -->
                <div class="space-y-2">
                    <Label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Catégories <span class="text-red-500">*</span>
                    </Label>
                    <div class="space-y-2 max-h-48 overflow-y-auto rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900/70">
                        <label
                            v-for="category in categories"
                            :key="category.id"
                            class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded px-2 py-1.5 transition"
                        >
                            <input
                                type="checkbox"
                                :value="category.id"
                                v-model="selectedCategoryIds"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-slate-600"
                            />
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ category.name }}</span>
                        </label>
                        <p v-if="categories.length === 0" class="text-xs text-slate-500 dark:text-slate-400 text-center py-2">
                            Aucune catégorie disponible
                        </p>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Sélectionnez au moins une catégorie pour tous les exercices importés
                    </p>
                </div>
            </div>

            <Separator />
            <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                <Button
                    type="button"
                    variant="outline"
                    class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 dark:hover:border-slate-600 transition-all duration-200"
                    @click="isOpen = false"
                >
                    Annuler
                </Button>
                <Button
                    type="button"
                    class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md"
                    :disabled="selectedFiles.length === 0 || selectedCategoryIds.length === 0 || isUploading"
                    @click="handleUpload"
                >
                    <Upload v-if="!isUploading" class="w-4 h-4 mr-2" />
                    <span v-if="isUploading">Importation...</span>
                    <span v-else>Importer {{ selectedFiles.length }} fichier(s)</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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
import { ref, watch, computed, onMounted } from 'vue';
import type { Exercise } from './types';
import { useNotifications } from '@/composables/useNotifications';
import { Sparkles, Upload, Loader2, RefreshCw, Coins } from 'lucide-vue-next';
import axios from 'axios';

const { error: notifyError, success: notifySuccess } = useNotifications();

interface Props {
    open?: boolean;
    exercise?: Exercise | null;
    categories: Array<{
        id: number;
        name: string;
    }>;
    isPro?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    exercise: null,
    isPro: false,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
    'updated': [];
}>();

const isOpen = ref(props.open);
const imagePreview = ref<string | null>(null);
const imageFile = ref<File | null>(null);

// AI Mode state
const isAiMode = ref(false);
const aiCredits = ref<number | null>(null);
const isAdmin = ref(false);
const isLoadingCredits = ref(false);
const isGenerating = ref(false);
const aiExerciseName = ref('');
const aiGender = ref<string>('');
const aiDescription = ref('');
const generatedImageBase64 = ref<string | null>(null);
const aiError = ref<string | null>(null);

// Check if user has unlimited credits (admin)
const hasUnlimitedCredits = computed(() => isAdmin.value || aiCredits.value === -1);

watch(() => props.open, (newValue) => {
    isOpen.value = newValue;
}, { immediate: true });

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
    if (newValue && props.isPro) {
        loadAiCredits();
    }
});

const isEditMode = computed(() => !!props.exercise);

// Disable AI mode in edit mode
watch(isEditMode, (editMode) => {
    if (editMode) {
        isAiMode.value = false;
    }
});

const exerciseForm = useForm({
    title: '',
    description: '',
    suggested_duration: '',
    category_ids: [] as number[],
    image: null as File | null,
});

// Load AI credits
const loadAiCredits = async () => {
    if (!props.isPro) return;
    
    isLoadingCredits.value = true;
    try {
        const response = await axios.get('/exercises/ai/credits');
        aiCredits.value = response.data.credits;
        isAdmin.value = response.data.is_admin ?? false;
    } catch (error) {
        console.error('Failed to load AI credits:', error);
        aiCredits.value = 0;
        isAdmin.value = false;
    } finally {
        isLoadingCredits.value = false;
    }
};

// Generate AI image
const generateAiImage = async () => {
    if (!aiExerciseName.value || !aiGender.value) {
        notifyError('Veuillez remplir le nom de l\'exercice et sélectionner le sexe du personnage.');
        return;
    }

    aiError.value = null;
    isGenerating.value = true;

    try {
        const response = await axios.post('/exercises/ai/generate', {
            exercise_name: aiExerciseName.value,
            gender: aiGender.value,
            description: aiDescription.value || null,
        });

        if (response.data.success) {
            generatedImageBase64.value = response.data.image_base64;
            imagePreview.value = `data:image/png;base64,${response.data.image_base64}`;
            aiCredits.value = response.data.credits;
            // Pre-fill the title with the exercise name
            exerciseForm.title = aiExerciseName.value;
            notifySuccess('Image générée avec succès !');
        }
    } catch (error: any) {
        const errorMessage = error.response?.data?.error || 'Une erreur est survenue lors de la génération.';
        aiError.value = errorMessage;
        notifyError(errorMessage);
        
        // Update credits if returned
        if (error.response?.data?.credits !== undefined) {
            aiCredits.value = error.response.data.credits;
        }
    } finally {
        isGenerating.value = false;
    }
};

// Reset AI generation
const resetAiGeneration = () => {
    generatedImageBase64.value = null;
    imagePreview.value = null;
    aiError.value = null;
};

// Initialiser le formulaire avec les données de l'exercice en mode édition
watch(
    () => [props.exercise, isOpen.value],
    ([exercise, open]) => {
        if (exercise && typeof exercise === 'object' && open) {
            exerciseForm.title = (exercise as any).name || (exercise as any).title || '';
            exerciseForm.description = (exercise as any).description || '';
            exerciseForm.suggested_duration = (exercise as any).suggested_duration || '';
            exerciseForm.category_ids = (exercise as any).category_ids || [];
            imagePreview.value = (exercise as any).image_url || null;
            imageFile.value = null;
        }
    },
    { immediate: true },
);

// Réinitialiser le formulaire quand la modal s'ouvre/ferme
watch(isOpen, (open) => {
    if (open) {
        if (props.exercise) {
            // Mode édition - charger les données complètes si nécessaire
            exerciseForm.title = (props.exercise as any).name || (props.exercise as any).title || '';
            exerciseForm.description = (props.exercise as any).description || '';
            exerciseForm.suggested_duration = (props.exercise as any).suggested_duration || '';
            exerciseForm.category_ids = (props.exercise as any).category_ids || [];
            imagePreview.value = (props.exercise as any).image_url || null;
            imageFile.value = null;
            isAiMode.value = false;
        } else {
            // Mode création
            exerciseForm.reset();
            imagePreview.value = null;
            imageFile.value = null;
            generatedImageBase64.value = null;
            aiExerciseName.value = '';
            aiGender.value = '';
            aiDescription.value = '';
            aiError.value = null;
        }
    } else {
        exerciseForm.reset();
        imagePreview.value = null;
        imageFile.value = null;
        generatedImageBase64.value = null;
        aiExerciseName.value = '';
        aiGender.value = '';
        aiDescription.value = '';
        aiError.value = null;
        isAiMode.value = false;
    }
});

// Watch pour mettre à jour le formulaire quand l'exercice change (même si la modal est déjà ouverte)
watch(() => props.exercise, (exercise) => {
    if (exercise && isOpen.value) {
        exerciseForm.title = (exercise as any).name || (exercise as any).title || '';
        exerciseForm.description = (exercise as any).description || '';
        exerciseForm.suggested_duration = (exercise as any).suggested_duration || '';
        exerciseForm.category_ids = (exercise as any).category_ids || [];
        imagePreview.value = (exercise as any).image_url || null;
        imageFile.value = null;
    }
}, { deep: true });

const handleImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        imageFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const handleSubmit = async () => {
    // If AI mode with generated image, use different endpoint
    if (isAiMode.value && generatedImageBase64.value) {
        try {
            const response = await axios.post('/exercises/ai/store', {
                title: exerciseForm.title,
                description: exerciseForm.description,
                suggested_duration: exerciseForm.suggested_duration,
                category_ids: exerciseForm.category_ids,
                image_base64: generatedImageBase64.value,
            });

            if (response.data.success) {
                isOpen.value = false;
                exerciseForm.reset();
                imagePreview.value = null;
                generatedImageBase64.value = null;
                aiExerciseName.value = '';
                aiGender.value = '';
                aiDescription.value = '';
                notifySuccess(response.data.message || 'Exercice créé avec succès !');
                emit('saved');
            }
        } catch (error: any) {
            const errorMessage = error.response?.data?.error || 'Une erreur est survenue lors de la création.';
            notifyError(errorMessage);
        }
        return;
    }

    // Standard form submission
    exerciseForm.image = imageFile.value;

    if (isEditMode.value && props.exercise) {
        // Mode édition
        exerciseForm.transform((data) => {
            const transformed: Record<string, any> = {
                ...data,
                _method: 'PUT',
            };
            // Ne pas inclure l'image si elle n'a pas été changée
            if (!imageFile.value) {
                delete transformed.image;
            }
            return transformed;
        }).post(`/exercises/${props.exercise.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                isOpen.value = false;
                exerciseForm.reset();
                imagePreview.value = null;
                imageFile.value = null;
                emit('updated');
            },
            onError: (errors) => {
                // Afficher la première erreur via notification
                const firstError = Object.values(errors)[0];
                if (firstError && typeof firstError === 'string') {
                    notifyError(firstError);
                } else if (Object.keys(errors).length > 0) {
                    notifyError('Une erreur est survenue lors de la modification de l\'exercice.');
                }
            },
        });
    } else {
        // Mode création
        exerciseForm.post('/exercises', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                isOpen.value = false;
                exerciseForm.reset();
                imagePreview.value = null;
                imageFile.value = null;
                emit('saved');
            },
            onError: (errors) => {
                // Afficher la première erreur via notification
                const firstError = Object.values(errors)[0];
                if (firstError && typeof firstError === 'string') {
                    notifyError(firstError);
                } else if (Object.keys(errors).length > 0) {
                    notifyError('Une erreur est survenue lors de la création de l\'exercice.');
                }
            },
        });
    }
};

const canSubmit = computed(() => {
    if (isAiMode.value) {
        return generatedImageBase64.value && exerciseForm.title && exerciseForm.category_ids.length > 0;
    }
    return isEditMode.value || imageFile.value;
});

const formId = `exercise-form-${Math.random().toString(36).substr(2, 9)}`;
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent 
            class="sm:max-w-[700px] !z-[60] p-0 overflow-hidden flex flex-col max-h-[90vh]"
        >
            <DialogHeader class="px-6 pt-6 pb-4 flex-shrink-0">
                <DialogTitle class="text-xl font-semibold">
                    {{ isEditMode ? 'Modifier l\'exercice' : 'Nouvel exercice' }}
                </DialogTitle>
                <DialogDescription class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    {{ isEditMode ? 'Modifiez les informations de l\'exercice.' : 'Créez un nouvel exercice pour votre bibliothèque.' }}
                </DialogDescription>
            </DialogHeader>
            <Separator />
            
            <!-- AI Mode Toggle (only in create mode and for Pro users) -->
            <div v-if="!isEditMode && isPro" class="px-6 py-3 bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-900/50 dark:to-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between gap-4">
                    <!-- Mode Tabs -->
                    <div class="flex rounded-lg bg-slate-200 dark:bg-slate-700 p-1 gap-1">
                        <button
                            type="button"
                            @click="isAiMode = false"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200"
                            :class="!isAiMode 
                                ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm' 
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <Upload class="h-4 w-4" />
                            <span>Upload manuel</span>
                        </button>
                        <button
                            type="button"
                            @click="isAiMode = true"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200"
                            :class="isAiMode 
                                ? 'bg-gradient-to-r from-purple-500 to-blue-500 text-white shadow-sm' 
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <Sparkles class="h-4 w-4" />
                            <span>Génération IA</span>
                        </button>
                    </div>
                    
                    <!-- Credits display -->
                    <div v-if="isAiMode" class="flex items-center gap-2 text-sm">
                        <Coins class="h-4 w-4 text-amber-500" />
                        <span class="text-slate-600 dark:text-slate-400">
                            <template v-if="isLoadingCredits">
                                <Loader2 class="h-3 w-3 animate-spin inline" />
                            </template>
                            <template v-else-if="hasUnlimitedCredits">
                                <span class="font-semibold text-green-600 dark:text-green-400">∞ Illimité</span>
                            </template>
                            <template v-else>
                                <span class="font-semibold text-amber-600 dark:text-amber-400">{{ isAdmin ? '∞' : (aiCredits ?? 0) }}</span> crédits
                            </template>
                        </span>
                    </div>
                </div>
            </div>

            <form :id="formId" @submit.prevent="handleSubmit" class="px-6 py-4 space-y-5 overflow-y-auto flex-1">
                <!-- AI Mode: Generation Form -->
                <template v-if="isAiMode && !generatedImageBase64">
                    <div class="space-y-4 p-4 rounded-lg border-2 border-dashed border-purple-200 dark:border-purple-800 bg-purple-50/50 dark:bg-purple-950/20">
                        <div class="text-center mb-4">
                            <Sparkles class="h-8 w-8 text-purple-500 mx-auto mb-2" />
                            <h3 class="font-medium text-purple-700 dark:text-purple-300">Générer une image avec l'IA</h3>
                            <p class="text-xs text-purple-600/70 dark:text-purple-400/70 mt-1">
                                Décrivez l'exercice et choisissez le sexe du personnage
                            </p>
                        </div>

                        <!-- Exercise Name for AI -->
                        <div class="space-y-2">
                            <Label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Nom de l'exercice <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="aiExerciseName"
                                type="text"
                                placeholder="Ex: Pompes, Squats, Fentes avant..."
                                class="h-10"
                            />
                        </div>

                        <!-- Gender Selection -->
                        <div class="space-y-2">
                            <Label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Sexe du personnage <span class="text-red-500">*</span>
                            </Label>
                            <select
                                v-model="aiGender"
                                class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition duration-150 ease-in-out focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white"
                            >
                                <option value="" disabled>Sélectionnez...</option>
                                <option value="homme">Homme</option>
                                <option value="femme">Femme</option>
                            </select>
                        </div>

                        <!-- Description for AI (optional) -->
                        <div class="space-y-2">
                            <Label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Description de l'exercice <span class="text-slate-400 text-xs font-normal">(optionnel)</span>
                            </Label>
                            <textarea
                                v-model="aiDescription"
                                rows="3"
                                placeholder="Décrivez la posture, les mouvements spécifiques, ou les détails importants de l'exercice pour améliorer la génération..."
                                class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition duration-150 ease-in-out placeholder:text-slate-400 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-200 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white dark:placeholder:text-slate-500"
                            />
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                💡 Plus votre description est précise, meilleur sera le résultat.
                            </p>
                        </div>

                        <!-- Error Message -->
                        <div v-if="aiError" class="p-3 rounded-md bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800">
                            <p class="text-sm text-red-600 dark:text-red-400">{{ aiError }}</p>
                        </div>

                        <!-- No Credits Warning (not shown for admins) -->
                        <div v-if="!hasUnlimitedCredits && aiCredits !== null && aiCredits <= 0" class="p-3 rounded-md bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800">
                            <p class="text-sm text-amber-600 dark:text-amber-400">
                                Vous n'avez plus de crédits IA. Vos crédits seront rechargés lors du prochain renouvellement de votre abonnement.
                            </p>
                        </div>

                        <!-- Generate Button -->
                        <Button
                            type="button"
                            @click="generateAiImage"
                            :disabled="isGenerating || !aiExerciseName || !aiGender || (!hasUnlimitedCredits && aiCredits !== null && aiCredits <= 0)"
                            class="w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white"
                        >
                            <Loader2 v-if="isGenerating" class="h-4 w-4 mr-2 animate-spin" />
                            <Sparkles v-else class="h-4 w-4 mr-2" />
                            {{ isGenerating ? 'Génération en cours...' : 'Générer l\'image' }}
                        </Button>
                    </div>
                </template>

                <!-- AI Mode: Generated Image Preview -->
                <template v-else-if="isAiMode && generatedImageBase64">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <Label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                Image générée
                            </Label>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="resetAiGeneration"
                                class="text-purple-600 hover:text-purple-700 hover:bg-purple-50 dark:hover:bg-purple-950/50"
                            >
                                <RefreshCw class="h-4 w-4 mr-1" />
                                Régénérer
                            </Button>
                        </div>
                        <div class="flex gap-4 items-start">
                            <div class="flex-shrink-0 w-40 h-40 rounded-lg overflow-hidden border-2 border-purple-200 dark:border-purple-700 shadow-lg">
                                <img
                                    :src="imagePreview!"
                                    alt="Image générée par IA"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="flex-1 p-3 rounded-md bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800">
                                <p class="text-sm text-green-700 dark:text-green-300">
                                    ✓ Image générée avec succès ! Vous pouvez maintenant compléter les informations de l'exercice ci-dessous.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Standard Mode: Image Upload -->
                <template v-else>
                    <div class="space-y-2">
                        <Label :for="`image_${formId}`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Image <span v-if="!isEditMode" class="text-red-500">*</span>
                        </Label>
                        <div class="flex gap-4 items-start">
                            <div v-if="imagePreview" class="flex-shrink-0 w-32 h-32 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700">
                                <img
                                    :src="imagePreview"
                                    alt="Aperçu de l'image"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="flex-1 space-y-2">
                                <Input
                                    :id="`image_${formId}`"
                                    type="file"
                                    accept="image/*"
                                    :required="!isEditMode && !isAiMode"
                                    class="h-10 cursor-pointer"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': exerciseForm.errors.image }"
                                    @change="handleImageChange"
                                />
                                <p v-if="exerciseForm.errors.image" class="text-xs text-red-500 mt-1">
                                    {{ exerciseForm.errors.image }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Formats acceptés : JPG, PNG, GIF. Taille maximale : 5MB
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Common fields (shown after image is ready) -->
                <template v-if="!isAiMode || generatedImageBase64">
                    <!-- Titre -->
                    <div class="space-y-2">
                        <Label :for="`title_${formId}`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Titre <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            :id="`title_${formId}`"
                            v-model="exerciseForm.title"
                            type="text"
                            placeholder="Ex: Pompes"
                            required
                            class="h-10"
                            :class="{ 'border-red-500 focus-visible:ring-red-500': exerciseForm.errors.title }"
                        />
                        <p v-if="exerciseForm.errors.title" class="text-xs text-red-500 mt-1">
                            {{ exerciseForm.errors.title }}
                        </p>
                    </div>

                    <!-- Catégories -->
                    <div class="space-y-2">
                        <Label class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Catégories <span class="text-red-500">*</span>
                        </Label>
                        <div class="space-y-2 max-h-48 overflow-y-auto rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900/70"
                            :class="{ 'border-red-500': exerciseForm.errors.category_ids }">
                            <label
                                v-for="category in categories"
                                :key="category.id"
                                class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded px-2 py-1.5 transition"
                            >
                                <input
                                    type="checkbox"
                                    :value="category.id"
                                    v-model="exerciseForm.category_ids"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-slate-600"
                                />
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ category.name }}</span>
                            </label>
                            <p v-if="categories.length === 0" class="text-xs text-slate-500 dark:text-slate-400 text-center py-2">
                                Aucune catégorie disponible
                            </p>
                        </div>
                        <p v-if="exerciseForm.errors.category_ids" class="text-xs text-red-500 mt-1">
                            {{ exerciseForm.errors.category_ids }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label :for="`description_${formId}`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Description
                        </Label>
                        <textarea
                            :id="`description_${formId}`"
                            v-model="exerciseForm.description"
                            rows="4"
                            placeholder="Décrivez l'exercice..."
                            class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition duration-150 ease-in-out placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900/70 dark:text-white dark:placeholder:text-slate-500"
                            :class="{ 'border-red-500 focus-visible:ring-red-500': exerciseForm.errors.description }"
                        />
                        <p v-if="exerciseForm.errors.description" class="text-xs text-red-500 mt-1">
                            {{ exerciseForm.errors.description }}
                        </p>
                    </div>

                    <!-- Durée suggérée -->
                    <div class="space-y-2">
                        <Label :for="`duration_${formId}`" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Durée suggérée
                        </Label>
                        <Input
                            :id="`duration_${formId}`"
                            v-model="exerciseForm.suggested_duration"
                            type="text"
                            placeholder="Ex: 30s, 1min, 2min"
                            class="h-10"
                            :class="{ 'border-red-500 focus-visible:ring-red-500': exerciseForm.errors.suggested_duration }"
                        />
                        <p v-if="exerciseForm.errors.suggested_duration" class="text-xs text-red-500 mt-1">
                            {{ exerciseForm.errors.suggested_duration }}
                        </p>
                    </div>
                </template>
            </form>
            
            <Separator class="flex-shrink-0" />
            <DialogFooter class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 flex-shrink-0">
                <Button
                    type="button"
                    variant="outline"
                    class="cursor-pointer hover:bg-slate-100 hover:border-slate-300 dark:hover:bg-slate-800 dark:hover:border-slate-600 transition-all duration-200"
                    @click="isOpen = false"
                >
                    Annuler
                </Button>
                <Button
                    v-if="!isAiMode || generatedImageBase64"
                    type="submit"
                    :form="formId"
                    class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md"
                    :disabled="exerciseForm.processing || isGenerating || !canSubmit"
                >
                    {{ exerciseForm.processing ? (isEditMode ? 'Modification...' : 'Création...') : (isEditMode ? 'Modifier' : 'Créer l\'exercice') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

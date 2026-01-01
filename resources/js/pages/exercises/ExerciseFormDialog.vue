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
import { ref, watch, computed } from 'vue';
import type { Exercise } from './types';
import { useNotifications } from '@/composables/useNotifications';

const { error: notifyError } = useNotifications();

interface Props {
    open?: boolean;
    exercise?: Exercise | null;
    categories: Array<{
        id: number;
        name: string;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    exercise: null,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': [];
    'updated': [];
}>();

const isOpen = ref(props.open);
const imagePreview = ref<string | null>(null);
const imageFile = ref<File | null>(null);

watch(() => props.open, (newValue) => {
    isOpen.value = newValue;
}, { immediate: true });

watch(isOpen, (newValue) => {
    emit('update:open', newValue);
});

const isEditMode = computed(() => !!props.exercise);

const exerciseForm = useForm({
    title: '',
    description: '',
    suggested_duration: '',
    category_ids: [] as number[],
    image: null as File | null,
});

// Initialiser le formulaire avec les données de l'exercice en mode édition
watch(
    () => [props.exercise, isOpen.value],
    ([exercise, open]) => {
        if (exercise && open) {
            exerciseForm.title = exercise.name || (exercise as any).title || '';
            exerciseForm.description = (exercise as any).description || '';
            exerciseForm.suggested_duration = (exercise as any).suggested_duration || '';
            exerciseForm.category_ids = (exercise as any).category_ids || [];
            imagePreview.value = exercise.image_url || null;
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
            exerciseForm.title = props.exercise.name || (props.exercise as any).title || '';
            exerciseForm.description = (props.exercise as any).description || '';
            exerciseForm.suggested_duration = (props.exercise as any).suggested_duration || '';
            exerciseForm.category_id = (props.exercise as any).category_id || null;
            imagePreview.value = props.exercise.image_url || null;
            imageFile.value = null;
        } else {
            // Mode création
            exerciseForm.reset();
            imagePreview.value = null;
            imageFile.value = null;
        }
    } else {
        exerciseForm.reset();
        imagePreview.value = null;
        imageFile.value = null;
    }
});

// Watch pour mettre à jour le formulaire quand l'exercice change (même si la modal est déjà ouverte)
watch(() => props.exercise, (exercise) => {
    if (exercise && isOpen.value) {
        exerciseForm.title = exercise.name || (exercise as any).title || '';
        exerciseForm.description = (exercise as any).description || '';
        exerciseForm.suggested_duration = (exercise as any).suggested_duration || '';
        exerciseForm.category_id = (exercise as any).category_id || null;
        imagePreview.value = exercise.image_url || null;
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

const handleSubmit = () => {
    // Mettre à jour l'image dans le formulaire
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
            <form :id="formId" @submit.prevent="handleSubmit" class="px-6 py-4 space-y-5 overflow-y-auto flex-1">
                <!-- Image - Layout horizontal pour économiser l'espace -->
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
                                :required="!isEditMode"
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
                    type="submit"
                    :form="formId"
                    class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md"
                    :disabled="exerciseForm.processing"
                >
                    {{ exerciseForm.processing ? (isEditMode ? 'Modification...' : 'Création...') : (isEditMode ? 'Modifier' : 'Créer l\'exercice') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>


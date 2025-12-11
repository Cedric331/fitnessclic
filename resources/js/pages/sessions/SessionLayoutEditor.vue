<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { 
    Save, 
    Download, 
    X, 
    Type, 
    Image as ImageIcon,
    Move,
    Maximize2,
    Minimize2,
    RotateCcw,
    Trash2,
    ArrowLeft,
    Search,
    Library,
    ChevronRight,
    ChevronLeft,
    Users,
    Grid2x2,
    Grid3x3,
    LayoutGrid
} from 'lucide-vue-next';
import { useNotifications } from '@/composables/useNotifications';
import Konva from 'konva';
import type { Exercise } from './types';

interface LayoutElement {
    id: string;
    type: 'image' | 'text';
    x: number;
    y: number;
    width?: number;
    height?: number;
    rotation?: number;
    imageUrl?: string;
    exerciseId?: number;
    text?: string;
    fontSize?: number;
    fontFamily?: string;
    fill?: string;
    konvaNode?: any;
}

const props = defineProps<{
    sessionId?: number;
    exercises: Exercise[];
    initialLayout?: {
        layout_data: LayoutElement[];
        canvas_width: number;
        canvas_height: number;
    } | null;
    sessionName?: string;
    sessionCustomers?: Array<{
        id: number;
        first_name: string;
        last_name: string;
        email?: string;
        full_name: string;
    }>;
    customers?: Array<{
        id: number;
        first_name: string;
        last_name: string;
        email?: string;
        full_name: string;
    }>;
}>();

const emit = defineEmits<{
    close: [];
    saved: [sessionId: number];
}>();

const page = usePage();
const { success: notifySuccess, error: notifyError } = useNotifications();

// Canvas dimensions (A4 portrait ratio)
const canvasWidth = ref(800);
const canvasHeight = ref(1000);
const scale = ref(1);

// Konva stage and layers
const containerRef = ref<HTMLDivElement | null>(null);
let stage: Konva.Stage | null = null;
let layer: Konva.Layer | null = null;
let transformer: Konva.Transformer | null = null;

// Elements
const elements = ref<LayoutElement[]>([]);
const selectedElementId = ref<string | null>(null);
const isDraggingExercise = ref(false);
const draggedExercise = ref<Exercise | null>(null);

// UI state
const showTextDialog = ref(false);
const textInput = ref('');
const textFontSize = ref(16);
const textColor = ref('#000000');
const isSaving = ref(false);
const showExerciseLibrary = ref(true);
const exerciseSearchTerm = ref('');
const draggingExerciseId = ref<number | null>(null);

// Session info
const sessionName = ref(props.sessionName || '');
const selectedCustomerIds = ref<number[]>(props.sessionCustomers?.map(c => c.id) || []);
const showCustomerModal = ref(false);
const customerSearchTerm = ref('');

// Library view mode
const libraryViewMode = ref<'grid-2' | 'grid-4' | 'grid-6'>('grid-6');

// Initialize canvas
onMounted(() => {
    if (!containerRef.value) return;

    // Load initial layout if provided
    if (props.initialLayout && props.initialLayout.layout_data) {
        canvasWidth.value = props.initialLayout.canvas_width || 800;
        canvasHeight.value = props.initialLayout.canvas_height || 1000;
        
        // S'assurer que layout_data est un tableau
        if (Array.isArray(props.initialLayout.layout_data)) {
            elements.value = JSON.parse(JSON.stringify(props.initialLayout.layout_data));
        } else {
            elements.value = [];
        }
        
        console.log('Initial layout loaded:', {
            canvasSize: { width: canvasWidth.value, height: canvasHeight.value },
            elementsCount: elements.value.length
        });
    }

    // S'assurer que le container est visible et a des dimensions
    if (!containerRef.value) {
        console.error('Container ref is null');
        return;
    }
    
    const containerRect = containerRef.value.getBoundingClientRect();
    console.log('Container dimensions:', containerRect);
    
    // Create Konva stage
    stage = new Konva.Stage({
        container: containerRef.value,
        width: canvasWidth.value * scale.value,
        height: canvasHeight.value * scale.value,
    });

    console.log('Stage created:', {
        width: canvasWidth.value * scale.value,
        height: canvasHeight.value * scale.value,
        container: containerRef.value,
        stageWidth: stage.width(),
        stageHeight: stage.height()
    });

    layer = new Konva.Layer();
    stage.add(layer);
    
    // Forcer un premier dessin pour s'assurer que le canvas est visible
    layer.draw();
    
    console.log('Layer created and added to stage, initial draw completed');

    // Create transformer for selection
    transformer = new Konva.Transformer({
        nodes: [],
        rotateEnabled: true,
        borderEnabled: true,
        borderStroke: '#0096ff',
        borderStrokeWidth: 2,
        anchorFill: '#0096ff',
        anchorStroke: '#ffffff',
        anchorSize: 8,
        // S'assurer que le redimensionnement est activé
        resizeEnabled: true,
        // Permettre le redimensionnement proportionnel avec Shift
        keepRatio: false,
        // Activer tous les ancres de redimensionnement
        enabledAnchors: ['top-left', 'top-center', 'top-right', 'middle-left', 'middle-right', 'bottom-left', 'bottom-center', 'bottom-right'],
    });
    layer.add(transformer);

    // Load existing elements
    loadElementsToCanvas().then(() => {
        // S'assurer que le layer est redessiné après le chargement
        if (layer) {
            layer.draw();
        }
    });

    // Handle clicks on stage (deselect)
    stage.on('click', (e) => {
        if (e.target === stage) {
            transformer.nodes([]);
            selectedElementId.value = null;
            layer?.draw();
        }
    });

    // Handle drag and drop from exercise library
    nextTick(() => {
        setupDragAndDrop();
    });
});

onUnmounted(() => {
    if (stage) {
        stage.destroy();
    }
});

// Load elements to canvas
const loadElementsToCanvas = async () => {
    if (!layer) {
        console.warn('Layer not initialized');
        return;
    }

    for (const element of elements.value) {
        try {
            if (element.type === 'image' && element.imageUrl) {
                await addImageToCanvas(element);
            } else if (element.type === 'text' && element.text) {
                addTextToCanvas(element);
            }
        } catch (error) {
            console.error('Error loading element:', error, element);
        }
    }
    
    // Redessiner le layer après avoir chargé tous les éléments
    if (layer) {
        layer.draw();
    }
};

// Add image to canvas
const addImageToCanvas = async (element: LayoutElement) => {
    if (!layer) return;

    return new Promise<void>((resolve, reject) => {
        const imageObj = new Image();
        
        // Ne pas utiliser crossOrigin pour les images locales
        // imageObj.crossOrigin = 'anonymous';
        
        imageObj.onload = () => {
            try {
                console.log('Image loaded successfully:', {
                    naturalWidth: imageObj.width,
                    naturalHeight: imageObj.height,
                    src: imageObj.src
                });
                
                // Définir une taille par défaut si l'image n'a pas de dimensions
                let width = element.width || imageObj.width || 200;
                let height = element.height || imageObj.height || 200;
                
                // Si l'image n'a pas de dimensions naturelles, utiliser une taille par défaut
                if (!imageObj.width || !imageObj.height) {
                    width = 200;
                    height = 200;
                }
                
                // Limiter la taille si l'image est trop grande
                const maxWidth = 300;
                const maxHeight = 300;
                const minWidth = 50;
                const minHeight = 50;
                
                // Redimensionner proportionnellement si nécessaire
                if (width > maxWidth || height > maxHeight) {
                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                    width = width * ratio;
                    height = height * ratio;
                }
                
                // S'assurer que la taille minimale est respectée
                if (width < minWidth) {
                    const ratio = minWidth / width;
                    width = minWidth;
                    height = height * ratio;
                }
                if (height < minHeight) {
                    const ratio = minHeight / height;
                    height = minHeight;
                    width = width * ratio;
                }
                
                // S'assurer que la position est dans les limites du canvas
                let x = element.x;
                let y = element.y;
                
                // Si la position n'est pas définie ou est en dehors, placer au centre
                if (x < 0 || x > canvasWidth.value || y < 0 || y > canvasHeight.value) {
                    x = (canvasWidth.value - width) / 2;
                    y = (canvasHeight.value - height) / 2;
                    element.x = x;
                    element.y = y;
                }
                
                console.log('Creating Konva image with:', {
                    x, y, width, height,
                    canvasWidth: canvasWidth.value,
                    canvasHeight: canvasHeight.value
                });
                
                const konvaImage = new Konva.Image({
                    x: x,
                    y: y,
                    image: imageObj,
                    width: width,
                    height: height,
                    rotation: element.rotation || 0,
                    draggable: true,
                    id: element.id,
                });

                konvaImage.on('dragend', () => {
                    updateElementPosition(element.id, konvaImage.x(), konvaImage.y());
                });

                konvaImage.on('transformend', () => {
                    updateElementTransform(element.id, konvaImage);
                });

                konvaImage.on('click', () => {
                    selectElement(element.id, konvaImage);
                });

                layer.add(konvaImage);
                element.konvaNode = konvaImage;
                
                // Mettre à jour les dimensions dans l'élément
                element.width = width;
                element.height = height;
                element.x = x;
                element.y = y;
                
                console.log('Image added to layer:', {
                    nodeId: konvaImage.id(),
                    position: { x: konvaImage.x(), y: konvaImage.y() },
                    size: { width: konvaImage.width(), height: konvaImage.height() },
                    layerChildren: layer.getChildren().length
                });
                
                // Forcer le redessin du layer plusieurs fois pour s'assurer
                layer.draw();
                
                // Redessiner après un court délai pour s'assurer que tout est bien rendu
                setTimeout(() => {
                    if (layer) {
                        layer.draw();
                        console.log('Layer redrawn after timeout');
                    }
                }, 100);
                
                // Vérifier que l'image est bien visible
                const bounds = konvaImage.getClientRect();
                console.log('Image bounds:', bounds);
                console.log('Stage size:', stage?.width(), stage?.height());
                
                resolve();
            } catch (error) {
                console.error('Error creating Konva image:', error);
                reject(error);
            }
        };
        
        imageObj.onerror = (error) => {
            console.error('Error loading image:', error, element.imageUrl);
            notifyError(`Erreur lors du chargement de l'image: ${element.imageUrl}`);
            reject(new Error('Failed to load image'));
        };
        
        // Définir la source après avoir attaché les handlers
        if (element.imageUrl) {
            // Vérifier si l'URL est absolue ou relative
            let imageUrl = element.imageUrl;
            
            // Si l'URL ne commence pas par http/https, ajouter le protocole et le domaine si nécessaire
            if (!imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
                if (!imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                // Si c'est une URL relative, s'assurer qu'elle commence par /storage/ pour les médias Laravel
                if (imageUrl.startsWith('/storage/')) {
                    // C'est déjà correct
                } else if (!imageUrl.startsWith('http')) {
                    // Essayer de construire l'URL complète
                    imageUrl = window.location.origin + (imageUrl.startsWith('/') ? imageUrl : '/' + imageUrl);
                }
            }
            
            console.log('Loading image from URL:', imageUrl);
            console.log('Original URL:', element.imageUrl);
            
            imageObj.src = imageUrl;
        } else {
            console.error('No image URL provided for element:', element);
            reject(new Error('No image URL provided'));
        }
    });
};

// Add text to canvas
const addTextToCanvas = (element: LayoutElement) => {
    if (!layer) return;

    const konvaText = new Konva.Text({
        x: element.x,
        y: element.y,
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fill: element.fill || '#000000',
        draggable: true,
        id: element.id,
    });

    konvaText.on('dragend', () => {
        updateElementPosition(element.id, konvaText.x(), konvaText.y());
    });

    konvaText.on('transformend', () => {
        updateElementTransform(element.id, konvaText);
    });

    konvaText.on('dblclick', () => {
        editText(element);
    });

    konvaText.on('click', () => {
        selectElement(element.id, konvaText);
    });

    layer.add(konvaText);
    element.konvaNode = konvaText;
};

// Update element position
const updateElementPosition = (id: string, x: number, y: number) => {
    const element = elements.value.find(el => el.id === id);
    if (element) {
        element.x = x;
        element.y = y;
    }
};

// Update element transform
const updateElementTransform = (id: string, node: any) => {
    const element = elements.value.find(el => el.id === id);
    if (element && node) {
        // Obtenir les nouvelles dimensions après transformation
        const newWidth = node.width() * node.scaleX();
        const newHeight = node.height() * node.scaleY();
        const newX = node.x();
        const newY = node.y();
        const newRotation = node.rotation();
        
        console.log('Updating element transform:', {
            id,
            oldSize: { width: element.width, height: element.height },
            newSize: { width: newWidth, height: newHeight },
            scale: { x: node.scaleX(), y: node.scaleY() }
        });
        
        // Mettre à jour les dimensions du nœud Konva AVANT de réinitialiser les scales
        node.width(newWidth);
        node.height(newHeight);
        
        // Réinitialiser les scales à 1 après avoir mis à jour les dimensions
        node.scaleX(1);
        node.scaleY(1);
        
        // Mettre à jour l'élément dans le tableau
        element.x = newX;
        element.y = newY;
        element.width = newWidth;
        element.height = newHeight;
        element.rotation = newRotation;
        
        // Forcer le redessin
        if (layer) {
            layer.draw();
        }
        
        console.log('Element transform updated:', {
            elementSize: { width: element.width, height: element.height },
            nodeSize: { width: node.width(), height: node.height() }
        });
    }
};

// Select element
const selectElement = (id: string, node: any) => {
    if (!transformer || !node) return;
    selectedElementId.value = id;
    
    // S'assurer que le nœud est bien attaché au transformer
    transformer.nodes([node]);
    
    // Forcer le transformer à se mettre à jour
    transformer.forceUpdate();
    
    // Redessiner le layer
    if (layer) {
        layer.draw();
    }
    
    console.log('Element selected:', {
        id,
        nodeSize: { width: node.width(), height: node.height() },
        nodePosition: { x: node.x(), y: node.y() }
    });
};

// Handle exercise drop
const handleExerciseDrop = async (exercise: Exercise, x?: number, y?: number) => {
    if (!layer || !stage) {
        notifyError('Canvas non initialisé');
        console.error('Layer or stage not initialized', { layer: !!layer, stage: !!stage });
        return;
    }
    
    if (!exercise.image_url) {
        notifyError('Cet exercice n\'a pas d\'image');
        return;
    }

    console.log('Adding exercise image:', {
        exercise: exercise.title,
        imageUrl: exercise.image_url,
        position: { x: x ?? 100, y: y ?? 100 }
    });

    try {
        const imageUrl = exercise.image_url;
        const element: LayoutElement = {
            id: `img-${Date.now()}`,
            type: 'image',
            x: x ?? 100,
            y: y ?? 100,
            imageUrl,
            exerciseId: exercise.id,
        };

        elements.value.push(element);
        
        console.log('Element added to array, loading image...');
        await addImageToCanvas(element);
        
        console.log('Image loaded, drawing layer...');
        
        // S'assurer que le layer est redessiné
        if (layer) {
            layer.draw();
            console.log('Layer drawn');
        }
        
        // Vérifier que l'image est bien sur le canvas
        const addedNode = element.konvaNode;
        if (addedNode && layer) {
            const nodes = layer.getChildren();
            console.log('Nodes on layer:', nodes.length);
            console.log('Added node:', addedNode);
        }
        
        notifySuccess(`Image de "${exercise.title}" ajoutée`);
    } catch (error: any) {
        console.error('Error adding exercise image:', error);
        notifyError(`Erreur lors de l'ajout de l'image: ${error.message || 'Erreur inconnue'}`);
        
        // Retirer l'élément de la liste si l'ajout a échoué
        const lastElement = elements.value[elements.value.length - 1];
        if (lastElement && lastElement.exerciseId === exercise.id) {
            elements.value.pop();
        }
    }
};

// Handle exercise drag start from library
const handleExerciseDragStart = (event: DragEvent, exercise: Exercise) => {
    if (!event.dataTransfer) return;
    
    draggingExerciseId.value = exercise.id;
    event.dataTransfer.effectAllowed = 'copy';
    event.dataTransfer.setData('application/json', JSON.stringify(exercise));
    event.dataTransfer.setData('text/plain', exercise.id.toString());
};

// Handle exercise drag end
const handleExerciseDragEnd = () => {
    draggingExerciseId.value = null;
};

// Filtered exercises
// Computed properties
const availableCustomers = computed(() => props.customers || []);
const selectedCustomers = computed(() => {
    return availableCustomers.value.filter(c => selectedCustomerIds.value.includes(c.id));
});

const filteredCustomers = computed(() => {
    if (!customerSearchTerm.value.trim()) {
        return availableCustomers.value;
    }
    const search = customerSearchTerm.value.toLowerCase();
    return availableCustomers.value.filter(customer => {
        const fullName = (customer.full_name || `${customer.first_name} ${customer.last_name}`).toLowerCase();
        const email = (customer.email || '').toLowerCase();
        return fullName.includes(search) || email.includes(search);
    });
});

const libraryGridCols = computed(() => {
    switch (libraryViewMode.value) {
        case 'grid-2':
            return 'grid-cols-2';
        case 'grid-4':
            return 'grid-cols-4';
        case 'grid-6':
            return 'grid-cols-6';
        default:
            return 'grid-cols-6';
    }
});

const filteredExercises = computed(() => {
    if (!exerciseSearchTerm.value.trim()) {
        return props.exercises.filter(ex => ex.image_url);
    }
    const search = exerciseSearchTerm.value.toLowerCase();
    return props.exercises.filter(ex => 
        ex.image_url && 
        ex.title.toLowerCase().includes(search)
    );
});

// Add text
const addText = () => {
    showTextDialog.value = true;
    textInput.value = '';
    textFontSize.value = 16;
    textColor.value = '#000000';
};

// Confirm text addition
const confirmText = () => {
    if (!textInput.value.trim() || !layer) return;

    if (editingElement) {
        // Update existing text
        editingElement.text = textInput.value;
        editingElement.fontSize = textFontSize.value;
        editingElement.fill = textColor.value;
        
        if (editingElement.konvaNode) {
            editingElement.konvaNode.text(textInput.value);
            editingElement.konvaNode.fontSize(textFontSize.value);
            editingElement.konvaNode.fill(textColor.value);
            layer.draw();
        }
        editingElement = null;
    } else {
        // Add new text
        const element: LayoutElement = {
            id: `text-${Date.now()}`,
            type: 'text',
            x: 100,
            y: 100,
            text: textInput.value,
            fontSize: textFontSize.value,
            fontFamily: 'Arial',
            fill: textColor.value,
        };

        elements.value.push(element);
        addTextToCanvas(element);
        layer.draw();
    }
    showTextDialog.value = false;
};

// Edit text
let editingElement: LayoutElement | null = null;
const editText = (element: LayoutElement) => {
    editingElement = element;
    textInput.value = element.text || '';
    textFontSize.value = element.fontSize || 16;
    textColor.value = element.fill || '#000000';
    showTextDialog.value = true;
};

// Delete selected element
const deleteSelected = () => {
    if (!selectedElementId.value || !layer) return;

    const element = elements.value.find(el => el.id === selectedElementId.value);
    if (element && element.konvaNode) {
        element.konvaNode.destroy();
        elements.value = elements.value.filter(el => el.id !== selectedElementId.value);
        if (transformer) {
            transformer.nodes([]);
        }
        selectedElementId.value = null;
        layer.draw();
    }
};

// Save layout
const saveLayout = async () => {
    isSaving.value = true;

    try {
        const layoutData = elements.value.map(el => ({
            id: el.id,
            type: el.type,
            x: el.x,
            y: el.y,
            width: el.width,
            height: el.height,
            rotation: el.rotation,
            imageUrl: el.imageUrl,
            exerciseId: el.exerciseId,
            text: el.text,
            fontSize: el.fontSize,
            fontFamily: el.fontFamily,
            fill: el.fill,
        }));

        const url = props.sessionId 
            ? `/sessions/${props.sessionId}/layout`
            : '/sessions/layout';

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (page.props as any).csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
            body: JSON.stringify({
                layout_data: layoutData,
                canvas_width: canvasWidth.value,
                canvas_height: canvasHeight.value,
                session_id: props.sessionId,
                session_name: sessionName.value || undefined,
                customer_ids: selectedCustomerIds.value.length > 0 ? selectedCustomerIds.value : undefined,
            }),
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Erreur lors de la sauvegarde');
        }

        const data = await response.json();
        notifySuccess('Mise en page sauvegardée avec succès');
        emit('saved', data.session_id);
    } catch (error: any) {
        notifyError(error.message || 'Erreur lors de la sauvegarde');
    } finally {
        isSaving.value = false;
    }
};

// Export to PDF
const exportToPDF = async () => {
    if (!stage) return;

    try {
        // Convert canvas to image
        const dataURL = stage.toDataURL({ pixelRatio: 2 });
        
        // Create a new window with the image and trigger print
        const printWindow = window.open();
        if (printWindow) {
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Mise en page</title>
                    <style>
                        body { margin: 0; padding: 0; }
                        img { width: 100%; height: auto; display: block; }
                        @media print {
                            body { margin: 0; }
                            img { width: 100%; page-break-after: avoid; }
                        }
                    </style>
                </head>
                <body>
                    <img src="${dataURL}" alt="Mise en page" />
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.print();
                }, 250);
            };
        } else {
            // Fallback: download as image
            const link = document.createElement('a');
            link.download = `mise-en-page-${Date.now()}.png`;
            link.href = dataURL;
            link.click();
            notifySuccess('Image téléchargée. Utilisez "Imprimer" dans votre navigateur pour créer un PDF.');
        }
    } catch (error: any) {
        notifyError('Erreur lors de l\'export');
        console.error(error);
    }
};

// Setup drag and drop
const setupDragAndDrop = () => {
    if (!containerRef.value) return;

    const handleDragOver = (e: DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.dataTransfer) {
            e.dataTransfer.dropEffect = 'copy';
        }
        isDraggingExercise.value = true;
    };

    const handleDragLeave = (e: DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        // Only hide if we're leaving the container
        if (!containerRef.value?.contains(e.relatedTarget as Node)) {
            isDraggingExercise.value = false;
        }
    };

    const handleDrop = async (e: DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        isDraggingExercise.value = false;

        if (!stage) return;

        // Get drop position relative to stage
        const pointerPos = stage.getPointerPosition();
        if (!pointerPos) return;

        try {
            const exerciseData = e.dataTransfer?.getData('application/json');
            if (exerciseData) {
                const exercise: Exercise = JSON.parse(exerciseData);
                await handleExerciseDrop(exercise, pointerPos.x, pointerPos.y);
            } else {
                // Try to get exercise ID from text/plain
                const exerciseId = e.dataTransfer?.getData('text/plain');
                if (exerciseId) {
                    const exercise = props.exercises.find(ex => ex.id === parseInt(exerciseId));
                    if (exercise) {
                        await handleExerciseDrop(exercise, pointerPos.x, pointerPos.y);
                    }
                }
            }
        } catch (error) {
            console.error('Error handling drop:', error);
        }
    };

    containerRef.value.addEventListener('dragover', handleDragOver);
    containerRef.value.addEventListener('dragleave', handleDragLeave);
    containerRef.value.addEventListener('drop', handleDrop);

    // Cleanup on unmount
    onUnmounted(() => {
        if (containerRef.value) {
            containerRef.value.removeEventListener('dragover', handleDragOver);
            containerRef.value.removeEventListener('dragleave', handleDragLeave);
            containerRef.value.removeEventListener('drop', handleDrop);
        }
    });
};
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Toolbar -->
        <div class="flex items-center justify-between p-4 border-b bg-white dark:bg-neutral-900">
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" @click="emit('close')">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Retour
                </Button>
                <h2 class="text-lg font-semibold">Éditeur de mise en page</h2>
            </div>
            <div class="flex items-center gap-2">
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="showExerciseLibrary = !showExerciseLibrary"
                >
                    <Library class="h-4 w-4 mr-2" />
                    {{ showExerciseLibrary ? 'Masquer' : 'Afficher' }} bibliothèque
                </Button>
                <Button variant="outline" size="sm" @click="addText">
                    <Type class="h-4 w-4 mr-2" />
                    Texte
                </Button>
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="deleteSelected"
                    :disabled="!selectedElementId"
                >
                    <Trash2 class="h-4 w-4 mr-2" />
                    Supprimer
                </Button>
                <Button variant="outline" size="sm" @click="exportToPDF">
                    <Download class="h-4 w-4 mr-2" />
                    Exporter PDF
                </Button>
                <Button size="sm" @click="saveLayout" :disabled="isSaving">
                    <Save class="h-4 w-4 mr-2" />
                    {{ isSaving ? 'Sauvegarde...' : 'Enregistrer' }}
                </Button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Left Sidebar: Session Info -->
            <div class="w-80 border-r bg-white dark:bg-neutral-900 flex flex-col overflow-hidden">
                <div class="p-4 border-b">
                    <h3 class="font-semibold mb-4">Informations de la séance</h3>
                    
                    <!-- Session Name -->
                    <div class="space-y-2 mb-4">
                        <Label>Nom de la séance</Label>
                        <Input
                            v-model="sessionName"
                            placeholder="Nom de la séance..."
                            class="w-full"
                        />
                    </div>

                    <!-- Customers -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label class="flex items-center gap-2">
                                <Users class="h-4 w-4" />
                                Clients
                            </Label>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="showCustomerModal = true"
                                class="text-xs"
                            >
                                {{ selectedCustomerIds.length > 0 ? `${selectedCustomerIds.length} sélectionné(s)` : 'Ajouter' }}
                            </Button>
                        </div>
                        
                        <!-- Selected customers list -->
                        <div v-if="selectedCustomers.length > 0" class="space-y-1 max-h-32 overflow-y-auto">
                            <div
                                v-for="customer in selectedCustomers"
                                :key="customer.id"
                                class="flex items-center justify-between p-2 bg-neutral-100 dark:bg-neutral-800 rounded text-sm"
                            >
                                <span class="truncate">{{ customer.full_name }}</span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="selectedCustomerIds = selectedCustomerIds.filter(id => id !== customer.id)"
                                    class="h-6 w-6 p-0"
                                >
                                    <X class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                        <p v-else class="text-xs text-neutral-500">Aucun client sélectionné</p>
                    </div>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="flex-1 overflow-auto bg-neutral-100 dark:bg-neutral-800 p-4">
                <div class="flex justify-center">
                    <div 
                        ref="containerRef"
                        class="bg-white shadow-lg"
                        :class="{ 'border-2 border-blue-500 border-dashed': isDraggingExercise }"
                        :style="{ 
                            width: `${canvasWidth}px`, 
                            height: `${canvasHeight}px`,
                            minWidth: `${canvasWidth}px`,
                            minHeight: `${canvasHeight}px`
                        }"
                    ></div>
                </div>
            </div>

            <!-- Exercise Library Sidebar -->
            <div 
                v-if="showExerciseLibrary"
                class="w-96 border-l bg-white dark:bg-neutral-900 flex flex-col overflow-hidden"
            >
                <div class="p-4 border-b space-y-3">
                    <h3 class="font-semibold flex items-center gap-2">
                        <Library class="h-4 w-4" />
                        Bibliothèque d'exercices
                    </h3>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-neutral-400" />
                        <Input
                            v-model="exerciseSearchTerm"
                            placeholder="Rechercher un exercice..."
                            class="pl-9"
                        />
                    </div>
                    <!-- View mode selector -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-neutral-500">Affichage:</span>
                        <div class="flex gap-1">
                            <Button
                                variant="ghost"
                                size="sm"
                                :class="{ 'bg-neutral-200 dark:bg-neutral-700': libraryViewMode === 'grid-2' }"
                                @click="libraryViewMode = 'grid-2'"
                                class="h-7 w-7 p-0"
                                title="2 par ligne"
                            >
                                <Grid2x2 class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                :class="{ 'bg-neutral-200 dark:bg-neutral-700': libraryViewMode === 'grid-4' }"
                                @click="libraryViewMode = 'grid-4'"
                                class="h-7 w-7 p-0"
                                title="4 par ligne"
                            >
                                <LayoutGrid class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                :class="{ 'bg-neutral-200 dark:bg-neutral-700': libraryViewMode === 'grid-6' }"
                                @click="libraryViewMode = 'grid-6'"
                                class="h-7 w-7 p-0"
                                title="6 par ligne"
                            >
                                <Grid3x3 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <div v-if="filteredExercises.length === 0" class="text-center py-12 text-neutral-500">
                        <p class="text-sm">Aucun exercice avec image trouvé</p>
                    </div>
                    <div v-else :class="['grid gap-3', libraryGridCols]">
                        <div
                            v-for="exercise in filteredExercises"
                            :key="exercise.id"
                            :class="{
                                'group cursor-move hover:shadow-lg transition-all relative rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-500': true,
                                'opacity-70 scale-95': draggingExerciseId === exercise.id,
                                'ring-2 ring-blue-500': draggingExerciseId === exercise.id
                            }"
                            :draggable="true"
                            @dragstart="handleExerciseDragStart($event, exercise)"
                            @dragend="handleExerciseDragEnd"
                            @click="() => handleExerciseDrop(exercise, canvasWidth.value / 2 - 50, canvasHeight.value / 2 - 50)"
                        >
                            <div class="relative aspect-square w-full overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                <img
                                    v-if="exercise.image_url"
                                    :src="exercise.image_url"
                                    :alt="exercise.title"
                                    class="h-full w-full object-cover"
                                    draggable="false"
                                />
                                <div
                                    v-else
                                    class="h-full w-full flex items-center justify-center text-neutral-400"
                                >
                                    <span class="text-xs">Aucune image</span>
                                </div>
                                <!-- Overlay au survol -->
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <p class="text-white text-xs text-center px-2 line-clamp-2">
                                        {{ exercise.title }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Selection Modal -->
        <div v-if="showCustomerModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showCustomerModal = false">
            <Card class="w-full max-w-md max-h-[80vh] flex flex-col">
                <CardContent class="p-6 space-y-4 flex-1 overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Sélectionner les clients</h3>
                        <Button variant="ghost" size="sm" @click="showCustomerModal = false">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                    
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-neutral-400" />
                        <Input
                            v-model="customerSearchTerm"
                            placeholder="Rechercher un client..."
                            class="pl-9"
                        />
                    </div>

                    <div class="flex-1 overflow-y-auto space-y-2">
                        <div
                            v-for="customer in filteredCustomers"
                            :key="customer.id"
                            class="flex items-center space-x-2 p-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :checked="selectedCustomerIds.includes(customer.id)"
                                @change="(e) => {
                                    if (e.target.checked) {
                                        if (!selectedCustomerIds.includes(customer.id)) {
                                            selectedCustomerIds.push(customer.id);
                                        }
                                    } else {
                                        selectedCustomerIds = selectedCustomerIds.filter(id => id !== customer.id);
                                    }
                                }"
                                class="rounded"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ customer.full_name }}</p>
                                <p v-if="customer.email" class="text-xs text-neutral-500 truncate">{{ customer.email }}</p>
                            </div>
                        </div>
                        <p v-if="filteredCustomers.length === 0" class="text-xs text-neutral-500 text-center py-4">
                            <span v-if="customerSearchTerm.trim()">
                                Aucun client trouvé pour "{{ customerSearchTerm }}"
                            </span>
                            <span v-else>
                                Aucun client disponible
                            </span>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-sm pt-4 border-t">
                        <span class="text-neutral-600 dark:text-neutral-400">
                            <span v-if="selectedCustomerIds.length > 0" class="font-medium text-blue-600 dark:text-blue-400">
                                {{ selectedCustomerIds.length }} client(s) sélectionné(s)
                            </span>
                            <span v-else class="text-neutral-500">
                                Aucun client sélectionné
                            </span>
                        </span>
                        <Button
                            v-if="selectedCustomerIds.length > 0"
                            variant="ghost"
                            size="sm"
                            @click="selectedCustomerIds = []"
                            class="text-xs text-red-600 hover:text-red-700"
                        >
                            <X class="h-3.5 w-3.5 mr-1" />
                            Tout effacer
                        </Button>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <Button variant="outline" @click="showCustomerModal = false">Fermer</Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Text Dialog -->
        <div v-if="showTextDialog" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <Card class="w-full max-w-md">
                <CardContent class="p-6 space-y-4">
                    <h3 class="text-lg font-semibold">Ajouter du texte</h3>
                    <div class="space-y-2">
                    <Label>Texte</Label>
                    <Input v-model="textInput" placeholder="Entrez votre texte..." />
                </div>
                <div class="space-y-2">
                    <Label>Taille (px)</Label>
                    <Input v-model.number="textFontSize" type="number" min="8" max="72" />
                </div>
                <div class="space-y-2">
                    <Label>Couleur</Label>
                    <Input v-model="textColor" type="color" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="showTextDialog = false">Annuler</Button>
                    <Button @click="confirmText">Ajouter</Button>
                </div>
            </CardContent>
        </Card>
        </div>
    </div>
</template>

<style scoped>
/* Styles pour le canvas Konva */
</style>


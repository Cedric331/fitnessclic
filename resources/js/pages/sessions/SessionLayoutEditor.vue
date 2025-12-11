<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
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
    LayoutGrid,
    Square,
    Circle,
    Minus,
    ArrowRight,
    Highlighter
} from 'lucide-vue-next';
import { useNotifications } from '@/composables/useNotifications';
import Konva from 'konva';
import type { Exercise } from './types';

interface LayoutElement {
    id: string;
    type: 'image' | 'text' | 'rect' | 'ellipse' | 'line' | 'arrow' | 'highlight';
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
    stroke?: string;
    strokeWidth?: number;
    opacity?: number;
    points?: number[];
    radiusX?: number;
    radiusY?: number;
    pointerLength?: number;
    pointerWidth?: number;
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

// Canvas dimensions (A4 portrait format - 210mm x 297mm)
// Format A4 exact: 210mm x 297mm
// Ratio: 297/210 = 1.4142857...
// Pour correspondre exactement au PDF, on augmente la hauteur pour qu'elle corresponde
// Si largeur = 800px, hauteur = 800 * 1.4142857 = 1131.43px
// Pour éviter les espaces dans le PDF, on utilise une hauteur légèrement plus grande
const canvasWidth = ref(800);
// Pour correspondre exactement au format A4 (210mm x 297mm) dans le PDF
// Ratio exact: 297/210 = 1.4142857...
// Avec largeur 800px: 800 * 1.4142857 = 1131.43px
// Pour éviter les espaces dans le PDF, on augmente légèrement
const canvasHeight = ref(1140); // Augmenté pour correspondre exactement au PDF
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

// Undo/Redo history
const history = ref<LayoutElement[][]>([]);
const historyIndex = ref(-1);
const maxHistorySize = 50;

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

// Shape drawing state
const isDrawingShape = ref(false);
const drawingShapeType = ref<'rect' | 'ellipse' | 'line' | 'arrow' | 'highlight' | null>(null);
const shapeStartPos = ref<{ x: number; y: number } | null>(null);
const shapeStrokeColor = ref('#000000');
const shapeStrokeWidth = ref(2);
const shapeOpacity = ref(1);
const tempShapeRef = ref<any>(null);
const isMouseDown = ref(false);

// Store current drawing event handlers to clean them up
let currentDrawingHandlers: {
    mousedown?: (e: any) => void;
    mousemove?: (e: any) => void;
    mouseup?: (e: any) => void;
} = {};

// Library view mode
const libraryViewMode = ref<'grid-2' | 'grid-4' | 'grid-6'>('grid-6');

// Initialize canvas
onMounted(() => {
    if (!containerRef.value) return;

    // Load initial layout if provided
    if (props.initialLayout && props.initialLayout.layout_data) {
        canvasWidth.value = props.initialLayout.canvas_width || 800;
        canvasHeight.value = props.initialLayout.canvas_height || 1140; // Format A4 par défaut
        
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
    
    // Forcer la mise à jour du canvas HTML pour s'assurer que les dimensions sont correctes
    const stageContent = stage.getContent();
    if (stageContent) {
        stageContent.style.width = `${canvasWidth.value * scale.value}px`;
        stageContent.style.height = `${canvasHeight.value * scale.value}px`;
    }
    
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
        // Fonction personnalisée pour calculer le bounding box
        boundBoxFunc: (oldBox, newBox) => {
            return newBox;
        },
    });
    layer.add(transformer);

    // Load existing elements
    loadElementsToCanvas().then(() => {
        // S'assurer que le layer est redessiné après le chargement
        if (layer) {
            layer.draw();
        }
        // Forcer la mise à jour du canvas HTML après le chargement
        nextTick(() => {
            if (stage) {
                const stageContent = stage.getContent();
                if (stageContent) {
                    stageContent.style.width = `${canvasWidth.value * scale.value}px`;
                    stageContent.style.height = `${canvasHeight.value * scale.value}px`;
                }
            }
        });
        // Initialize history with initial state
        saveToHistory();
    });

    // Handle clicks on stage (deselect only if clicking on empty stage)
    stage.on('click', (e) => {
        // Ne pas désélectionner si on clique sur un élément (les éléments gèrent leur propre sélection)
        if (e.target === stage || e.target === layer) {
            transformer.nodes([]);
            selectedElementId.value = null;
            layer?.draw();
        }
    });

    // Handle drag and drop from exercise library
    nextTick(() => {
        setupDragAndDrop();
    });
    
    // Handle keyboard events for delete
    const handleKeyDown = (e: KeyboardEvent) => {
        // Prevent deletion if user is typing in an input field
        if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) {
            return;
        }
        
        // Delete key (both Delete and Backspace)
        if ((e.key === 'Delete' || e.key === 'Backspace') && selectedElementId.value) {
            e.preventDefault();
            deleteSelected();
        }
    };
    
    window.addEventListener('keydown', handleKeyDown);
    
    // Cleanup on unmount
    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeyDown);
    });
});

// Watch canvas dimensions to recalculate footer position and update stage
watch([canvasWidth, canvasHeight], () => {
    if (stage && layer) {
        // Mettre à jour les dimensions du stage
        stage.width(canvasWidth.value * scale.value);
        stage.height(canvasHeight.value * scale.value);
        // Forcer la mise à jour du canvas HTML
        stage.getContent().style.width = `${canvasWidth.value * scale.value}px`;
        stage.getContent().style.height = `${canvasHeight.value * scale.value}px`;
        // Recalculer la position du footer
        recalculateFooterPosition();
        // Redessiner
        layer.draw();
    }
}, { immediate: false });

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
            } else if (['rect', 'ellipse', 'line', 'arrow', 'highlight'].includes(element.type)) {
                addShapeToCanvas(element);
            } else if (['rect', 'ellipse', 'line', 'arrow', 'highlight'].includes(element.type)) {
                addShapeToCanvas(element);
            }
        } catch (error) {
            console.error('Error loading element:', error, element);
        }
    }
    
    // Ajouter le footer par défaut si aucun footer n'existe
    await addDefaultFooterIfNeeded();
    
    // Redessiner le layer après avoir chargé tous les éléments
    if (layer) {
        layer.draw();
    }
};

// Recalculate footer position (useful when canvas dimensions change)
const recalculateFooterPosition = () => {
    const footerHeight = 60;
    const footerY = canvasHeight.value - footerHeight;
    
    const footerElements = elements.value.filter(el => el.id && el.id.includes('footer'));
    const footerTextEl = footerElements.find(el => el.id?.includes('footer-text'));
    
    footerElements.forEach(footerEl => {
        if (footerEl.type === 'rect' && footerEl.id?.includes('footer-bg')) {
            footerEl.y = footerY;
            footerEl.width = canvasWidth.value;
            if (footerEl.konvaNode) {
                footerEl.konvaNode.y(footerY);
                footerEl.konvaNode.width(canvasWidth.value);
            }
        } else if (footerEl.type === 'text' && footerEl.id?.includes('footer-text')) {
            footerEl.x = canvasWidth.value / 2;
            footerEl.y = footerY + footerHeight / 2;
            if (footerEl.konvaNode) {
                footerEl.konvaNode.x(canvasWidth.value / 2);
                footerEl.konvaNode.y(footerY + footerHeight / 2);
                footerEl.konvaNode.offsetX(footerEl.konvaNode.width() / 2);
                footerEl.konvaNode.offsetY(footerEl.konvaNode.height() / 2);
            }
        } else if (footerEl.type === 'image' && footerEl.id?.includes('footer-logo')) {
            const logoHeight = footerEl.height || 40;
            footerEl.y = footerY + (footerHeight - logoHeight) / 2;
            // Recalculer la position X du logo par rapport au texte
            if (footerTextEl && footerTextEl.konvaNode && footerEl.konvaNode) {
                const textWidth = footerTextEl.konvaNode.width();
                const textX = canvasWidth.value / 2;
                const textRight = textX + textWidth / 2;
                const newLogoX = textRight + 15;
                footerEl.x = newLogoX;
                footerEl.konvaNode.x(newLogoX);
            }
            if (footerEl.konvaNode) {
                footerEl.konvaNode.y(footerY + (footerHeight - logoHeight) / 2);
            }
        }
    });
    
    if (layer) {
        layer.draw();
    }
};

// Add default footer if it doesn't exist
const addDefaultFooterIfNeeded = async () => {
    // Vérifier si un footer existe déjà (rechercher un élément avec id contenant 'footer')
    const hasFooter = elements.value.some(el => el.id && el.id.includes('footer'));
    
    if (hasFooter) {
        // Si le footer existe, recalculer sa position au cas où les dimensions ont changé
        recalculateFooterPosition();
        return;
    }
    
    // Dimensions du footer
    const footerHeight = 60;
    const footerY = canvasHeight.value - footerHeight;
    
    // Créer le rectangle bleu clair du footer
    const footerRect: LayoutElement = {
        id: 'footer-bg-' + Date.now(),
        type: 'rect',
        x: 0,
        y: footerY,
        width: canvasWidth.value,
        height: footerHeight,
        fill: '#E3F2FD', // Bleu clair
        stroke: undefined,
        strokeWidth: 0,
        opacity: 1,
    };
    
    // Créer le texte du footer (centré)
    const footerText: LayoutElement = {
        id: 'footer-text-' + Date.now(),
        type: 'text',
        x: canvasWidth.value / 2,
        y: footerY + footerHeight / 2,
        text: 'Fitnessclic.com création de séances personnalisées en quelques clics.',
        fontSize: 12,
        fontFamily: 'Arial',
        fill: '#000000',
    };
    
    // Créer l'image du logo (charger d'abord pour obtenir les dimensions réelles)
    const logoImageUrl = '/assets/logo_fitnessclic.png';
    
    // Charger l'image pour obtenir ses dimensions réelles
    const logoImg = new Image();
    logoImg.src = logoImageUrl;
    
    await new Promise<void>((resolve) => {
        logoImg.onload = () => {
            // Calculer les dimensions en respectant le ratio d'aspect
            const maxHeight = footerHeight - 10; // Laisser un peu de marge
            const maxWidth = 100;
            
            const aspectRatio = logoImg.width / logoImg.height;
            let logoWidth = maxWidth;
            let logoHeight = logoWidth / aspectRatio;
            
            // Si la hauteur dépasse, ajuster
            if (logoHeight > maxHeight) {
                logoHeight = maxHeight;
                logoWidth = logoHeight * aspectRatio;
            }
            
            // Position initiale du logo (sera ajustée après le chargement du texte)
            // Estimation : le texte fait environ 400px de large, donc on place le logo à droite du texte
            const estimatedTextWidth = 400;
            const textX = canvasWidth.value / 2; // Le texte est centré
            const textRight = textX + estimatedTextWidth / 2;
            const logoX = textRight + 15; // 15px d'espacement après le texte
            
            const logoImage: LayoutElement = {
                id: 'footer-logo-' + Date.now(),
                type: 'image',
                x: logoX,
                y: footerY + (footerHeight - logoHeight) / 2, // Centré verticalement
                width: logoWidth,
                height: logoHeight,
                imageUrl: logoImageUrl,
            };
            
            // Ajouter les éléments au canvas
            elements.value.push(footerRect);
            elements.value.push(footerText);
            elements.value.push(logoImage);
            
            // Créer les éléments Konva
            addShapeToCanvas(footerRect);
            addTextToCanvas(footerText);
            addImageToCanvas(logoImage).then(() => {
                // Ajuster la position du logo après que le texte soit chargé
                if (footerText.konvaNode && logoImage.konvaNode) {
                    const textWidth = footerText.konvaNode.width();
                    const textX = canvasWidth.value / 2;
                    const textRight = textX + textWidth / 2;
                    const newLogoX = textRight + 15; // 15px d'espacement après le texte
                    
                    // Mettre à jour la position
                    logoImage.x = newLogoX;
                    logoImage.konvaNode.x(newLogoX);
                    if (layer) {
                        layer.draw();
                    }
                }
            }).then(() => {
                // Rendre le footer non déplaçable
                if (footerRect.konvaNode) {
                    footerRect.konvaNode.draggable(false);
                }
                if (footerText.konvaNode) {
                    footerText.konvaNode.draggable(false);
                    // Centrer le texte
                    footerText.konvaNode.offsetX(footerText.konvaNode.width() / 2);
                    footerText.konvaNode.offsetY(footerText.konvaNode.height() / 2);
                }
                if (logoImage.konvaNode) {
                    logoImage.konvaNode.draggable(false);
                }
                resolve();
            });
        };
        logoImg.onerror = () => {
            // Si l'image ne charge pas, continuer sans logo
            elements.value.push(footerRect);
            elements.value.push(footerText);
            addShapeToCanvas(footerRect);
            addTextToCanvas(footerText);
            if (footerRect.konvaNode) {
                footerRect.konvaNode.draggable(false);
            }
            if (footerText.konvaNode) {
                footerText.konvaNode.draggable(false);
                footerText.konvaNode.offsetX(footerText.konvaNode.width() / 2);
                footerText.konvaNode.offsetY(footerText.konvaNode.height() / 2);
            }
            resolve();
        };
    });
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
                
                // Obtenir les dimensions naturelles de l'image
                const naturalWidth = imageObj.width || 200;
                const naturalHeight = imageObj.height || 200;
                const aspectRatio = naturalWidth / naturalHeight;
                
                // Si des dimensions sont spécifiées dans l'élément, les utiliser
                // Sinon, utiliser les dimensions naturelles
                let width = element.width;
                let height = element.height;
                
                // Si les deux dimensions sont spécifiées, vérifier qu'elles respectent le ratio
                if (width && height) {
                    const specifiedRatio = width / height;
                    // Si le ratio est différent, ajuster pour respecter le ratio d'origine
                    if (Math.abs(specifiedRatio - aspectRatio) > 0.01) {
                        // Ajuster la hauteur pour respecter le ratio
                        height = width / aspectRatio;
                    }
                } else if (width) {
                    // Si seule la largeur est spécifiée, calculer la hauteur
                    height = width / aspectRatio;
                } else if (height) {
                    // Si seule la hauteur est spécifiée, calculer la largeur
                    width = height * aspectRatio;
                } else {
                    // Si aucune dimension n'est spécifiée, utiliser les dimensions naturelles
                    width = naturalWidth;
                    height = naturalHeight;
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
                let x = element.x ?? (canvasWidth.value / 2);
                let y = element.y ?? (canvasHeight.value / 2);
                
                // Si la position est en dehors, placer au centre
                if (x < 0 || x > canvasWidth.value || y < 0 || y > canvasHeight.value) {
                    x = (canvasWidth.value - width) / 2;
                    y = (canvasHeight.value - height) / 2;
                }
                
                // Mettre à jour la position dans l'élément
                element.x = x;
                element.y = y;
                
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

                konvaImage.on('click', (e) => {
                    e.cancelBubble = true;
                    selectElement(element.id, konvaImage);
                });

                // S'assurer que l'image est bien draggable
                konvaImage.draggable(true);
                
                // Vérifier que la position est correcte avant d'ajouter
                console.log('Before adding to layer:', {
                    elementId: element.id,
                    elementPosition: { x: element.x, y: element.y },
                    konvaPosition: { x: konvaImage.x(), y: konvaImage.y() },
                    calculatedPosition: { x, y }
                });
                
                // S'assurer que la position de Konva correspond à celle de l'élément
                konvaImage.x(x);
                konvaImage.y(y);
                
                layer.add(konvaImage);
                element.konvaNode = konvaImage;
                
                // Mettre à jour les dimensions dans l'élément
                element.width = width;
                element.height = height;
                element.x = x;
                element.y = y;
                
                // Vérifier après ajout
                console.log('After adding to layer:', {
                    elementId: element.id,
                    elementPosition: { x: element.x, y: element.y },
                    konvaPosition: { x: konvaImage.x(), y: konvaImage.y() },
                    draggable: konvaImage.draggable(),
                    inLayer: layer.findOne(`#${element.id}`) !== undefined
                });
                
                // Forcer le layer à se mettre à jour
                layer.batchDraw();
                
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

// Save current state to history
const saveToHistory = () => {
    // Remove any future history if we're not at the end
    if (historyIndex.value < history.value.length - 1) {
        history.value = history.value.slice(0, historyIndex.value + 1);
    }
    
    // Create a deep copy of current elements (without konvaNode)
    const snapshot = elements.value.map(el => {
        const { konvaNode, ...elementWithoutNode } = el;
        return JSON.parse(JSON.stringify(elementWithoutNode));
    });
    
    history.value.push(snapshot);
    historyIndex.value = history.value.length - 1;
    
    // Limit history size
    if (history.value.length > maxHistorySize) {
        history.value.shift();
        historyIndex.value = history.value.length - 1;
    }
};

// Undo last action
const undo = async () => {
    if (historyIndex.value <= 0 || !layer) return;
    
    historyIndex.value--;
    const previousState = history.value[historyIndex.value];
    
    // Clear current canvas
    elements.value.forEach(el => {
        if (el.konvaNode) {
            el.konvaNode.destroy();
        }
    });
    elements.value = [];
    
    // Restore previous state
    if (previousState) {
        elements.value = JSON.parse(JSON.stringify(previousState));
        await loadElementsToCanvas();
    }
    
    if (transformer) {
        transformer.nodes([]);
    }
    selectedElementId.value = null;
    
    if (layer) {
        layer.draw();
    }
};

// Check if undo is available
const canUndo = computed(() => historyIndex.value > 0);

// Update element position
const updateElementPosition = (id: string, x: number, y: number) => {
    const element = elements.value.find(el => el.id === id);
    if (element) {
        element.x = x;
        element.y = y;
        
        // Pour les ellipses dans un groupe, mettre à jour la position du groupe
        if (element.type === 'ellipse' && element.konvaNode && (element.konvaNode as any)._ellipseShape) {
            const ellipseShape = (element.konvaNode as any)._ellipseShape;
            const radiusX = ellipseShape.radiusX();
            const radiusY = ellipseShape.radiusY();
            // Le groupe doit être positionné au coin supérieur gauche
            element.konvaNode.x(x - radiusX);
            element.konvaNode.y(y - radiusY);
        }
    }
};

// Track if we're currently transforming (to avoid saving history on every mousemove)
let isTransforming = false;
let transformStartState: LayoutElement[] | null = null;

// Update element transform
const updateElementTransform = (id: string, node: any) => {
    const element = elements.value.find(el => el.id === id);
    if (element && node) {
        // Save history only at the start of transformation
        if (!isTransforming) {
            isTransforming = true;
            transformStartState = JSON.parse(JSON.stringify(elements.value.map(el => {
                const { konvaNode, ...elWithoutNode } = el;
                return elWithoutNode;
            })));
        }
        
        const newX = node.x();
        const newY = node.y();
        const newRotation = node.rotation();
        
        element.x = newX;
        element.y = newY;
        element.rotation = newRotation;
        
        if (element.type === 'ellipse') {
            // Pour les ellipses, le node est un Group qui contient l'ellipse
            const ellipseShape = (node as any)._ellipseShape;
            if (ellipseShape) {
                const currentRadiusX = ellipseShape.radiusX();
                const currentRadiusY = ellipseShape.radiusY();
                const scaleX = node.scaleX();
                const scaleY = node.scaleY();
                
                const newRadiusX = currentRadiusX * scaleX;
                const newRadiusY = currentRadiusY * scaleY;
                
                ellipseShape.radiusX(newRadiusX);
                ellipseShape.radiusY(newRadiusY);
                // Mettre à jour la position de l'ellipse dans le groupe
                ellipseShape.x(newRadiusX);
                ellipseShape.y(newRadiusY);
                
                node.scaleX(1);
                node.scaleY(1);
                
                element.radiusX = newRadiusX;
                element.radiusY = newRadiusY;
                // Mettre à jour la position de l'élément (centre du groupe)
                element.x = node.x() + newRadiusX;
                element.y = node.y() + newRadiusY;
            }
        } else if (element.type === 'line' || element.type === 'arrow') {
            if (node.points) {
                const points = node.points();
                const scaleX = node.scaleX();
                const scaleY = node.scaleY();
                const transformedPoints: number[] = [];
                for (let i = 0; i < points.length; i += 2) {
                    transformedPoints.push(points[i] * scaleX);
                    transformedPoints.push(points[i + 1] * scaleY);
                }
                node.points(transformedPoints);
                node.scaleX(1);
                node.scaleY(1);
                element.points = transformedPoints;
            }
        } else {
            const newWidth = node.width() * node.scaleX();
            const newHeight = node.height() * node.scaleY();
            node.width(newWidth);
            node.height(newHeight);
            node.scaleX(1);
            node.scaleY(1);
            element.width = newWidth;
            element.height = newHeight;
        }
        
        if (layer) {
            layer.draw();
        }
        
        // Save history after transformation ends
        if (isTransforming && transformStartState) {
            isTransforming = false;
            // Only save if something actually changed
            const currentState = JSON.stringify(elements.value.map(el => {
                const { konvaNode, ...elWithoutNode } = el;
                return elWithoutNode;
            }));
            if (JSON.stringify(transformStartState) !== currentState) {
                saveToHistory();
            }
            transformStartState = null;
        }
    }
};

// Select element
const selectElement = (id: string, node: any) => {
    if (!transformer || !node) return;
    
    // Si l'élément est déjà sélectionné, le désélectionner
    if (selectedElementId.value === id) {
        transformer.nodes([]);
        selectedElementId.value = null;
        if (layer) {
            layer.draw();
        }
        return;
    }
    
    // Sinon, sélectionner l'élément
    selectedElementId.value = id;
    
    // S'assurer que le nœud est bien attaché au transformer
    transformer.nodes([node]);
    
    // Pour les ellipses dans un groupe, le transformer devrait fonctionner normalement
    // Forcer le transformer à se mettre à jour
    transformer.forceUpdate();
    
    // Redessiner le layer
    if (layer) {
        layer.draw();
    }
    
    const element = elements.value.find(el => el.id === id);
    console.log('Element selected:', {
        id,
        elementType: element?.type,
        nodeSize: { width: node.width(), height: node.height() },
        nodePosition: { x: node.x(), y: node.y() },
        selectedElement: selectedElement.value
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
        // S'assurer que les coordonnées sont valides
        const finalX = x !== undefined && x >= 0 ? x : (canvasWidth.value / 2);
        const finalY = y !== undefined && y >= 0 ? y : (canvasHeight.value / 2);
        
        const element: LayoutElement = {
            id: `img-${Date.now()}`,
            type: 'image',
            x: finalX,
            y: finalY,
            imageUrl,
            exerciseId: exercise.id,
        };

        console.log('Adding element with position:', { x: finalX, y: finalY, canvasWidth: canvasWidth.value, canvasHeight: canvasHeight.value });
        
        saveToHistory();
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

// Start drawing a shape
const startDrawingShape = (type: 'rect' | 'ellipse' | 'line' | 'arrow' | 'highlight') => {
    if (!stage || !layer) return;
    
    // Clean up previous drawing mode
    if (tempShapeRef.value) {
        tempShapeRef.value.destroy();
        tempShapeRef.value = null;
    }
    
    // Remove previous event handlers
    if (currentDrawingHandlers.mousedown) {
        stage.off('mousedown', currentDrawingHandlers.mousedown);
    }
    if (currentDrawingHandlers.mousemove) {
        stage.off('mousemove', currentDrawingHandlers.mousemove);
    }
    if (currentDrawingHandlers.mouseup) {
        stage.off('mouseup', currentDrawingHandlers.mouseup);
    }
    
    // Reset handlers object
    currentDrawingHandlers = {};
    
    // Reset drawing state
    isDrawingShape.value = false;
    drawingShapeType.value = null;
    shapeStartPos.value = null;
    isMouseDown.value = false;
    
    // Set new drawing mode
    drawingShapeType.value = type;
    isDrawingShape.value = true;
    
    const handleStageMouseDown = (e: any) => {
        if (!isDrawingShape.value || drawingShapeType.value !== type) return;
        if (e.target !== stage && e.target !== layer) return;
        
        const pointerPos = stage.getPointerPosition();
        if (!pointerPos) return;
        
        isMouseDown.value = true;
        shapeStartPos.value = { x: pointerPos.x, y: pointerPos.y };
        e.cancelBubble = true;
    };
    
    const handleStageMouseMove = (e: any) => {
        if (!isDrawingShape.value || !shapeStartPos.value || !isMouseDown.value) return;
        
        const pointerPos = stage.getPointerPosition();
        if (!pointerPos) return;
        
        const width = pointerPos.x - shapeStartPos.value.x;
        const height = pointerPos.y - shapeStartPos.value.y;
        
        if (tempShapeRef.value) {
            tempShapeRef.value.destroy();
        }
        
        if (type === 'rect' || type === 'highlight') {
            const tempRect = new Konva.Rect({
                x: Math.min(shapeStartPos.value.x, pointerPos.x),
                y: Math.min(shapeStartPos.value.y, pointerPos.y),
                width: Math.abs(width),
                height: Math.abs(height),
                fill: type === 'highlight' ? '#FFFF00' : undefined,
                opacity: type === 'highlight' ? 0.3 : shapeOpacity.value,
                stroke: type === 'highlight' ? undefined : shapeStrokeColor.value,
                strokeWidth: shapeStrokeWidth.value,
            });
            tempRect.name('temp-shape');
            layer.add(tempRect);
            tempShapeRef.value = tempRect;
        } else if (type === 'ellipse') {
            const tempEllipse = new Konva.Ellipse({
                x: shapeStartPos.value.x + width / 2,
                y: shapeStartPos.value.y + height / 2,
                radiusX: Math.abs(width) / 2,
                radiusY: Math.abs(height) / 2,
                fill: undefined,
                opacity: shapeOpacity.value,
                stroke: shapeStrokeColor.value,
                strokeWidth: shapeStrokeWidth.value,
            });
            tempEllipse.name('temp-shape');
            layer.add(tempEllipse);
            tempShapeRef.value = tempEllipse;
        } else if (type === 'line' || type === 'arrow') {
            const points = [shapeStartPos.value.x, shapeStartPos.value.y, pointerPos.x, pointerPos.y];
            if (type === 'arrow') {
                const tempArrow = new Konva.Arrow({
                    points,
                    stroke: shapeStrokeColor.value,
                    strokeWidth: shapeStrokeWidth.value,
                    fill: shapeStrokeColor.value,
                    opacity: shapeOpacity.value,
                    pointerLength: 10,
                    pointerWidth: 10,
                });
                tempArrow.name('temp-shape');
                layer.add(tempArrow);
                tempShapeRef.value = tempArrow;
            } else {
                const tempLine = new Konva.Line({
                    points,
                    stroke: shapeStrokeColor.value,
                    strokeWidth: shapeStrokeWidth.value,
                    opacity: shapeOpacity.value,
                    lineCap: 'round',
                    lineJoin: 'round',
                });
                tempLine.name('temp-shape');
                layer.add(tempLine);
                tempShapeRef.value = tempLine;
            }
        }
        
        layer.draw();
    };
    
    const handleStageMouseUp = (e: any) => {
        if (!isDrawingShape.value || !shapeStartPos.value || !isMouseDown.value) return;
        
        isMouseDown.value = false;
        const pointerPos = stage.getPointerPosition();
        if (!pointerPos) return;
        
        if (tempShapeRef.value) {
            tempShapeRef.value.destroy();
            tempShapeRef.value = null;
        }
        
        const width = pointerPos.x - shapeStartPos.value.x;
        const height = pointerPos.y - shapeStartPos.value.y;
        
        if (Math.abs(width) < 5 || Math.abs(height) < 5) {
            shapeStartPos.value = null;
            layer.draw();
            return;
        }
        
        let element: LayoutElement;
        
        if (type === 'rect' || type === 'highlight') {
            element = {
                id: `${type}-${Date.now()}`,
                type: type,
                x: Math.min(shapeStartPos.value.x, pointerPos.x),
                y: Math.min(shapeStartPos.value.y, pointerPos.y),
                width: Math.abs(width),
                height: Math.abs(height),
                fill: type === 'highlight' ? '#FFFF00' : undefined,
                stroke: type === 'highlight' ? undefined : shapeStrokeColor.value,
                strokeWidth: shapeStrokeWidth.value,
                opacity: type === 'highlight' ? 0.3 : shapeOpacity.value,
            };
        } else if (type === 'ellipse') {
            element = {
                id: `ellipse-${Date.now()}`,
                type: 'ellipse',
                x: shapeStartPos.value.x + width / 2,
                y: shapeStartPos.value.y + height / 2,
                radiusX: Math.abs(width) / 2,
                radiusY: Math.abs(height) / 2,
                stroke: shapeStrokeColor.value,
                strokeWidth: shapeStrokeWidth.value,
                opacity: shapeOpacity.value,
            };
        } else {
            element = {
                id: `${type}-${Date.now()}`,
                type: type,
                x: shapeStartPos.value.x,
                y: shapeStartPos.value.y,
                points: [shapeStartPos.value.x, shapeStartPos.value.y, pointerPos.x, pointerPos.y],
                stroke: shapeStrokeColor.value,
                strokeWidth: shapeStrokeWidth.value,
                fill: type === 'arrow' ? shapeStrokeColor.value : undefined,
                opacity: shapeOpacity.value,
                pointerLength: type === 'arrow' ? 10 : undefined,
                pointerWidth: type === 'arrow' ? 10 : undefined,
            };
        }
        
        saveToHistory();
        elements.value.push(element);
        addShapeToCanvas(element);
        shapeStartPos.value = null;
        // Ne pas désactiver le mode de dessin pour permettre de continuer à dessiner
        // Le mode reste actif jusqu'à ce que l'utilisateur clique sur un autre bouton ou désélectionne
        
        // Sélectionner automatiquement la forme créée
        nextTick(() => {
            const createdElement = elements.value.find(el => el.id === element.id);
            if (createdElement && createdElement.konvaNode) {
                // Pour les ellipses, attendre plus longtemps et forcer plusieurs mises à jour
                if (createdElement.type === 'ellipse') {
                    setTimeout(() => {
                        if (createdElement.konvaNode && transformer && layer) {
                            selectedElementId.value = createdElement.id;
                            transformer.nodes([createdElement.konvaNode]);
                            transformer.forceUpdate();
                            layer.draw();
                            // Forcer une deuxième mise à jour
                            setTimeout(() => {
                                if (transformer && layer) {
                                    transformer.forceUpdate();
                                    layer.draw();
                                }
                            }, 100);
                        }
                    }, 100);
                } else {
                    selectElement(createdElement.id, createdElement.konvaNode);
                }
            }
        });
        
        // Ne pas nettoyer les événements pour permettre de continuer à dessiner
        // Les événements seront nettoyés quand l'utilisateur changera de mode de dessin
    };
    
    // Store handlers for cleanup
    currentDrawingHandlers.mousedown = handleStageMouseDown;
    currentDrawingHandlers.mousemove = handleStageMouseMove;
    currentDrawingHandlers.mouseup = handleStageMouseUp;
    
    // Add event listeners
    stage.on('mousedown', handleStageMouseDown);
    stage.on('mousemove', handleStageMouseMove);
    stage.on('mouseup', handleStageMouseUp);
};

// Add shape to canvas
const addShapeToCanvas = (element: LayoutElement) => {
    if (!layer) return;
    
    let konvaShape: any = null;
    
    switch (element.type) {
        case 'rect':
        case 'highlight':
            konvaShape = new Konva.Rect({
                x: element.x,
                y: element.y,
                width: element.width || 100,
                height: element.height || 100,
                fill: element.fill || undefined,
                stroke: element.stroke !== undefined ? element.stroke : shapeStrokeColor.value,
                strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : shapeStrokeWidth.value,
                opacity: element.opacity !== undefined ? element.opacity : shapeOpacity.value,
                draggable: true,
                rotation: element.rotation || 0,
            });
            break;
        case 'ellipse':
            const radiusX = element.radiusX !== undefined ? element.radiusX : 50;
            const radiusY = element.radiusY !== undefined ? element.radiusY : 50;
            
            // Créer un groupe pour envelopper l'ellipse et faciliter le calcul du transformer
            const ellipseGroup = new Konva.Group({
                x: element.x - radiusX,
                y: element.y - radiusY,
                draggable: true,
                rotation: element.rotation || 0,
            });
            
            // Créer l'ellipse à l'intérieur du groupe, positionnée au centre du groupe
            const ellipseShape = new Konva.Ellipse({
                x: radiusX,
                y: radiusY,
                radiusX: radiusX,
                radiusY: radiusY,
                fill: element.fill || undefined,
                stroke: element.stroke !== undefined ? element.stroke : shapeStrokeColor.value,
                strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : shapeStrokeWidth.value,
                opacity: element.opacity !== undefined ? element.opacity : shapeOpacity.value,
            });
            
            ellipseGroup.add(ellipseShape);
            konvaShape = ellipseGroup;
            
            // Stocker une référence à l'ellipse interne pour les mises à jour
            (konvaShape as any)._ellipseShape = ellipseShape;
            break;
        case 'line':
            if (element.points && element.points.length >= 4) {
                konvaShape = new Konva.Line({
                    points: element.points,
                    stroke: element.stroke || shapeStrokeColor.value,
                    strokeWidth: element.strokeWidth || shapeStrokeWidth.value,
                    opacity: element.opacity !== undefined ? element.opacity : shapeOpacity.value,
                    draggable: true,
                    lineCap: 'round',
                    lineJoin: 'round',
                });
            }
            break;
        case 'arrow':
            if (element.points && element.points.length >= 4) {
                konvaShape = new Konva.Arrow({
                    points: element.points,
                    pointerLength: element.pointerLength || 10,
                    pointerWidth: element.pointerWidth || 10,
                    fill: element.fill || shapeStrokeColor.value,
                    stroke: element.stroke || shapeStrokeColor.value,
                    strokeWidth: element.strokeWidth || shapeStrokeWidth.value,
                    opacity: element.opacity !== undefined ? element.opacity : shapeOpacity.value,
                    draggable: true,
                });
            }
            break;
    }
    
    if (konvaShape) {
        konvaShape.id(element.id);
        
        konvaShape.on('dragend', () => {
            // Pour les ellipses dans un groupe, calculer la position du centre
            if (element.type === 'ellipse' && (konvaShape as any)._ellipseShape) {
                const ellipseShape = (konvaShape as any)._ellipseShape;
                const radiusX = ellipseShape.radiusX();
                const radiusY = ellipseShape.radiusY();
                const centerX = konvaShape.x() + radiusX;
                const centerY = konvaShape.y() + radiusY;
                updateElementPosition(element.id, centerX, centerY);
            } else {
                updateElementPosition(element.id, konvaShape.x(), konvaShape.y());
            }
        });
        
        konvaShape.on('transformend', () => {
            updateElementTransform(element.id, konvaShape);
        });
        
        konvaShape.on('click', () => {
            selectElement(element.id, konvaShape);
        });
        
        layer.add(konvaShape);
        element.konvaNode = konvaShape;
        layer.draw();
    }
};

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
        saveToHistory();
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
        saveToHistory();
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

// Get selected element
const selectedElement = computed(() => {
    if (!selectedElementId.value) return null;
    return elements.value.find(el => el.id === selectedElementId.value) || null;
});

// Update selected element properties
const updateSelectedElementProperty = (property: string, value: any) => {
    if (!selectedElement.value || !selectedElement.value.konvaNode || !layer) return;
    
    saveToHistory();
    
    const element = selectedElement.value;
    const node = element.konvaNode;
    
    // Update element data
    (element as any)[property] = value;
    
    // Update Konva node
    if (property === 'stroke') {
        node.stroke(value || undefined);
    } else if (property === 'strokeWidth') {
        node.strokeWidth(value);
    } else if (property === 'opacity') {
        node.opacity(value);
    } else if (property === 'fill') {
        node.fill(value || undefined);
    }
    
    layer.draw();
};

// Delete selected element
const deleteSelected = () => {
    if (!selectedElementId.value || !layer) return;

    saveToHistory();
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
// Handle back button
const handleBack = () => {
    // Si on a un sessionId, rediriger vers la page Show
    if (props.sessionId) {
        router.visit(`/sessions/${props.sessionId}`);
    } else {
        // Sinon, émettre l'événement close
        emit('close');
    }
};

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
            stroke: el.stroke,
            strokeWidth: el.strokeWidth,
            opacity: el.opacity,
            points: el.points,
            radiusX: el.radiusX,
            radiusY: el.radiusY,
            pointerLength: el.pointerLength,
            pointerWidth: el.pointerWidth,
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
        notifySuccess('Génération du PDF en cours...');
        
        // S'assurer que le footer est bien en bas avant l'export
        // Utiliser la fonction de recalcul du footer
        recalculateFooterPosition();
        
        // Utiliser les dimensions réelles du canvas (sans le scale d'affichage)
        // Les éléments sont positionnés selon ces dimensions, pas selon les dimensions du stage
        const realCanvasWidth = canvasWidth.value;
        const realCanvasHeight = canvasHeight.value;
        
        // Temporairement ajuster le stage pour qu'il corresponde aux dimensions réelles
        // Cela garantit que toDataURL capture l'image avec les bonnes dimensions
        const originalStageWidth = stage.width();
        const originalStageHeight = stage.height();
        stage.width(realCanvasWidth);
        stage.height(realCanvasHeight);
        
        // Redessiner le layer avec les nouvelles dimensions
        if (layer) {
            layer.draw();
        }
        
        // Convert canvas to image with high quality
        const dataURL = stage.toDataURL({ 
            pixelRatio: 2,
            mimeType: 'image/png',
            quality: 1
        });
        
        // Restaurer les dimensions originales du stage
        stage.width(originalStageWidth);
        stage.height(originalStageHeight);
        if (layer) {
            layer.draw();
        }
        
        // Load jsPDF from CDN
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
        document.head.appendChild(script);
        
        await new Promise((resolve, reject) => {
            script.onload = resolve;
            script.onerror = reject;
        });
        
        // @ts-ignore - jsPDF is loaded from CDN
        const { jsPDF } = window.jspdf;
        
        // Convertir les dimensions du canvas en mm (1px = 0.264583mm à 96 DPI)
        const pxToMm = 0.264583;
        const pdfWidth = realCanvasWidth * pxToMm;
        const pdfHeight = realCanvasHeight * pxToMm;
        
        // Create PDF with exact canvas dimensions (in mm)
        const pdf = new jsPDF({
            orientation: pdfHeight > pdfWidth ? 'portrait' : 'landscape',
            unit: 'mm',
            format: [pdfWidth, pdfHeight] // Dimensions personnalisées correspondant exactement au canvas
        });
        
        // Les dimensions de l'image correspondent exactement au PDF
        const imgWidth = pdfWidth;
        const imgHeight = pdfHeight;
        
        // Positionner l'image à (0, 0) pour qu'elle remplisse toute la page
        const x = 0;
        const y = 0;
        
        // L'image correspond exactement au PDF, pas besoin de redimensionner
        pdf.addImage(dataURL, 'PNG', x, y, imgWidth, imgHeight);
        
        // Generate filename
        const name = sessionName.value || 'mise-en-page';
        const fileName = `${name.replace(/[^a-z0-9]/gi, '-').toLowerCase()}-${Date.now()}.pdf`;
        
        // Download the PDF
        pdf.save(fileName);
        
        // Remove script tag
        document.head.removeChild(script);
        
        notifySuccess('PDF téléchargé avec succès');
    } catch (error: any) {
        console.error('Error exporting PDF:', error);
        notifyError('Erreur lors de l\'export PDF: ' + (error.message || 'Erreur inconnue'));
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
    <AppLayout>
        <div class="flex flex-col h-full">
                    <!-- Toolbar -->
                    <div class="flex items-center justify-between p-4 border-b bg-white dark:bg-neutral-900">
            <div class="flex items-center gap-2">
                <Button 
                    variant="ghost" 
                    size="sm" 
                    @click="handleBack"
                >
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Retour
                </Button>
                <h2 class="text-lg font-semibold">Éditeur de mise en page</h2>
            </div>
            <!-- Boutons de forme au centre -->
            <div class="flex items-center gap-2">
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="undo"
                    :disabled="!canUndo"
                    title="Annuler (Ctrl+Z)"
                >
                    <RotateCcw class="h-4 w-4 mr-2" />
                    Annuler
                </Button>
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="startDrawingShape('arrow')"
                    :class="{ 'bg-blue-100 dark:bg-blue-900': drawingShapeType === 'arrow' }"
                >
                    <ArrowRight class="h-4 w-4 mr-2" />
                    Flèche
                </Button>
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="startDrawingShape('rect')"
                    :class="{ 'bg-blue-100 dark:bg-blue-900': drawingShapeType === 'rect' }"
                >
                    <Square class="h-4 w-4 mr-2" />
                    Carré
                </Button>
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="startDrawingShape('ellipse')"
                    :class="{ 'bg-blue-100 dark:bg-blue-900': drawingShapeType === 'ellipse' }"
                >
                    <Circle class="h-4 w-4 mr-2" />
                    Rond
                </Button>
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="startDrawingShape('line')"
                    :class="{ 'bg-blue-100 dark:bg-blue-900': drawingShapeType === 'line' }"
                >
                    <Minus class="h-4 w-4 mr-2" />
                    Ligne
                </Button>
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="startDrawingShape('highlight')"
                    :class="{ 'bg-blue-100 dark:bg-blue-900': drawingShapeType === 'highlight' }"
                >
                    <Highlighter class="h-4 w-4 mr-2" />
                    Surligner
                </Button>
                <Button variant="outline" size="sm" @click="addText">
                    <Type class="h-4 w-4 mr-2" />
                    Texte
                </Button>
            </div>
            
            <!-- Boutons d'action à droite -->
            <div class="flex items-center gap-2">
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
                <!-- Informations de la séance -->
                <div class="p-4 border-b flex-1 overflow-y-auto">
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
                            @click="(e) => {
                                // Empêcher le drag si c'est juste un clic
                                e.stopPropagation();
                                const centerX = canvasWidth.value / 2;
                                const centerY = canvasHeight.value / 2;
                                handleExerciseDrop(exercise, centerX, centerY);
                            }"
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
    </AppLayout>
</template>

<style scoped>
/* Styles pour le canvas Konva */
</style>


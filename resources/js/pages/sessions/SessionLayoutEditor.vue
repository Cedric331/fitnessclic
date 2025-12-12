<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
    Highlighter,
    Plus,
    Bold,
    Italic,
    Underline,
    AlignLeft,
    AlignCenter,
    AlignRight
} from 'lucide-vue-next';
import { useNotifications } from '@/composables/useNotifications';
import Konva from 'konva';
import type { Exercise } from './types';

interface ExerciseInstructionRow {
    series?: number;
    reps?: number;
    duration?: number; // en secondes
    useDuration?: boolean; // true = durée, false = répets
    recovery?: number;
    load?: number;
    useBodyweight?: boolean; // true = poids de corps, false = charge
    instructions?: string; // Déprécié - maintenant global
}

interface ExerciseInstructionsStyle {
    fontSize?: number;
    fontFamily?: string;
    fontWeight?: 'normal' | 'bold';
    fontStyle?: 'normal' | 'italic';
    fill?: string;
    backgroundColor?: string;
    stroke?: string;
    strokeWidth?: number;
    padding?: number;
    borderRadius?: number;
}

interface ExerciseData {
    title?: string;
    showTitle?: boolean;
    titlePosition?: 'above' | 'below'; // Position du titre par rapport à l'image
    instructions?: string; // Consignes globales
    showInstructions?: boolean;
    instructionsPosition?: 'above' | 'below'; // Position des consignes par rapport à l'image
    instructionsStyle?: ExerciseInstructionsStyle;
    rows: ExerciseInstructionRow[];
}

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
    exerciseData?: ExerciseData;
    text?: string;
    fontSize?: number;
    fontFamily?: string;
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    opacity?: number;
    fontWeight?: 'normal' | 'bold';
    fontStyle?: 'normal' | 'italic';
    textDecoration?: 'none' | 'underline';
    align?: 'left' | 'center' | 'right';
    points?: number[];
    radiusX?: number;
    radiusY?: number;
    pointerLength?: number;
    pointerWidth?: number;
    konvaNode?: any;
    buttonNode?: any;
    deleteButtonNode?: any;
    tableGroup?: any;
    titleNode?: any;
    textFrameNode?: any; // Rectangle autour du texte pour le cadre
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
const textFontFamily = ref('Arial');
const textFontWeight = ref<'normal' | 'bold'>('normal');
const textFontStyle = ref<'normal' | 'italic'>('normal');
const textDecoration = ref<'none' | 'underline'>('none');
const textAlign = ref<'left' | 'center' | 'right'>('left');
const textStroke = ref(false);
const textStrokeColor = ref('#000000');
const textStrokeWidth = ref(1);
const isSaving = ref(false);
const showExerciseLibrary = ref(true);
const exerciseSearchTerm = ref('');
const draggingExerciseId = ref<number | null>(null);
const showExerciseInstructionsModal = ref(false);
const editingExerciseElement = ref<LayoutElement | null>(null);
const exerciseData = ref<ExerciseData>({
    title: '',
    showTitle: true,
    titlePosition: 'above',
    instructions: '',
    rows: [{ series: 1, reps: 20, recovery: 30, load: 10, useDuration: false, useBodyweight: false }]
});

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
const loadElementsToCanvas = async (addFooter: boolean = true) => {
    if (!layer) {
        console.warn('Layer not initialized');
        return;
    }

    for (const element of elements.value) {
        try {
            if (element.type === 'image' && element.imageUrl) {
                await addImageToCanvas(element);
                // Si l'élément a des données d'exercice, créer le tableau et le titre après le chargement
                if (element.exerciseData) {
                    nextTick(() => {
                        createExerciseTable(element);
                        createExerciseTitle(element);
                    });
                }
            } else if (element.type === 'text' && element.text) {
                addTextToCanvas(element);
            } else if (['rect', 'ellipse', 'line', 'arrow', 'highlight'].includes(element.type)) {
                addShapeToCanvas(element);
            }
        } catch (error) {
            console.error('Error loading element:', error, element);
        }
    }
    
    // Ajouter le footer par défaut si aucun footer n'existe (seulement si demandé)
    if (addFooter) {
        await addDefaultFooterIfNeeded();
    }
    
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
    // Vérifier aussi si les éléments du footer ont déjà des konvaNodes (déjà chargés)
    const footerElements = elements.value.filter(el => el.id && el.id.includes('footer'));
    const hasFooter = footerElements.length > 0;
    const footerAlreadyLoaded = footerElements.some(el => el.konvaNode);
    
    if (hasFooter && footerAlreadyLoaded) {
        // Si le footer existe et est déjà chargé, recalculer sa position au cas où les dimensions ont changé
        recalculateFooterPosition();
        return;
    }
    
    if (hasFooter && !footerAlreadyLoaded) {
        // Si le footer existe dans elements mais n'est pas encore chargé, ne pas le recréer
        // Il sera chargé par loadElementsToCanvas
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
                
                // Créer un groupe pour l'image et le bouton (si c'est un exercice)
                const imageGroup = new Konva.Group({
                    x: x,
                    y: y,
                    draggable: true,
                    id: element.id,
                });

                const konvaImage = new Konva.Image({
                    x: 0,
                    y: 0,
                    image: imageObj,
                    width: width,
                    height: height,
                    rotation: element.rotation || 0,
                });

                imageGroup.add(konvaImage);

                // Si c'est une image d'exercice, ajouter les boutons
                if (element.exerciseId) {
                    // Bouton "+ Consignes" en bas à droite
                    const instructionsButtonText = '+ Consignes';
                    const instructionsButtonPadding = 8;
                    const instructionsButtonFontSize = 12;
                    
                    // Créer un texte temporaire pour mesurer la largeur
                    const tempText = new Konva.Text({
                        text: instructionsButtonText,
                        fontSize: instructionsButtonFontSize,
                        fontFamily: 'Arial',
                    });
                    const textWidth = tempText.width();
                    tempText.destroy();
                    
                    const instructionsButtonWidth = textWidth + instructionsButtonPadding * 2;
                    const instructionsButtonHeight = 28;
                    const instructionsButtonX = width - instructionsButtonWidth - 5;
                    const instructionsButtonY = height - instructionsButtonHeight - 5;

                    // Rectangle de fond du bouton "+ Consignes"
                    const instructionsButtonBg = new Konva.Rect({
                        x: instructionsButtonX,
                        y: instructionsButtonY,
                        width: instructionsButtonWidth,
                        height: instructionsButtonHeight,
                        fill: '#3B82F6',
                        stroke: '#ffffff',
                        strokeWidth: 2,
                        opacity: 0.9,
                        cornerRadius: 4,
                    });

                    // Texte "+ Consignes" sur le bouton (bien centré)
                    const instructionsButtonTextNode = new Konva.Text({
                        x: instructionsButtonX + instructionsButtonWidth / 2,
                        y: instructionsButtonY + instructionsButtonHeight / 2,
                        text: instructionsButtonText,
                        fontSize: instructionsButtonFontSize,
                        fontFamily: 'Arial',
                        fill: '#ffffff',
                        align: 'center',
                        verticalAlign: 'middle',
                        offsetX: textWidth / 2,
                        offsetY: instructionsButtonFontSize / 2.5,
                    });

                    const instructionsButtonGroup = new Konva.Group({
                        x: 0,
                        y: 0,
                    });
                    instructionsButtonGroup.add(instructionsButtonBg);
                    instructionsButtonGroup.add(instructionsButtonTextNode);

                    instructionsButtonGroup.on('click', (e) => {
                    e.cancelBubble = true;
                        openExerciseInstructionsModal(element);
                    });

                    instructionsButtonGroup.on('mouseenter', () => {
                        document.body.style.cursor = 'pointer';
                        instructionsButtonBg.fill('#2563EB');
                        layer?.draw();
                    });

                    instructionsButtonGroup.on('mouseleave', () => {
                        document.body.style.cursor = 'default';
                        instructionsButtonBg.fill('#3B82F6');
                        layer?.draw();
                    });

                    imageGroup.add(instructionsButtonGroup);
                    element.buttonNode = instructionsButtonGroup;

                    // Bouton de suppression en haut à droite
                    const deleteButtonSize = 24;
                    const deleteButtonX = width - deleteButtonSize - 5;
                    const deleteButtonY = 5;
                    const buttonCenterX = deleteButtonX + deleteButtonSize / 2;
                    const buttonCenterY = deleteButtonY + deleteButtonSize / 2;

                    // Cercle de fond du bouton de suppression
                    const deleteButtonBg = new Konva.Circle({
                        x: buttonCenterX,
                        y: buttonCenterY,
                        radius: deleteButtonSize / 2,
                        fill: '#EF4444',
                        stroke: '#ffffff',
                        strokeWidth: 2,
                        opacity: 0.9,
                    });

                    // Symbole "×" sur le bouton de suppression (bien centré)
                    const deleteButtonXIcon = new Konva.Text({
                        x: buttonCenterX,
                        y: buttonCenterY,
                        text: '×',
                        fontSize: 18,
                        fontFamily: 'Arial',
                        fill: '#ffffff',
                        align: 'center',
                        verticalAlign: 'middle',
                    });
                    
                    // Centrer le texte en calculant ses dimensions réelles
                    deleteButtonXIcon.offsetX(deleteButtonXIcon.width() / 2);
                    deleteButtonXIcon.offsetY(deleteButtonXIcon.height() / 2);

                    const deleteButtonGroup = new Konva.Group({
                        x: 0,
                        y: 0,
                    });
                    deleteButtonGroup.add(deleteButtonBg);
                    deleteButtonGroup.add(deleteButtonXIcon);

                    deleteButtonGroup.on('click', (e) => {
                        e.cancelBubble = true;
                        deleteExerciseImage(element);
                    });

                    deleteButtonGroup.on('mouseenter', () => {
                        document.body.style.cursor = 'pointer';
                        deleteButtonBg.fill('#DC2626');
                        layer?.draw();
                    });

                    deleteButtonGroup.on('mouseleave', () => {
                        document.body.style.cursor = 'default';
                        deleteButtonBg.fill('#EF4444');
                        layer?.draw();
                    });

                    imageGroup.add(deleteButtonGroup);
                    element.deleteButtonNode = deleteButtonGroup;
                }

                imageGroup.on('dragend', () => {
                    updateElementPosition(element.id, imageGroup.x(), imageGroup.y());
                    // Mettre à jour la position du tableau si présent
                    updateExerciseTablePosition(element);
                });

                imageGroup.on('transformend', () => {
                    updateElementTransform(element.id, imageGroup);
                    updateExerciseTablePosition(element);
                });

                imageGroup.on('click', (e) => {
                    // Ne pas sélectionner si on clique sur les boutons
                    if (e.target !== imageGroup && e.target.parent === imageGroup) {
                        // Vérifier si c'est le bouton "+ Consignes"
                        if (element.buttonNode && (
                            e.target === element.buttonNode || 
                            e.target.parent === element.buttonNode || 
                            e.target.parent?.parent === element.buttonNode ||
                            e.target.parent?.parent?.parent === element.buttonNode
                        )) {
                            return;
                        }
                        // Vérifier si c'est le bouton de suppression
                        if (element.deleteButtonNode && (
                            e.target === element.deleteButtonNode || 
                            e.target.parent === element.deleteButtonNode || 
                            e.target.parent?.parent === element.deleteButtonNode ||
                            e.target.parent?.parent?.parent === element.deleteButtonNode
                        )) {
                            return;
                        }
                    }
                    e.cancelBubble = true;
                    selectElement(element.id, imageGroup);
                });

                // S'assurer que le groupe est bien draggable
                imageGroup.draggable(true);
                
                // Vérifier que la position est correcte avant d'ajouter
                console.log('Before adding to layer:', {
                    elementId: element.id,
                    elementPosition: { x: element.x, y: element.y },
                    konvaPosition: { x: imageGroup.x(), y: imageGroup.y() },
                    calculatedPosition: { x, y }
                });
                
                // S'assurer que la position de Konva correspond à celle de l'élément
                imageGroup.x(x);
                imageGroup.y(y);
                
                layer.add(imageGroup);
                element.konvaNode = imageGroup;

                // Forcer le layer à se redessiner pour que le groupe calcule correctement son bounding box
                layer.draw();

                // S'assurer que le groupe calcule correctement son bounding box en incluant tous les enfants
                // Le transformer devrait automatiquement utiliser getClientRect() qui inclut tous les enfants
                // Mais on force un recalcul pour être sûr
                nextTick(() => {
                    // Obtenir le bounding box complet du groupe (incluant l'image et le bouton)
                    const box = imageGroup.getClientRect();
                    console.log('Image group bounding box:', box);
                    
                    // Forcer le transformer à recalculer le bounding box si l'élément est sélectionné
                    if (selectedElementId.value === element.id && transformer) {
                        // Détacher et réattacher le transformer pour forcer le recalcul
                        transformer.nodes([]);
                        nextTick(() => {
                            if (transformer) {
                                transformer.nodes([imageGroup]);
                                transformer.forceUpdate();
                                if (layer) {
                                    layer.draw();
                                }
                            }
                        });
                    }
                });

                // Si l'élément a déjà des données d'exercice, créer le tableau et le titre
                if (element.exerciseData) {
                    createExerciseTable(element);
                    createExerciseTitle(element);
                }
                
                // Mettre à jour les dimensions dans l'élément
                element.width = width;
                element.height = height;
                element.x = x;
                element.y = y;

                // Si c'est un exercice, initialiser les données si nécessaire
                if (element.exerciseId && !element.exerciseData) {
                    const exercise = props.exercises.find(ex => ex.id === element.exerciseId);
                    if (exercise) {
                        element.exerciseData = {
                            title: exercise.title,
                            showTitle: false,
                            titlePosition: 'above',
                            instructions: '',
                            rows: []
                        };
                    }
                }
                
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

    const fontStyle = (element.fontStyle === 'italic' ? 'italic' : '') + (element.fontWeight === 'bold' ? ' bold' : '') || 'normal';
    
    // Vérifier si c'est un élément du footer (ne pas appliquer la logique d'alignement automatique)
    const isFooterElement = element.id && element.id.includes('footer');
    
    if (isFooterElement) {
        // Pour le footer, utiliser la logique originale (centrage manuel avec offsetX)
    const konvaText = new Konva.Text({
        x: element.x,
        y: element.y,
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fill: element.fill || '#000000',
            fontStyle: fontStyle,
            textDecoration: element.textDecoration === 'underline' ? 'underline' : '',
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
        return;
    }
    
    // Calculer la largeur du texte pour l'alignement
    const tempText = new Konva.Text({
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
    });
    const textWidth = tempText.width();
    tempText.destroy();
    
    // Pour l'alignement, on utilise la largeur du canvas
    const align = element.align || 'left';
    const margin = 20; // Marge par rapport aux bords
    
    // Largeur du texte pour l'affichage (utiliser toute la largeur disponible)
    const textDisplayWidth = canvasWidth.value - (margin * 2);
    
    // Ajuster la position X selon l'alignement par rapport à la page
    // Dans Konva:
    // - align: 'left' : X est le point de départ (gauche), texte aligné à gauche
    // - align: 'center' : X est le centre de la zone, texte centré autour de X
    // - align: 'right' : X est le point de départ (gauche) de la zone, texte aligné à droite dans la zone
    let textX = element.x;
    if (align === 'center') {
        // Centrer sur la largeur du canvas
        // Avec align: 'center', le point X est le centre de la zone
        // Pour centrer la zone sur la page, le centre doit être à canvasWidth / 2
        // Mais la zone commence à X - width/2, donc on doit ajuster
        // En fait, avec align: 'center', X est le centre, donc on met X au centre du canvas
        textX = canvasWidth.value / 2;
        element.x = textX; // Sauvegarder la position centrée
    } else if (align === 'right') {
        // Aligner à droite de la page
        // Le texte doit se terminer à canvasWidth - margin
        // Donc la zone doit commencer à (canvasWidth - margin) - textDisplayWidth
        textX = (canvasWidth.value - margin) - textDisplayWidth;
        element.x = textX; // Sauvegarder la position à droite
    } else {
        // Aligner à gauche de la page
        // X est le point de départ (gauche) de la zone
        textX = margin;
        element.x = textX; // Sauvegarder la position à gauche
    }
    
    // Créer un groupe pour contenir le texte et éventuellement le rectangle de cadre
    const textGroup = new Konva.Group({
        x: 0,
        y: 0,
        draggable: true,
        id: element.id,
    });
    
    // Créer le texte
    const konvaText = new Konva.Text({
        x: textX,
        y: element.y,
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fill: element.fill || '#000000',
        fontStyle: fontStyle,
        textDecoration: element.textDecoration === 'underline' ? 'underline' : '',
        align: align,
        width: textDisplayWidth,
        draggable: false, // Le texte n'est pas draggable directement, c'est le groupe qui l'est
    });
    
    // Pour le centrage, utiliser offsetX pour que le texte soit centré autour du point X
    // Sans offsetX, la zone commence à X et s'étend jusqu'à X + width, ce qui dépasse
    if (align === 'center') {
        konvaText.offsetX(textDisplayWidth / 2);
    }
    
    textGroup.add(konvaText);
    
    // Si un cadre est demandé, créer un rectangle autour du texte avec une marge de 2px
    // On doit d'abord ajouter le texte au groupe pour pouvoir mesurer ses dimensions réelles
    if (element.stroke) {
        // Attendre que le texte soit rendu pour obtenir ses dimensions réelles
        nextTick(() => {
            if (!layer) return;
            
            // Calculer les dimensions du rectangle en fonction de la taille réelle du texte
            const textPadding = 2; // Marge de 2px entre le texte et le rectangle
            
            // Obtenir les dimensions réelles du texte rendu
            // Créer un texte temporaire pour mesurer la largeur réelle
            const tempText = new Konva.Text({
                text: konvaText.text(),
                fontSize: konvaText.fontSize(),
                fontFamily: konvaText.fontFamily(),
                fontStyle: konvaText.fontStyle(),
            });
            const textActualWidth = tempText.width();
            tempText.destroy();
            const textActualHeight = konvaText.height();
            
            // Position réelle du texte (en tenant compte de l'alignement)
            let textActualX = textX;
            if (align === 'center') {
                textActualX = textX - textActualWidth / 2;
            } else if (align === 'right') {
                textActualX = textX + textDisplayWidth - textActualWidth;
            }
            
            // Calculer la position et les dimensions du rectangle
            let rectX = textActualX;
            let rectY = element.y;
            let rectWidth = textActualWidth;
            let rectHeight = textActualHeight;
            
            // Ajuster pour la marge de 2px
            rectX -= textPadding;
            rectY -= textPadding;
            rectWidth += textPadding * 2;
            rectHeight += textPadding * 2;
            
            const frameRect = new Konva.Rect({
                x: rectX,
                y: rectY,
                width: rectWidth,
                height: rectHeight,
                fill: undefined,
                stroke: element.stroke,
                strokeWidth: element.strokeWidth || 1,
                draggable: false,
                listening: false, // Ne pas interférer avec les événements
            });
            
            textGroup.add(frameRect);
            element.textFrameNode = frameRect;
            layer.draw();
        });
    }
    
    // Sauvegarder la largeur dans l'élément
    element.width = textDisplayWidth;

    textGroup.on('dragend', () => {
        updateElementPosition(element.id, textGroup.x(), textGroup.y());
    });

    textGroup.on('transformend', () => {
        updateElementTransform(element.id, textGroup);
    });

    textGroup.on('dblclick', () => {
        editText(element);
    });

    textGroup.on('click', () => {
        selectElement(element.id, textGroup);
    });

    layer.add(textGroup);
    element.konvaNode = textGroup;
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
    if (historyIndex.value < 0 || !layer) return;
    
    // Si on est à l'index 0, on ne peut pas faire undo (on est déjà à l'état initial)
    if (historyIndex.value === 0) return;
    
    historyIndex.value--;
    const previousState = history.value[historyIndex.value];
    
    // Clear current canvas
    elements.value.forEach(el => {
        if (el.konvaNode) {
            el.konvaNode.destroy();
        }
        if (el.tableGroup) {
            el.tableGroup.destroy();
        }
        if (el.titleNode) {
            el.titleNode.destroy();
        }
    });
    elements.value = [];
    
    // Restore previous state
    if (previousState) {
        elements.value = JSON.parse(JSON.stringify(previousState));
        // Charger tous les éléments (y compris le footer s'il existe dans l'historique)
        // Ne pas ajouter de nouveau footer car il devrait déjà être dans l'état restauré
        await loadElementsToCanvas(false);
        
        // Vérifier si le footer existe dans l'état restauré
        const footerElements = elements.value.filter(el => el.id && el.id.includes('footer'));
        
        // Si le footer n'existe pas dans l'état restauré, l'ajouter (cas où on revient à un état avant l'ajout du footer)
        if (footerElements.length === 0) {
            await addDefaultFooterIfNeeded();
        }
    } else {
        // Si pas d'état précédent, charger quand même le footer
        await loadElementsToCanvas(true);
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
        
        // Mettre à jour la position du tableau si présent
        if (element.type === 'image' && element.exerciseId) {
            updateExerciseTablePosition(element);
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
        } else if (element.type === 'image') {
            // Pour les images dans un groupe, mettre à jour les dimensions de l'image interne
            const imageNode = node.findOne('Image');
            if (imageNode) {
                const newWidth = imageNode.width() * node.scaleX();
                const newHeight = imageNode.height() * node.scaleY();
                imageNode.width(newWidth);
                imageNode.height(newHeight);
                node.scaleX(1);
                node.scaleY(1);
                element.width = newWidth;
                element.height = newHeight;
            } else {
                // Fallback si pas d'image trouvée
                const newWidth = node.width() * node.scaleX();
                const newHeight = node.height() * node.scaleY();
                node.width(newWidth);
                node.height(newHeight);
                node.scaleX(1);
                node.scaleY(1);
                element.width = newWidth;
                element.height = newHeight;
            }
            // Mettre à jour la position du tableau
            updateExerciseTablePosition(element);
        } else if (element.type === 'text') {
            // Pour les textes, mettre à jour la taille de la police et recalculer le cadre
            const textNode = node.findOne('Text');
            if (textNode) {
                const scaleX = node.scaleX();
                const scaleY = node.scaleY();
                
                // Mettre à jour la taille de la police en fonction du scale
                const currentFontSize = element.fontSize || 16;
                const newFontSize = Math.max(8, Math.min(200, currentFontSize * Math.max(scaleX, scaleY)));
                textNode.fontSize(newFontSize);
                element.fontSize = newFontSize;
                
                // Réinitialiser le scale
                node.scaleX(1);
                node.scaleY(1);
                
                // Mettre à jour le cadre s'il existe
                if (element.stroke && element.textFrameNode) {
                    const textPadding = 2;
                    // Créer un texte temporaire pour mesurer la largeur réelle
                    const tempText = new Konva.Text({
                        text: textNode.text(),
                        fontSize: textNode.fontSize(),
                        fontFamily: textNode.fontFamily(),
                        fontStyle: textNode.fontStyle(),
                    });
                    const textActualWidth = tempText.width();
                    tempText.destroy();
                    const textActualHeight = textNode.height();
                    const textX = textNode.x();
                    const textY = textNode.y();
                    const align = element.align || 'left';
                    
                    let textActualX = textX;
                    if (align === 'center') {
                        textActualX = textX - textActualWidth / 2;
                    } else if (align === 'right') {
                        const textDisplayWidth = element.width || textNode.width();
                        textActualX = textX + textDisplayWidth - textActualWidth;
                    }
                    
                    const frameRect = element.textFrameNode;
                    frameRect.x(textActualX - textPadding);
                    frameRect.y(textY - textPadding);
                    frameRect.width(textActualWidth + (textPadding * 2));
                    frameRect.height(textActualHeight + (textPadding * 2));
                }
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
    
    // Pour les images dans un groupe, s'assurer que le transformer calcule le bounding box correctement
    const element = elements.value.find(el => el.id === id);
    
    // Si c'est un texte (avec ou sans cadre), configurer boundBoxFunc pour utiliser la largeur réelle du texte
    if (element && element.type === 'text') {
        transformer.boundBoxFunc((oldBox, newBox) => {
            const textNode = node.findOne('Text');
            if (textNode) {
                // Créer un texte temporaire pour mesurer la largeur réelle
                const tempText = new Konva.Text({
                    text: textNode.text(),
                    fontSize: textNode.fontSize(),
                    fontFamily: textNode.fontFamily(),
                    fontStyle: textNode.fontStyle(),
                });
                const textActualWidth = tempText.width();
                tempText.destroy();
                const textActualHeight = textNode.height();
                
                const align = element.align || 'left';
                const textX = textNode.x();
                const textY = textNode.y();
                let actualX = textX;
                
                if (align === 'center') {
             
                    const offsetX = textNode.offsetX() || 0;
         
                    if (offsetX > 0) {
                        actualX = textX - textActualWidth / 2;
                    } else {
                        actualX = textX - textActualWidth / 2;
                    }
                } else if (align === 'right') {
                    const textDisplayWidth = element.width || textNode.width();
                    actualX = textX + textDisplayWidth - textActualWidth;
                }
                
                // Si un cadre existe, prendre en compte ses dimensions (padding de 2px de chaque côté)
                const frameNode = node.findOne('Rect');
                let finalX = actualX;
                let finalY = textY;
                let finalWidth = textActualWidth;
                let finalHeight = textActualHeight;
                
                if (frameNode && element.stroke) {
                    const textPadding = 2; // Marge de 2px entre le texte et le rectangle
                    // Le cadre a un padding de 2px de chaque côté, donc on doit ajuster
                    finalX = actualX - textPadding;
                    finalY = textY - textPadding;
                    finalWidth = textActualWidth + (textPadding * 2);
                    finalHeight = textActualHeight + (textPadding * 2);
                }
                
                return {
                    x: finalX,
                    y: finalY,
                    width: finalWidth,
                    height: finalHeight,
                    rotation: newBox.rotation || 0,
                };
            }
            return newBox;
        });
        
        // Forcer la mise à jour du transformer après un court délai pour s'assurer que le cadre est créé
        nextTick(() => {
            if (transformer && layer) {
                setTimeout(() => {
                    if (transformer && layer) {
                        transformer.forceUpdate();
                        layer.draw();
                    }
                }, 50);
            }
        });
    } else if (element && element.type === 'image' && element.exerciseId && transformer) {

        const box = node.getClientRect();
        console.log('Group bounding box on select:', box);
        
        // Réinitialiser boundBoxFunc pour les images
        transformer.boundBoxFunc((oldBox, newBox) => {
            return newBox;
        });
        
        // Forcer le transformer à recalculer le bounding box en incluant tous les enfants
        nextTick(() => {
            if (transformer) {
                // Forcer la mise à jour du transformer
                transformer.forceUpdate();
                // Attendre un peu pour que Konva recalcule
                setTimeout(() => {
                    if (transformer && layer) {
                        transformer.forceUpdate();
                        layer.draw();
                    }
                }, 10);
            }
        });
    } else if (transformer) {
        // Réinitialiser boundBoxFunc pour les autres éléments
        transformer.boundBoxFunc((oldBox, newBox) => {
            return newBox;
        });
        // Pour les autres éléments, forcer la mise à jour normalement
        transformer.forceUpdate();
    }
    
    // S'assurer que le nœud est bien attaché au transformer
    transformer.nodes([node]);
    
    // Redessiner le layer
    if (layer) {
        layer.draw();
    }
    
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
    textFontFamily.value = 'Arial';
    textFontWeight.value = 'normal';
    textFontStyle.value = 'normal';
    textDecoration.value = 'none';
    textAlign.value = 'left';
    textStroke.value = false;
    textStrokeColor.value = '#000000';
    textStrokeWidth.value = 1;
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
        editingElement.fontFamily = textFontFamily.value;
        editingElement.fontWeight = textFontWeight.value;
        editingElement.fontStyle = textFontStyle.value;
        editingElement.textDecoration = textDecoration.value;
        editingElement.align = textAlign.value;
        editingElement.stroke = textStroke.value ? textStrokeColor.value : undefined;
        editingElement.strokeWidth = textStroke.value ? textStrokeWidth.value : undefined;
        
        if (editingElement.konvaNode) {
            updateTextNode(editingElement.konvaNode, editingElement);
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
            fontFamily: textFontFamily.value,
            fill: textColor.value,
            fontWeight: textFontWeight.value,
            fontStyle: textFontStyle.value,
            textDecoration: textDecoration.value,
            align: textAlign.value,
            stroke: textStroke.value ? textStrokeColor.value : undefined,
            strokeWidth: textStroke.value ? textStrokeWidth.value : undefined,
        };

        elements.value.push(element);
        addTextToCanvas(element);
        layer.draw();
    }
    showTextDialog.value = false;
};

// Helper function to update text node properties
const updateTextNode = (node: any, element: LayoutElement) => {
    // Vérifier si c'est un élément du footer (ne pas appliquer la logique d'alignement automatique)
    const isFooterElement = element.id && element.id.includes('footer');
    
    if (isFooterElement) {
        // Pour le footer, mettre à jour seulement les propriétés de base
        node.text(element.text || '');
        node.fontSize(element.fontSize || 16);
        node.fill(element.fill || '#000000');
        node.fontFamily(element.fontFamily || 'Arial');
        node.fontStyle((element.fontStyle === 'italic' ? 'italic' : '') + (element.fontWeight === 'bold' ? ' bold' : '') || 'normal');
        node.textDecoration(element.textDecoration === 'underline' ? 'underline' : '');
        node.x(element.x);
        node.y(element.y);
        // Le footer utilise offsetX pour le centrage, donc on le recalcule
        node.offsetX(node.width() / 2);
        node.offsetY(node.height() / 2);
        return;
    }
    
    // Le node est maintenant un groupe, on doit trouver le texte à l'intérieur
    const textNode = node.findOne('Text');
    if (!textNode) return;
    
    textNode.text(element.text || '');
    textNode.fontSize(element.fontSize || 16);
    textNode.fill(element.fill || '#000000');
    textNode.fontFamily(element.fontFamily || 'Arial');
    textNode.fontStyle((element.fontStyle === 'italic' ? 'italic' : '') + (element.fontWeight === 'bold' ? ' bold' : '') || 'normal');
    textNode.textDecoration(element.textDecoration === 'underline' ? 'underline' : '');
    
    const align = element.align || 'left';
    const margin = 20; // Marge par rapport aux bords
    
    // Largeur du texte pour l'affichage (utiliser toute la largeur disponible)
    const textDisplayWidth = canvasWidth.value - (margin * 2);
    element.width = textDisplayWidth;
    textNode.width(textDisplayWidth);
    textNode.align(align);
    
    // Ajuster la position X selon l'alignement par rapport à la page
    // Dans Konva:
    // - align: 'left' : X est le point de départ (gauche), texte aligné à gauche
    // - align: 'center' : X est le centre de la zone, texte centré autour de X
    // - align: 'right' : X est le point de départ (gauche) de la zone, texte aligné à droite dans la zone
    let textX = element.x;
    if (align === 'center') {
        // Centrer sur la largeur du canvas
        // Le point X est le centre de la zone, donc pour centrer la zone sur la page:
        // la zone doit commencer à (canvasWidth - textDisplayWidth) / 2
        // et le centre est à canvasWidth / 2
        textX = canvasWidth.value / 2;
        element.x = textX; // Sauvegarder la position centrée
        // Utiliser offsetX pour centrer le texte autour du point X
        textNode.offsetX(textDisplayWidth / 2);
    } else {
        // Réinitialiser offsetX pour les autres alignements
        textNode.offsetX(0);
        if (align === 'right') {
            // Aligner à droite de la page
            // Le texte doit se terminer à canvasWidth - margin
            // Donc la zone doit commencer à (canvasWidth - margin) - textDisplayWidth
            textX = (canvasWidth.value - margin) - textDisplayWidth;
            element.x = textX; // Sauvegarder la position à droite
        } else {
            // Aligner à gauche de la page
            // X est le point de départ (gauche) de la zone
            textX = margin;
            element.x = textX; // Sauvegarder la position à gauche
        }
    }
    textNode.x(textX);
    textNode.y(element.y);
    
    // Appliquer le stroke (cadre) autour du texte
    // Le node est maintenant un groupe, on doit trouver le rectangle
    const frameNode = node.findOne('Rect');
    
    if (element.stroke && textNode) {
        // Si un cadre est demandé, créer ou mettre à jour le rectangle
        const textPadding = 2; // Marge de 2px
        
        // Obtenir les dimensions réelles du texte rendu
        // Créer un texte temporaire pour mesurer la largeur réelle
        const tempText = new Konva.Text({
            text: textNode.text(),
            fontSize: textNode.fontSize(),
            fontFamily: textNode.fontFamily(),
            fontStyle: textNode.fontStyle(),
        });
        const textActualWidth = tempText.width();
        tempText.destroy();
        const textActualHeight = textNode.height();
        
        // Position réelle du texte (en tenant compte de l'alignement)
        let textActualX = textX;
        if (align === 'center') {
            textActualX = textX - textActualWidth / 2;
        } else if (align === 'right') {
            textActualX = textX + textDisplayWidth - textActualWidth;
        }
        
        // Calculer la position et les dimensions du rectangle
        let rectX = textActualX;
        let rectY = element.y;
        let rectWidth = textActualWidth;
        let rectHeight = textActualHeight;
        
        // Ajuster pour la marge de 2px
        rectX -= textPadding;
        rectY -= textPadding;
        rectWidth += textPadding * 2;
        rectHeight += textPadding * 2;
        
        if (!frameNode) {
            // Créer le rectangle de cadre
            const frameRect = new Konva.Rect({
                x: rectX,
                y: rectY,
                width: rectWidth,
                height: rectHeight,
                fill: undefined,
                stroke: element.stroke,
                strokeWidth: element.strokeWidth || 1,
                draggable: false,
                listening: false, // Ne pas interférer avec les événements
            });
            
            node.add(frameRect);
            element.textFrameNode = frameRect;
        } else {
            // Mettre à jour le rectangle existant
            frameNode.x(rectX);
            frameNode.y(rectY);
            frameNode.width(rectWidth);
            frameNode.height(rectHeight);
            frameNode.stroke(element.stroke);
            frameNode.strokeWidth(element.strokeWidth || 1);
        }
    } else {
        // Supprimer le rectangle de cadre s'il existe
        if (frameNode) {
            frameNode.destroy();
            element.textFrameNode = null;
        }
    }
};

// Edit text
let editingElement: LayoutElement | null = null;
const editText = (element: LayoutElement) => {
    editingElement = element;
    textInput.value = element.text || '';
    textFontSize.value = element.fontSize || 16;
    textColor.value = element.fill || '#000000';
    textFontFamily.value = element.fontFamily || 'Arial';
    textFontWeight.value = element.fontWeight || 'normal';
    textFontStyle.value = element.fontStyle || 'normal';
    textDecoration.value = element.textDecoration || 'none';
    textAlign.value = element.align || 'left';
    textStroke.value = !!element.stroke;
    textStrokeColor.value = element.stroke || '#000000';
    textStrokeWidth.value = element.strokeWidth || 1;
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
    if (element) {
        if (element.konvaNode) {
        element.konvaNode.destroy();
        }
        if (element.tableGroup) {
            element.tableGroup.destroy();
        }
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
            exerciseData: el.exerciseData,
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
        
        // Masquer les boutons avant l'export
        const buttonNodes: any[] = [];
        const deleteButtonNodes: any[] = [];
        elements.value.forEach(element => {
            if (element.buttonNode) {
                buttonNodes.push(element.buttonNode);
                element.buttonNode.visible(false);
            }
            if (element.deleteButtonNode) {
                deleteButtonNodes.push(element.deleteButtonNode);
                element.deleteButtonNode.visible(false);
            }
        });
        
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
        
        // Réafficher les boutons après l'export
        buttonNodes.forEach(buttonNode => {
            if (buttonNode) {
                buttonNode.visible(true);
            }
        });
        deleteButtonNodes.forEach(deleteButtonNode => {
            if (deleteButtonNode) {
                deleteButtonNode.visible(true);
            }
        });
        
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

// Open exercise instructions modal
const openExerciseInstructionsModal = (element: LayoutElement) => {
    editingExerciseElement.value = element;
    if (element.exerciseData) {
        exerciseData.value = JSON.parse(JSON.stringify(element.exerciseData));
        // S'assurer que instructionsStyle est toujours défini
        if (!exerciseData.value.instructionsStyle) {
            exerciseData.value.instructionsStyle = {
                fontSize: 12,
                fontFamily: 'Arial',
                fontWeight: 'normal',
                fontStyle: 'normal',
                fill: '#000000',
                backgroundColor: undefined,
                stroke: undefined,
                strokeWidth: 0,
                padding: 5,
                borderRadius: 0
            };
        }
        // S'assurer que showInstructions est défini
        if (exerciseData.value.showInstructions === undefined) {
            exerciseData.value.showInstructions = false;
        }
        // S'assurer que instructionsPosition est défini
        if (!exerciseData.value.instructionsPosition) {
            exerciseData.value.instructionsPosition = 'below';
        }
    } else {
        // Récupérer le titre de l'exercice
        const exercise = props.exercises.find(ex => ex.id === element.exerciseId);
        exerciseData.value = {
            title: exercise?.title || '',
            showTitle: true,
            titlePosition: 'above',
            instructions: '',
            showInstructions: false,
            instructionsPosition: 'below',
            instructionsStyle: {
                fontSize: 12,
                fontFamily: 'Arial',
                fontWeight: 'normal',
                fontStyle: 'normal',
                fill: '#000000',
                backgroundColor: undefined,
                stroke: undefined,
                strokeWidth: 0,
                padding: 5,
                borderRadius: 0
            },
            rows: [{ series: 1, reps: 20, recovery: 30, load: 10, useDuration: false, useBodyweight: false }]
        };
    }
    showExerciseInstructionsModal.value = true;
};

// Add row to exercise instructions
const addExerciseInstructionRow = () => {
    exerciseData.value.rows.push({ 
        series: 1, 
        reps: 20, 
        recovery: 30, 
        load: 10, 
        useDuration: false, 
        useBodyweight: false 
    });
};

// Remove row from exercise instructions
const removeExerciseInstructionRow = (index: number) => {
    // S'assurer que l'index est bien un nombre
    const rowIndex = Number(index);
    
    if (exerciseData.value.rows.length > 0 && rowIndex >= 0 && rowIndex < exerciseData.value.rows.length) {
        // Créer une nouvelle copie du tableau sans la ligne à supprimer
        const newRows = exerciseData.value.rows.filter((_, i) => i !== rowIndex);
        
        // Forcer la réactivité Vue en assignant un nouveau tableau
        exerciseData.value.rows = newRows;
        
        // Si toutes les lignes sont supprimées et qu'on est en train d'éditer un élément, supprimer le tableau
        if (newRows.length === 0 && editingExerciseElement.value && editingExerciseElement.value.tableGroup) {
            editingExerciseElement.value.tableGroup.destroy();
            editingExerciseElement.value.tableGroup = null;
            if (layer) {
                layer.draw();
            }
        }
    }
};

// Delete exercise image directly
const deleteExerciseImage = (element: LayoutElement) => {
    if (!layer) return;

    saveToHistory();
    
    if (element.konvaNode) {
        element.konvaNode.destroy();
    }
    if (element.tableGroup) {
        element.tableGroup.destroy();
    }
    if (element.titleNode) {
        element.titleNode.destroy();
    }
    
    elements.value = elements.value.filter(el => el.id !== element.id);
    
    if (transformer) {
        transformer.nodes([]);
    }
    if (selectedElementId.value === element.id) {
        selectedElementId.value = null;
    }
    
    if (layer) {
        layer.draw();
    }
};

// Delete exercise block
const deleteExerciseBlock = () => {
    if (!editingExerciseElement.value || !layer) return;

    saveToHistory();
    const element = editingExerciseElement.value;
    
    if (element.konvaNode) {
        element.konvaNode.destroy();
    }
    if (element.tableGroup) {
        element.tableGroup.destroy();
    }
    if (element.titleNode) {
        element.titleNode.destroy();
    }
    
    elements.value = elements.value.filter(el => el.id !== element.id);
    
    if (transformer) {
        transformer.nodes([]);
    }
    selectedElementId.value = null;
    
    showExerciseInstructionsModal.value = false;
    editingExerciseElement.value = null;
    
    if (layer) {
        layer.draw();
    }
};

// Save exercise instructions
const saveExerciseInstructions = () => {
    if (!editingExerciseElement.value || !layer) return;

    saveToHistory();
    
    editingExerciseElement.value.exerciseData = JSON.parse(JSON.stringify(exerciseData.value));

    // Créer ou mettre à jour le tableau et le titre
    createExerciseTable(editingExerciseElement.value);
    createExerciseTitle(editingExerciseElement.value);

    showExerciseInstructionsModal.value = false;
    editingExerciseElement.value = null;
};

// Create exercise title
const createExerciseTitle = (element: LayoutElement) => {
    if (!layer || !element.konvaNode || !element.exerciseData) return;

    // Supprimer l'ancien titre s'il existe
    if (element.titleNode) {
        element.titleNode.destroy();
        element.titleNode = null;
    }

    if (!element.exerciseData.showTitle || !element.exerciseData.title) {
        return;
    }

    const imageNode = element.konvaNode;
    const imageWidth = element.width || 200;
    const imageHeight = element.height || 200;
    const imageX = imageNode.x();
    const imageY = imageNode.y();

    const titleText = new Konva.Text({
        x: imageX,
        y: element.exerciseData.titlePosition === 'above' ? imageY - 25 : imageY + imageHeight + 5,
        text: element.exerciseData.title,
        fontSize: 14,
        fontFamily: 'Arial',
        fill: '#000000',
        fontStyle: 'bold',
        width: imageWidth,
        align: 'left',
    });

    layer.add(titleText);
    element.titleNode = titleText;
    layer.draw();
};

// Create exercise instructions
// Create exercise table next to image
const createExerciseTable = (element: LayoutElement) => {
    if (!layer || !element.konvaNode || !element.exerciseData) return;

    // Supprimer l'ancien tableau s'il existe
    if (element.tableGroup) {
        element.tableGroup.destroy();
        element.tableGroup = null;
    }

    // Si aucune ligne n'existe, ne pas créer de tableau
    if (!element.exerciseData.rows || element.exerciseData.rows.length === 0) {
        if (layer) {
            layer.draw();
        }
        return;
    }

    const imageNode = element.konvaNode;
    const imageWidth = element.width || 200;
    const imageHeight = element.height || 200;
    const imageX = imageNode.x();
    const imageY = imageNode.y();

    // Créer un groupe pour le tableau
    const tableGroup = new Konva.Group({
        x: imageX + imageWidth + 10,
        y: imageY,
    });

    // Dimensions du tableau (design plus proche du screen)
    const cellPadding = 8;
    const cellHeight = 35;
    const colWidths = {
        series: 60,
        reps: 60,
        recovery: 60,
        load: 60
    };
    const tableWidth = Object.values(colWidths).reduce((a, b) => a + b, 0) + cellPadding * 5;
    const headerHeight = 35;
    const tableHeight = headerHeight + (element.exerciseData.rows.length * cellHeight) + cellPadding * 2;

    // Fond du tableau (blanc)
    const tableBg = new Konva.Rect({
        x: 0,
        y: 0,
        width: tableWidth,
        height: tableHeight,
        fill: '#ffffff',
        stroke: '#e5e7eb',
        strokeWidth: 1,
        cornerRadius: 4,
    });
    tableGroup.add(tableBg);

    // En-têtes avec style (utiliser les labels de la première ligne comme référence)
    const firstRow = element.exerciseData.rows[0];
    const repsLabel = firstRow?.useDuration ? 'durée (s)' : 'répets';
    const loadLabel = firstRow?.useBodyweight ? 'poids de corps' : 'charge';

    const headers = [
        { text: 'série(s)', x: cellPadding, width: colWidths.series },
        { text: repsLabel, x: cellPadding + colWidths.series, width: colWidths.reps },
        { text: 'récup', x: cellPadding + colWidths.series + colWidths.reps, width: colWidths.recovery },
        { text: loadLabel, x: cellPadding + colWidths.series + colWidths.reps + colWidths.recovery, width: colWidths.load }
    ];

    // Ligne de séparation des en-têtes
    const headerLine = new Konva.Line({
        points: [0, headerHeight, tableWidth, headerHeight],
        stroke: '#d1d5db',
        strokeWidth: 1,
    });
    tableGroup.add(headerLine);

    headers.forEach(header => {
        const headerText = new Konva.Text({
            x: header.x,
            y: cellPadding + 5,
            text: header.text,
            fontSize: 11,
            fontFamily: 'Arial',
            fill: '#374151',
            width: header.width,
            align: 'center',
            fontStyle: 'bold',
        });
        tableGroup.add(headerText);
    });

    // Lignes de données
    element.exerciseData.rows.forEach((row, rowIndex) => {
        const rowY = headerHeight + (rowIndex * cellHeight) + cellPadding;
        
        // Ligne de séparation
        if (rowIndex > 0) {
            const rowLine = new Konva.Line({
                points: [0, rowY - cellPadding, tableWidth, rowY - cellPadding],
                stroke: '#e5e7eb',
                strokeWidth: 0.5,
            });
            tableGroup.add(rowLine);
        }

        // Cellules avec les bonnes valeurs selon les toggles
        const repsValue = row.useDuration ? (row.duration?.toString() || '') : (row.reps?.toString() || '');
        const loadValue = row.useBodyweight ? 'Pdc' : (row.load?.toString() || '');

        const cells = [
            { text: row.series?.toString() || '', x: cellPadding, width: colWidths.series },
            { text: repsValue, x: cellPadding + colWidths.series, width: colWidths.reps },
            { text: row.recovery?.toString() || '', x: cellPadding + colWidths.series + colWidths.reps, width: colWidths.recovery },
            { text: loadValue, x: cellPadding + colWidths.series + colWidths.reps + colWidths.recovery, width: colWidths.load }
        ];

        cells.forEach(cell => {
            const cellText = new Konva.Text({
                x: cell.x,
                y: rowY + 8,
                text: cell.text,
                fontSize: 11,
                fontFamily: 'Arial',
                fill: '#111827',
                width: cell.width,
                align: 'center',
            });
            tableGroup.add(cellText);
        });
    });

    layer.add(tableGroup);
    element.tableGroup = tableGroup;
    layer.draw();
};

// Update exercise table position when image moves
const updateExerciseTablePosition = (element: LayoutElement) => {
    if (!element.konvaNode) return;

    const imageNode = element.konvaNode;
    const imageWidth = element.width || 200;
    const imageHeight = element.height || 200;
    const imageX = imageNode.x();
    const imageY = imageNode.y();

    // Mettre à jour la position du tableau
    if (element.tableGroup) {
        element.tableGroup.x(imageX + imageWidth + 10);
        element.tableGroup.y(imageY);
    }

    // Mettre à jour la position du titre
    if (element.titleNode && element.exerciseData) {
        element.titleNode.x(imageX);
        element.titleNode.y(
            element.exerciseData.titlePosition === 'above' 
                ? imageY - 25 
                : imageY + imageHeight + 5
        );
    }

    
    if (layer) {
        layer.draw();
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
                                    @click="selectedCustomerIds = selectedCustomerIds.filter((id: number) => id !== customer.id)"
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
                            @click="(e: MouseEvent) => {
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
                                @change="(e: Event) => {
                                    const target = e.target as HTMLInputElement;
                                    if (target.checked) {
                                        if (!selectedCustomerIds.includes(customer.id)) {
                                            selectedCustomerIds.push(customer.id);
                                        }
                                    } else {
                                        selectedCustomerIds = selectedCustomerIds.filter((id: number) => id !== customer.id);
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
            <Card class="w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <CardContent class="p-6 space-y-4">
                    <h3 class="text-lg font-semibold">{{ editingElement ? 'Modifier le texte' : 'Ajouter du texte' }}</h3>
                    
                    <div class="space-y-2">
                    <Label>Texte</Label>
                        <Textarea 
                            v-model="textInput" 
                            placeholder="Entrez votre texte..." 
                            class="min-h-[80px]"
                        />
                </div>

                    <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label>Taille (px)</Label>
                            <Input v-model.number="textFontSize" type="number" min="8" max="200" />
                </div>
                <div class="space-y-2">
                            <Label>Police</Label>
                            <Select v-model="textFontFamily">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Arial">Arial</SelectItem>
                                    <SelectItem value="Helvetica">Helvetica</SelectItem>
                                    <SelectItem value="Times New Roman">Times New Roman</SelectItem>
                                    <SelectItem value="Courier New">Courier New</SelectItem>
                                    <SelectItem value="Verdana">Verdana</SelectItem>
                                    <SelectItem value="Georgia">Georgia</SelectItem>
                                    <SelectItem value="Palatino">Palatino</SelectItem>
                                    <SelectItem value="Garamond">Garamond</SelectItem>
                                    <SelectItem value="Comic Sans MS">Comic Sans MS</SelectItem>
                                    <SelectItem value="Impact">Impact</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Style</Label>
                        <div class="flex gap-2">
                            <Button 
                                :variant="textFontWeight === 'bold' ? 'default' : 'outline'"
                                size="sm"
                                @click="textFontWeight = textFontWeight === 'bold' ? 'normal' : 'bold'"
                            >
                                <Bold class="h-4 w-4" />
                            </Button>
                            <Button 
                                :variant="textFontStyle === 'italic' ? 'default' : 'outline'"
                                size="sm"
                                @click="textFontStyle = textFontStyle === 'italic' ? 'normal' : 'italic'"
                            >
                                <Italic class="h-4 w-4" />
                            </Button>
                            <Button 
                                :variant="textDecoration === 'underline' ? 'default' : 'outline'"
                                size="sm"
                                @click="textDecoration = textDecoration === 'underline' ? 'none' : 'underline'"
                            >
                                <Underline class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Couleur du texte</Label>
                    <Input v-model="textColor" type="color" />
                </div>

                    <div class="space-y-2">
                        <Label>Alignement</Label>
                        <div class="flex gap-2">
                            <Button 
                                :variant="textAlign === 'left' ? 'default' : 'outline'"
                                size="sm"
                                @click="textAlign = 'left'"
                            >
                                <AlignLeft class="h-4 w-4" />
                            </Button>
                            <Button 
                                :variant="textAlign === 'center' ? 'default' : 'outline'"
                                size="sm"
                                @click="textAlign = 'center'"
                            >
                                <AlignCenter class="h-4 w-4" />
                            </Button>
                            <Button 
                                :variant="textAlign === 'right' ? 'default' : 'outline'"
                                size="sm"
                                @click="textAlign = 'right'"
                            >
                                <AlignRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <input 
                                type="checkbox" 
                                id="textStroke" 
                                v-model="textStroke"
                                class="rounded"
                            />
                            <Label for="textStroke" class="cursor-pointer">Ajouter un cadre autour du texte</Label>
                        </div>
                        <div v-if="textStroke" class="grid grid-cols-2 gap-4 ml-6">
                            <div class="space-y-2">
                                <Label>Couleur du cadre</Label>
                                <Input v-model="textStrokeColor" type="color" />
                            </div>
                            <div class="space-y-2">
                                <Label>Épaisseur du cadre (px)</Label>
                                <Input v-model.number="textStrokeWidth" type="number" min="1" max="10" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                    <Button variant="outline" @click="showTextDialog = false">Annuler</Button>
                        <Button @click="confirmText">{{ editingElement ? 'Modifier' : 'Ajouter' }}</Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Exercise Instructions Modal -->
        <div v-if="showExerciseInstructionsModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <Card class="w-full max-w-5xl max-h-[90vh] flex flex-col">
                <CardContent class="p-6 space-y-4 flex-1 overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Configuration de l'exercice</h3>
                        <Button variant="ghost" size="sm" @click="showExerciseInstructionsModal = false">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <div class="flex-1 overflow-y-auto space-y-6">
                        <!-- Options d'affichage du titre -->
                        <div class="space-y-3 p-4 border rounded-lg">
                            <div class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    :checked="exerciseData.showTitle"
                                    @change="(e: Event) => {
                                        const target = e.target as HTMLInputElement;
                                        exerciseData.showTitle = target.checked;
                                    }"
                                    class="rounded"
                                    id="showTitle"
                                />
                                <Label for="showTitle" class="cursor-pointer">Afficher le nom de l'exercice</Label>
                            </div>
                            <div v-if="exerciseData.showTitle" class="space-y-2 ml-6">
                                <Label class="text-sm">Position du titre</Label>
                                <div class="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :class="{ 'bg-primary text-primary-foreground': exerciseData.titlePosition === 'above' }"
                                        @click="exerciseData.titlePosition = 'above'"
                                    >
                                        Au-dessus de l'image
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :class="{ 'bg-primary text-primary-foreground': exerciseData.titlePosition === 'below' }"
                                        @click="exerciseData.titlePosition = 'below'"
                                    >
                                        En dessous de l'image
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Tableau des séries -->
                        <div class="space-y-3">
                            <Label class="text-base font-semibold">Séries</Label>
                            <div class="space-y-3">
                                <div
                                    v-for="(row, index) in exerciseData.rows"
                                    :key="`row-${index}-${row.series || 0}-${row.reps || 0}`"
                                    class="grid grid-cols-12 gap-3 items-end p-4 border rounded-lg bg-neutral-50 dark:bg-neutral-900"
                                >
                                    <div class="space-y-1 col-span-2">
                                        <Label class="text-xs">Série(s)</Label>
                                        <Input
                                            v-model.number="row.series"
                                            type="number"
                                            min="1"
                                            class="w-full"
                                            placeholder="1"
                                        />
                                    </div>
                                    <div class="space-y-1 col-span-2">
                                        <div class="flex items-center justify-between mb-1">
                                            <Label class="text-xs">{{ row.useDuration ? 'Durée (s)' : 'Répets' }}</Label>
                                            <button
                                                type="button"
                                                @click="row.useDuration = !row.useDuration"
                                                class="text-xs text-blue-600 hover:text-blue-700"
                                            >
                                                {{ row.useDuration ? 'Répets' : 'Durée' }}
                                            </button>
                                        </div>
                                        <Input
                                            v-if="!row.useDuration"
                                            v-model.number="row.reps"
                                            type="number"
                                            min="1"
                                            class="w-full"
                                            placeholder="20"
                                        />
                                        <Input
                                            v-else
                                            v-model.number="row.duration"
                                            type="number"
                                            min="1"
                                            class="w-full"
                                            placeholder="30"
                                        />
                                    </div>
                                    <div class="space-y-1 col-span-2">
                                        <Label class="text-xs">Récup (s)</Label>
                                        <Input
                                            v-model.number="row.recovery"
                                            type="number"
                                            min="0"
                                            class="w-full"
                                            placeholder="30"
                                        />
                                    </div>
                                    <div class="space-y-1 col-span-2">
                                        <div class="flex items-center justify-between mb-1">
                                            <Label class="text-xs">{{ row.useBodyweight ? 'Poids de corps' : 'Charge (kg)' }}</Label>
                                            <button
                                                type="button"
                                                @click="row.useBodyweight = !row.useBodyweight"
                                                class="text-xs text-blue-600 hover:text-blue-700"
                                            >
                                                {{ row.useBodyweight ? 'Charge' : 'Pdc' }}
                                            </button>
                                        </div>
                                        <Input
                                            v-if="!row.useBodyweight"
                                            v-model.number="row.load"
                                            type="number"
                                            min="0"
                                            class="w-full"
                                            placeholder="10"
                                        />
                                        <Input
                                            v-else
                                            value="Pdc"
                                            disabled
                                            class="w-full bg-neutral-200 dark:bg-neutral-800"
                                        />
                                    </div>
                                    <div class="flex justify-end col-span-4">
                                        <button
                                            type="button"
                                            @click.stop.prevent="removeExerciseInstructionRow(index)"
                                            class="inline-flex items-center justify-center h-8 w-8 p-0 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 cursor-pointer rounded-md transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        >
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t">
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="addExerciseInstructionRow"
                            >
                                <Plus class="h-4 w-4 mr-2" />
                                Ajouter une ligne
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deleteExerciseBlock"
                            >
                                <Trash2 class="h-4 w-4 mr-2" />
                                Supprimer le bloc
                            </Button>
                        </div>
                        <div class="flex gap-2">
                            <Button variant="outline" @click="showExerciseInstructionsModal = false">
                                Annuler
                            </Button>
                            <Button @click="saveExerciseInstructions">
                                Enregistrer
                            </Button>
                        </div>
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


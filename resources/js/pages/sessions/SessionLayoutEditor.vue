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
    AlignRight,
    Pencil,
    Printer
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

interface ExerciseTitleStyle {
    fontSize?: number;
    fontFamily?: string;
    fill?: string;
    backgroundColor?: string;
    stroke?: string;
    strokeWidth?: number;
}

interface ExerciseData {
    title?: string;
    showTitle?: boolean;
    titlePosition?: 'above' | 'below'; // Position du titre par rapport à l'image
    titleStyle?: ExerciseTitleStyle; // Style du titre
    imageFrame?: boolean; // Encadrer l'image
    imageFrameColor?: string; // Couleur du cadre de l'image
    imageFrameWidth?: number; // Épaisseur du cadre de l'image
    instructions?: string; // Consignes globales
    showInstructions?: boolean;
    instructionsPosition?: 'above' | 'below'; // Position des consignes par rapport à l'image
    instructionsStyle?: ExerciseInstructionsStyle;
    rows: ExerciseInstructionRow[];
}

interface TableData {
    rows: ExerciseInstructionRow[];
}

interface LayoutElement {
    id: string;
    type: 'image' | 'text' | 'rect' | 'ellipse' | 'line' | 'arrow' | 'highlight' | 'table';
    x: number;
    y: number;
    width?: number;
    height?: number;
    scaleX?: number;
    scaleY?: number;
    rotation?: number;
    imageUrl?: string;
    exerciseId?: number;
    exerciseData?: ExerciseData;
    tableData?: TableData;
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
    imageFrameNode?: any; // Rectangle autour de l'image pour le cadre
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
const canvasWidth = ref(800);
const canvasHeight = ref(1140);
const scale = ref(1);

const containerRef = ref<HTMLDivElement | null>(null);
let stage: Konva.Stage | null = null;
let layer: Konva.Layer | null = null;
let transformer: Konva.Transformer | null = null;

const elements = ref<LayoutElement[]>([]);
const selectedElementId = ref<string | null>(null);
const isDraggingExercise = ref(false);
const draggedExercise = ref<Exercise | null>(null);

const history = ref<LayoutElement[][]>([]);
const historyIndex = ref(-1);
const maxHistorySize = 50;

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

const showTableModal = ref(false);
const editingTableElement = ref<LayoutElement | null>(null);
const tableData = ref<TableData>({
    rows: [{ series: 1, reps: 20, recovery: 30, load: 10, useDuration: false, useBodyweight: false }]
});

const showExerciseImageModal = ref(false);
const editingExerciseImageElement = ref<LayoutElement | null>(null);
const exerciseImageData = ref<{
    title: string;
    showTitle: boolean;
    titlePosition: 'above' | 'below';
    fontSize: number;
    fontFamily: string;
    fill: string;
    backgroundColor: string | undefined;
    stroke: string | undefined;
    strokeWidth: number;
    imageFrame: boolean;
    imageFrameColor: string;
    imageFrameWidth: number;
}>({
    title: '',
    showTitle: true,
    titlePosition: 'above',
    fontSize: 14,
    fontFamily: 'Arial',
    fill: '#000000',
    backgroundColor: undefined,
    stroke: undefined,
    strokeWidth: 0,
    imageFrame: false,
    imageFrameColor: '#000000',
    imageFrameWidth: 2
});

const sessionName = ref(props.sessionName || '');
const selectedCustomerIds = ref<number[]>(props.sessionCustomers?.map(c => c.id) || []);
const showCustomerModal = ref(false);
const customerSearchTerm = ref('');

const isDrawingShape = ref(false);
const drawingShapeType = ref<'rect' | 'ellipse' | 'line' | 'arrow' | 'highlight' | null>(null);
const shapeStartPos = ref<{ x: number; y: number } | null>(null);
const shapeStrokeColor = ref('#000000');
const shapeStrokeWidth = ref(2);
const shapeOpacity = ref(1);
const tempShapeRef = ref<any>(null);
const isMouseDown = ref(false);

let currentDrawingHandlers: {
    mousedown?: (e: any) => void;
    mousemove?: (e: any) => void;
    mouseup?: (e: any) => void;
} = {};

const libraryViewMode = ref<'grid-2' | 'grid-4' | 'grid-6'>('grid-6');

onMounted(() => {
    if (!containerRef.value) return;

    if (props.initialLayout && props.initialLayout.layout_data) {
        canvasWidth.value = props.initialLayout.canvas_width || 800;
        canvasHeight.value = props.initialLayout.canvas_height || 1140; // Format A4 par défaut
        
        if (Array.isArray(props.initialLayout.layout_data)) {
            elements.value = JSON.parse(JSON.stringify(props.initialLayout.layout_data));
        } else {
            elements.value = [];
        }
        
    }

    if (!containerRef.value) {
        return;
    }
    
    const containerRect = containerRef.value.getBoundingClientRect();
    
    stage = new Konva.Stage({
        container: containerRef.value,
        width: canvasWidth.value * scale.value,
        height: canvasHeight.value * scale.value,
    });


    layer = new Konva.Layer();
    stage.add(layer);
    
    const stageContent = stage.getContent();
    if (stageContent) {
        stageContent.style.width = `${canvasWidth.value * scale.value}px`;
        stageContent.style.height = `${canvasHeight.value * scale.value}px`;
    }
    
    layer.draw();
    
    transformer = new Konva.Transformer({
        nodes: [],
        rotateEnabled: true,
        borderEnabled: true,
        borderStroke: '#0096ff',
        borderStrokeWidth: 2,
        anchorFill: '#0096ff',
        anchorStroke: '#ffffff',
        anchorSize: 8,
        resizeEnabled: true,
        keepRatio: false,
        enabledAnchors: ['top-left', 'top-center', 'top-right', 'middle-left', 'middle-right', 'bottom-left', 'bottom-center', 'bottom-right'],
        boundBoxFunc: (oldBox, newBox) => {
            return newBox;
        },
    });
    layer.add(transformer);

    loadElementsToCanvas().then(() => {
        if (layer) {
            layer.draw();
        }
        nextTick(() => {
            if (stage) {
                const stageContent = stage.getContent();
                if (stageContent) {
                    stageContent.style.width = `${canvasWidth.value * scale.value}px`;
                    stageContent.style.height = `${canvasHeight.value * scale.value}px`;
                }
            }
        });
        saveToHistory();
    });

    stage.on('click', (e) => {
        const target = e.target as any;
        if (target === stage || target === layer) {
            transformer?.nodes([]);
            selectedElementId.value = null;
            layer?.draw();
        }
    });

    nextTick(() => {
        setupDragAndDrop();
    });
    
    const handleKeyDown = (e: KeyboardEvent) => {
        if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) {
            return;
        }
        
        if ((e.key === 'Delete' || e.key === 'Backspace') && selectedElementId.value) {
            e.preventDefault();
            deleteSelected();
        }
    };
    
    window.addEventListener('keydown', handleKeyDown);
    
    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeyDown);
    });
});

watch([canvasWidth, canvasHeight], () => {
    if (stage && layer) {
        stage.width(canvasWidth.value * scale.value);
        stage.height(canvasHeight.value * scale.value);
        stage.getContent().style.width = `${canvasWidth.value * scale.value}px`;
        stage.getContent().style.height = `${canvasHeight.value * scale.value}px`;
        recalculateFooterPosition();
        layer.draw();
    }
}, { immediate: false });

onUnmounted(() => {
    if (stage) {
        stage.destroy();
    }
});

const loadElementsToCanvas = async (addFooter: boolean = true) => {
    if (!layer) {
        return;
    }

    for (const element of elements.value) {
        try {
            if (element.type === 'image' && element.imageUrl) {
                await addImageToCanvas(element);
                if (element.exerciseData) {
                    nextTick(() => {
                        createExerciseTitle(element);
                        createImageFrame(element);
                    });
                }
            } else if (element.type === 'text' && element.text) {
                addTextToCanvas(element);
            } else if (element.type === 'table' && element.tableData) {
                addTableToCanvas(element);
            } else if (['rect', 'ellipse', 'line', 'arrow', 'highlight'].includes(element.type)) {
                addShapeToCanvas(element);
            }
        } catch (error) {
        }
    }
    
    if (addFooter) {
        await addDefaultFooterIfNeeded();
    }
    
    if (layer) {
        layer.draw();
    }
};

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

const addDefaultFooterIfNeeded = async () => {
    const footerElements = elements.value.filter(el => el.id && el.id.includes('footer'));
    const hasFooter = footerElements.length > 0;
    const footerAlreadyLoaded = footerElements.some(el => el.konvaNode);
    
    if (hasFooter && footerAlreadyLoaded) {
        recalculateFooterPosition();
        return;
    }
    
    if (hasFooter && !footerAlreadyLoaded) {
        return;
    }
    
    const footerHeight = 60;
    const footerY = canvasHeight.value - footerHeight;
    
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
    
    const logoImageUrl = '/assets/logo_fitnessclic.png';
    
    const logoImg = new Image();
    logoImg.src = logoImageUrl;
    
    await new Promise<void>((resolve) => {
        logoImg.onload = () => {
            const maxHeight = footerHeight - 10;
            const maxWidth = 100;
            
            const aspectRatio = logoImg.width / logoImg.height;
            let logoWidth = maxWidth;
            let logoHeight = logoWidth / aspectRatio;
            
            if (logoHeight > maxHeight) {
                logoHeight = maxHeight;
                logoWidth = logoHeight * aspectRatio;
            }
            
            const estimatedTextWidth = 400;
            const textX = canvasWidth.value / 2;
            const textRight = textX + estimatedTextWidth / 2;
            const logoX = textRight + 15; 
            
            const logoImage: LayoutElement = {
                id: 'footer-logo-' + Date.now(),
                type: 'image',
                x: logoX,
                y: footerY + (footerHeight - logoHeight) / 2,
                width: logoWidth,
                height: logoHeight,
                imageUrl: logoImageUrl,
            };
            
            elements.value.push(footerRect);
            elements.value.push(footerText);
            elements.value.push(logoImage);
            
            addShapeToCanvas(footerRect);
            addTextToCanvas(footerText);
            addImageToCanvas(logoImage).then(() => {
                if (footerText.konvaNode && logoImage.konvaNode) {
                    const textWidth = footerText.konvaNode.width();
                    const textX = canvasWidth.value / 2;
                    const textRight = textX + textWidth / 2;
                    const newLogoX = textRight + 15;
                    
                    logoImage.x = newLogoX;
                    logoImage.konvaNode.x(newLogoX);
                    if (layer) {
                        layer.draw();
                    }
                }
            }).then(() => {
                if (footerRect.konvaNode) {
                    footerRect.konvaNode.draggable(false);
                }
                if (footerText.konvaNode) {
                    footerText.konvaNode.draggable(false);
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

const addImageToCanvas = async (element: LayoutElement) => {
    if (!layer) return;
    const localLayer = layer;

    return new Promise<void>((resolve, reject) => {
        const imageObj = new Image();
        
        
        imageObj.onload = () => {
            try {
                const naturalWidth = imageObj.width || 200;
                const naturalHeight = imageObj.height || 200;
                const aspectRatio = naturalWidth / naturalHeight;
                
                const isNewInsertWithoutSize = element.width === undefined && element.height === undefined;

                let width = element.width;
                let height = element.height;
                
                if (width && height) {
                    const specifiedRatio = width / height;
                    if (Math.abs(specifiedRatio - aspectRatio) > 0.01) {
                        height = width / aspectRatio;
                    }
                } else if (width) {
                    height = width / aspectRatio;
                } else if (height) {
                    width = height * aspectRatio;
                } else {
                    // Taille de base (sera ensuite normalisée via max/min)
                    width = naturalWidth;
                    height = naturalHeight;
                }
                
                const maxWidth = 300;
                const maxHeight = 300;
                const minWidth = 50;
                const minHeight = 50;
                
                if (width > maxWidth || height > maxHeight) {
                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                    width = width * ratio;
                    height = height * ratio;
                }
                
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

                // Exigence: à l'insertion d'un exercice, la taille doit être divisée par 2.
                // On applique donc le facteur sur la taille finale (après contraintes max/min),
                // sinon les images déjà "capées" ne changent pas visuellement.
                if (isNewInsertWithoutSize) {
                    width = width / 2;
                    height = height / 2;

                    // Re-clamp pour éviter des éléments trop petits à manipuler
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
                }
                
                let x = element.x ?? (canvasWidth.value / 2);
                let y = element.y ?? (canvasHeight.value / 2);
                
                if (x < 0 || x > canvasWidth.value || y < 0 || y > canvasHeight.value) {
                    x = (canvasWidth.value - width) / 2;
                    y = (canvasHeight.value - height) / 2;
                }
                
                element.x = x;
                element.y = y;
                
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

                if (element.exerciseId) {
                    const editButtonSize = 28;
                    const editButtonX = 5;
                    const editButtonY = 5;
                    const editButtonCenterX = editButtonX + editButtonSize / 2;
                    const editButtonCenterY = editButtonY + editButtonSize / 2;

                    const editButtonBg = new Konva.Circle({
                        x: editButtonCenterX,
                        y: editButtonCenterY,
                        radius: editButtonSize / 2,
                        fill: '#3B82F6',
                        stroke: '#ffffff',
                        strokeWidth: 2,
                        opacity: 0.9,
                    });

                    const editButtonIcon = new Konva.Text({
                        x: editButtonCenterX,
                        y: editButtonCenterY,
                        text: '✎',
                        fontSize: 16,
                        fontFamily: 'Arial',
                        fill: '#ffffff',
                        align: 'center',
                        verticalAlign: 'middle',
                    });
                    editButtonIcon.offsetX(editButtonIcon.width() / 2);
                    editButtonIcon.offsetY(editButtonIcon.height() / 2);

                    const editButtonGroup = new Konva.Group({
                        x: 0,
                        y: 0,
                    });
                    editButtonGroup.add(editButtonBg);
                    editButtonGroup.add(editButtonIcon);

                    editButtonGroup.on('click', (e) => {
                        e.cancelBubble = true;
                        openExerciseImageModal(element);
                    });

                    editButtonGroup.on('mouseenter', () => {
                        document.body.style.cursor = 'pointer';
                        editButtonBg.fill('#2563EB');
                        layer?.draw();
                    });

                    editButtonGroup.on('mouseleave', () => {
                        document.body.style.cursor = 'default';
                        editButtonBg.fill('#3B82F6');
                        layer?.draw();
                    });

                    imageGroup.add(editButtonGroup);
                    element.buttonNode = editButtonGroup;

                    const deleteButtonSize = 24;
                    const deleteButtonX = width - deleteButtonSize - 5;
                    const deleteButtonY = 5;
                    const buttonCenterX = deleteButtonX + deleteButtonSize / 2;
                    const buttonCenterY = deleteButtonY + deleteButtonSize / 2;

                    const deleteButtonBg = new Konva.Circle({
                        x: buttonCenterX,
                        y: buttonCenterY,
                        radius: deleteButtonSize / 2,
                        fill: '#EF4444',
                        stroke: '#ffffff',
                        strokeWidth: 2,
                        opacity: 0.9,
                    });

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
                });

                imageGroup.on('transformend', () => {
                    updateElementTransform(element.id, imageGroup);
                });

                imageGroup.on('click', (e) => {
                    if (e.target !== imageGroup && e.target.parent === imageGroup) {
                        if (element.buttonNode && (
                            e.target === element.buttonNode || 
                            e.target.parent === element.buttonNode || 
                            e.target.parent?.parent === element.buttonNode ||
                            e.target.parent?.parent?.parent === element.buttonNode
                        )) {
                            return;
                        }
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

                imageGroup.draggable(true);
                imageGroup.x(x);
                imageGroup.y(y);
                
                localLayer.add(imageGroup);
                element.konvaNode = imageGroup;

                localLayer.draw();

                if (element.exerciseData) {
                    nextTick(() => {
                        createExerciseTitle(element);
                        createImageFrame(element);
                    });
                }

                nextTick(() => {
                    const box = imageGroup.getClientRect();
                    
                    if (selectedElementId.value === element.id && transformer) {
                        transformer.nodes([]);
                        nextTick(() => {
                            if (transformer) {
                                transformer.nodes([imageGroup]);
                                transformer.forceUpdate();
                                localLayer.draw();
                            }
                        });
                    }
                });
                
                element.width = width;
                element.height = height;
                element.x = x;
                element.y = y;

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
                
                localLayer.batchDraw();
                localLayer.draw();
                
                setTimeout(() => {
                    localLayer.draw();
                }, 100);
                
                resolve();
            } catch (error) {
                reject(error);
            }
        };
        
        imageObj.onerror = (error) => {
            notifyError(`Erreur lors du chargement de l'image: ${element.imageUrl}`);
            reject(new Error('Failed to load image'));
        };
        
        if (element.imageUrl) {
            let imageUrl = element.imageUrl;
            
            if (!imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
                if (!imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                if (imageUrl.startsWith('/storage/')) {
                } else if (!imageUrl.startsWith('http')) {
                    imageUrl = window.location.origin + (imageUrl.startsWith('/') ? imageUrl : '/' + imageUrl);
                }
            }
            
            imageObj.src = imageUrl;
        } else {
            reject(new Error('No image URL provided'));
        }
    });
};

const addTextToCanvas = (element: LayoutElement) => {
    if (!layer) return;

    const fontStyle = (element.fontStyle === 'italic' ? 'italic' : '') + (element.fontWeight === 'bold' ? ' bold' : '') || 'normal';
    
    const isFooterElement = element.id && element.id.includes('footer');
    
    if (isFooterElement) {
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

    const align = element.align || 'left';
    const margin = 20;
    const maxWidth = canvasWidth.value - (margin * 2);

    // Mesure "naturelle" (sans wrap) pour que le transformer encadre bien un petit texte
    const measureText = new Konva.Text({
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fontStyle: fontStyle,
        textDecoration: element.textDecoration === 'underline' ? 'underline' : '',
    });
    const naturalWidth = measureText.width();
    measureText.destroy();

    const boxWidth = Math.max(1, Math.min(naturalWidth, maxWidth));

    // Position du bloc texte sur la page selon l'alignement
    let groupX = margin;
    if (align === 'center') {
        groupX = (canvasWidth.value - boxWidth) / 2;
    } else if (align === 'right') {
        groupX = canvasWidth.value - margin - boxWidth;
    }

    element.x = groupX;

    const textGroup = new Konva.Group({
        x: groupX,
        y: element.y,
        draggable: true,
        id: element.id,
    });

    const konvaText = new Konva.Text({
        x: 0,
        y: 0,
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fill: element.fill || '#000000',
        fontStyle: fontStyle,
        textDecoration: element.textDecoration === 'underline' ? 'underline' : '',
        align: align,
        width: boxWidth,
        draggable: false,
    });

    textGroup.add(konvaText);

    element.width = boxWidth;

    if (element.stroke) {
        const textPadding = 2;
        const frameRect = new Konva.Rect({
            x: -textPadding,
            y: -textPadding,
            width: boxWidth + (textPadding * 2),
            height: konvaText.height() + (textPadding * 2),
            fill: undefined,
            stroke: element.stroke,
            strokeWidth: element.strokeWidth || 1,
            draggable: false,
            listening: false,
        });
        textGroup.add(frameRect);
        frameRect.moveToBottom();
        element.textFrameNode = frameRect;
    }

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

const addTableToCanvas = (element: LayoutElement) => {
    if (!layer || !element.tableData) return;
    
    const cellPadding = 5;
    const cellHeight = 28;
    const colWidths = {
        series: 55,
        reps: 55,
        recovery: 55,
        load: 55
    };
    const tableWidth = Object.values(colWidths).reduce((a, b) => a + b, 0) + cellPadding * 5;
    const headerHeight = 28;
    const tableHeight = headerHeight + (element.tableData.rows.length * cellHeight) + cellPadding * 2;
    
    const tableGroup = new Konva.Group({
        x: element.x,
        y: element.y,
        draggable: true,
        id: element.id,
    });

    // Important: un tableau est un Konva.Group -> le resize se fait via scaleX/scaleY.
    // On réapplique donc l'échelle persistée pour éviter que la taille "revienne" à l'origine.
    tableGroup.scaleX(element.scaleX ?? 1);
    tableGroup.scaleY(element.scaleY ?? 1);
    
    const tableBg = new Konva.Rect({
        x: 0,
        y: 0,
        width: tableWidth,
        height: tableHeight,
        fill: '#ffffff',
        stroke: '#e5e7eb',
        strokeWidth: 1,
        cornerRadius: 4,
        shadowColor: 'rgba(0, 0, 0, 0.15)',
        shadowBlur: 8,
        shadowOffset: { x: 0, y: 2 },
        shadowOpacity: 1,
    });
    tableGroup.add(tableBg);
    
    const headerBg = new Konva.Rect({
        x: 0,
        y: 0,
        width: tableWidth,
        height: headerHeight,
        fill: '#E0F2FE',
        cornerRadius: 4,
    });
    tableGroup.add(headerBg);
    
    const firstRow = element.tableData.rows[0];
    const repsLabel = firstRow?.useDuration ? 'durée (s)' : 'répets';
    const loadLabel = firstRow?.useBodyweight ? 'poids de corps' : 'charge';
    
    const headers = [
        { text: 'série(s)', x: cellPadding, width: colWidths.series },
        { text: repsLabel, x: cellPadding + colWidths.series, width: colWidths.reps },
        { text: 'récup', x: cellPadding + colWidths.series + colWidths.reps, width: colWidths.recovery },
        { text: loadLabel, x: cellPadding + colWidths.series + colWidths.reps + colWidths.recovery, width: colWidths.load }
    ];
    
    const headerLine = new Konva.Line({
        points: [0, headerHeight, tableWidth, headerHeight],
        stroke: '#d1d5db',
        strokeWidth: 1,
    });
    tableGroup.add(headerLine);
    
    headers.forEach(header => {
        const headerText = new Konva.Text({
            x: header.x,
            y: cellPadding + 3,
            text: header.text,
            fontSize: 10,
            fontFamily: 'Arial',
            fill: '#374151',
            width: header.width,
            align: 'center',
            fontStyle: 'bold',
        });
        tableGroup.add(headerText);
    });
    
    element.tableData.rows.forEach((row, rowIndex) => {
        const rowY = headerHeight + (rowIndex * cellHeight) + cellPadding;
        
        if (rowIndex > 0) {
            const rowLine = new Konva.Line({
                points: [0, rowY - cellPadding, tableWidth, rowY - cellPadding],
                stroke: '#e5e7eb',
                strokeWidth: 0.5,
            });
            tableGroup.add(rowLine);
        }
        
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
                y: rowY + 5,
                text: cell.text,
                fontSize: 10,
                fontFamily: 'Arial',
                fill: '#111827',
                width: cell.width,
                align: 'center',
            });
            tableGroup.add(cellText);
        });
    });
    
    const deleteButtonSize = 24;
    const deleteButtonX = tableWidth - deleteButtonSize - 5;
    const deleteButtonY = 5;
    const buttonCenterX = deleteButtonX + deleteButtonSize / 2;
    const buttonCenterY = deleteButtonY + deleteButtonSize / 2;
    
    const deleteButtonBg = new Konva.Circle({
        x: buttonCenterX,
        y: buttonCenterY,
        radius: deleteButtonSize / 2,
        fill: '#EF4444',
        stroke: '#ffffff',
        strokeWidth: 2,
    });
    
    const deleteButtonXIcon = new Konva.Text({
        x: buttonCenterX,
        y: buttonCenterY,
        text: '×',
        fontSize: 18,
        fontFamily: 'Arial',
        fill: '#ffffff',
        fontStyle: 'bold',
    });
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
        if (!layer) return;
        
        saveToHistory();
        
        if (element.konvaNode) {
            element.konvaNode.destroy();
        }
        if (element.tableGroup) {
            element.tableGroup.destroy();
        }
        
        elements.value = elements.value.filter(el => el.id !== element.id);
        
        if (selectedElementId.value === element.id) {
            if (transformer) {
                transformer.nodes([]);
            }
            selectedElementId.value = null;
        }
        
        layer.draw();
    });
    
    deleteButtonGroup.on('mouseenter', () => {
        deleteButtonBg.fill('#DC2626');
        layer?.draw();
    });
    
    deleteButtonGroup.on('mouseleave', () => {
        deleteButtonBg.fill('#EF4444');
        layer?.draw();
    });
    
    tableGroup.add(deleteButtonGroup);
    element.deleteButtonNode = deleteButtonGroup;
    
    const instructionsButtonText = '+ Consignes';
    const instructionsButtonPadding = 8;
    const instructionsButtonFontSize = 12;
    const tempText = new Konva.Text({
        text: instructionsButtonText,
        fontSize: instructionsButtonFontSize,
        fontFamily: 'Arial',
    });
    const textWidth = tempText.width();
    tempText.destroy();
    const instructionsButtonWidth = textWidth + instructionsButtonPadding * 2;
    const instructionsButtonHeight = 28;
    const spacingBelowTable = 8; 
    const instructionsButtonX = (tableWidth - instructionsButtonWidth) / 2; 
    const instructionsButtonY = tableHeight + spacingBelowTable;
    
    const instructionsButtonBg = new Konva.Rect({
        x: instructionsButtonX,
        y: instructionsButtonY,
        width: instructionsButtonWidth,
        height: instructionsButtonHeight,
        fill: '#3B82F6',
        cornerRadius: 4,
    });
    
    const instructionsButtonTextNode = new Konva.Text({
        x: instructionsButtonX + instructionsButtonWidth / 2,
        y: instructionsButtonY + instructionsButtonHeight / 2,
        text: instructionsButtonText,
        fontSize: instructionsButtonFontSize,
        fontFamily: 'Arial',
        fill: '#ffffff',
        fontStyle: 'bold',
    });
    instructionsButtonTextNode.offsetX(instructionsButtonTextNode.width() / 2);
    instructionsButtonTextNode.offsetY(instructionsButtonFontSize / 2.5);
    
    const instructionsButtonGroup = new Konva.Group({
        x: 0,
        y: 0,
    });
    instructionsButtonGroup.add(instructionsButtonBg);
    instructionsButtonGroup.add(instructionsButtonTextNode);
    
    instructionsButtonGroup.on('click', (e) => {
        e.cancelBubble = true;
        openTableModal(element);
    });
    
    instructionsButtonGroup.on('mouseenter', () => {
        instructionsButtonBg.fill('#2563EB');
        layer?.draw();
    });
    
    instructionsButtonGroup.on('mouseleave', () => {
        instructionsButtonBg.fill('#3B82F6');
        layer?.draw();
    });
    
    tableGroup.add(instructionsButtonGroup);
    element.buttonNode = instructionsButtonGroup;
    
    tableGroup.on('dragend', () => {
        updateElementPosition(element.id, tableGroup.x(), tableGroup.y());
    });
    
    tableGroup.on('transformend', () => {
        updateElementTransform(element.id, tableGroup);
    });
    
    tableGroup.on('click', (e) => {
        if (element.deleteButtonNode && (
            e.target === element.deleteButtonNode || 
            e.target.parent === element.deleteButtonNode || 
            e.target.parent?.parent === element.deleteButtonNode ||
            e.target.parent?.parent?.parent === element.deleteButtonNode
        )) {
            return;
        }
        if (element.buttonNode && (
            e.target === element.buttonNode || 
            e.target.parent === element.buttonNode || 
            e.target.parent?.parent === element.buttonNode ||
            e.target.parent?.parent?.parent === element.buttonNode
        )) {
            return;
        }
        selectElement(element.id, tableGroup);
    });
    
    const buttonSpacing = 8;
    const buttonHeight = 28;
    element.width = tableWidth;
    element.height = tableHeight + buttonSpacing + buttonHeight;
    
    layer.add(tableGroup);
    element.konvaNode = tableGroup;
    element.tableGroup = tableGroup;
};

const saveToHistory = () => {
    if (historyIndex.value < history.value.length - 1) {
        history.value = history.value.slice(0, historyIndex.value + 1);
    }
    
    const snapshot = elements.value.map(el => {
        const { konvaNode, ...elementWithoutNode } = el;
        return JSON.parse(JSON.stringify(elementWithoutNode));
    });
    
    history.value.push(snapshot);
    historyIndex.value = history.value.length - 1;
    
    if (history.value.length > maxHistorySize) {
        history.value.shift();
        historyIndex.value = history.value.length - 1;
    }
};

const undo = async () => {
    if (historyIndex.value < 0 || !layer) return;
    
    if (historyIndex.value === 0) return;
    
    historyIndex.value--;
    const previousState = history.value[historyIndex.value];
    
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
    
    if (previousState) {
        elements.value = JSON.parse(JSON.stringify(previousState));
        await loadElementsToCanvas(false);
        
        const footerElements = elements.value.filter(el => el.id && el.id.includes('footer'));
        
        if (footerElements.length === 0) {
            await addDefaultFooterIfNeeded();
        }
    } else {
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

const canUndo = computed(() => historyIndex.value > 0);

const updateElementPosition = (id: string, x: number, y: number) => {
    const element = elements.value.find(el => el.id === id);
    if (element) {
        element.x = x;
        element.y = y;
        
        if (element.type === 'ellipse' && element.konvaNode && (element.konvaNode as any)._ellipseShape) {
            const ellipseShape = (element.konvaNode as any)._ellipseShape;
            const radiusX = ellipseShape.radiusX();
            const radiusY = ellipseShape.radiusY();
            element.konvaNode.x(x - radiusX);
            element.konvaNode.y(y - radiusY);
        }
        
    }
};

let isTransforming = false;
let transformStartState: LayoutElement[] | null = null;

const updateElementTransform = (id: string, node: any) => {
    const element = elements.value.find(el => el.id === id);
    if (element && node) {
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
                ellipseShape.x(newRadiusX);
                ellipseShape.y(newRadiusY);
                
                node.scaleX(1);
                node.scaleY(1);
                
                element.radiusX = newRadiusX;
                element.radiusY = newRadiusY;
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
                
                if (element.imageFrameNode && element.exerciseData?.imageFrame) {
                    element.imageFrameNode.width(newWidth);
                    element.imageFrameNode.height(newHeight);
                }

                // Réadapte les overlays (bouton supprimer / titre) au nouveau format
                if (element.deleteButtonNode) {
                    const deleteButtonSize = 24;
                    const deleteButtonX = newWidth - deleteButtonSize - 5;
                    const deleteButtonY = 5;
                    const buttonCenterX = deleteButtonX + deleteButtonSize / 2;
                    const buttonCenterY = deleteButtonY + deleteButtonSize / 2;
                    const bg = element.deleteButtonNode.findOne('Circle');
                    const txt = element.deleteButtonNode.findOne('Text');
                    if (bg) {
                        bg.x(buttonCenterX);
                        bg.y(buttonCenterY);
                    }
                    if (txt) {
                        txt.x(buttonCenterX);
                        txt.y(buttonCenterY);
                        txt.offsetX(txt.width() / 2);
                        txt.offsetY(txt.height() / 2);
                    }
                }
                if (element.titleNode && element.exerciseData?.showTitle) {
                    createExerciseTitle(element);
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
                
                if (element.imageFrameNode && element.exerciseData?.imageFrame) {
                    element.imageFrameNode.width(newWidth);
                    element.imageFrameNode.height(newHeight);
                }

                // Réadapte les overlays (bouton supprimer / titre) au nouveau format
                if (element.deleteButtonNode) {
                    const deleteButtonSize = 24;
                    const deleteButtonX = newWidth - deleteButtonSize - 5;
                    const deleteButtonY = 5;
                    const buttonCenterX = deleteButtonX + deleteButtonSize / 2;
                    const buttonCenterY = deleteButtonY + deleteButtonSize / 2;
                    const bg = element.deleteButtonNode.findOne('Circle');
                    const txt = element.deleteButtonNode.findOne('Text');
                    if (bg) {
                        bg.x(buttonCenterX);
                        bg.y(buttonCenterY);
                    }
                    if (txt) {
                        txt.x(buttonCenterX);
                        txt.y(buttonCenterY);
                        txt.offsetX(txt.width() / 2);
                        txt.offsetY(txt.height() / 2);
                    }
                }
                if (element.titleNode && element.exerciseData?.showTitle) {
                    createExerciseTitle(element);
                }
            }
        } else if (element.type === 'table') {
            // Konva.Group: on conserve la mise à l'échelle plutôt que d'essayer de modifier width/height
            element.scaleX = node.scaleX();
            element.scaleY = node.scaleY();
        } else if (element.type === 'text') {
            const textNode = node.findOne('Text');
            if (textNode) {
                const scaleX = node.scaleX();
                const scaleY = node.scaleY();
                
                const currentFontSize = element.fontSize || 16;
                const newFontSize = Math.max(8, Math.min(200, currentFontSize * Math.max(scaleX, scaleY)));
                textNode.fontSize(newFontSize);
                element.fontSize = newFontSize;
                
                node.scaleX(1);
                node.scaleY(1);

                // Recalcule largeur/position du texte + cadre selon l'alignement
                updateTextNode(node, element);
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

        // Force le recalcul du transformer après un resize (notamment images)
        if (transformer && selectedElementId.value === id) {
            transformer.forceUpdate();
            layer?.batchDraw();
        }
        
        if (isTransforming && transformStartState) {
            isTransforming = false;
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

const selectElement = (id: string, node: any) => {
    if (!transformer || !node) return;
    
    if (selectedElementId.value === id) {
        transformer.nodes([]);
        selectedElementId.value = null;
        if (layer) {
            layer.draw();
        }
        return;
    }
    
    selectedElementId.value = id;
    
    if (node) {
        node.moveToTop();
    }
    
    const element = elements.value.find(el => el.id === id);
    
    if (element && element.type === 'text') {
        // Le texte est désormais dimensionné à sa largeur réelle (ou maxWidth si wrap),
        // donc le transformer encadre correctement sans hack de boundBoxFunc.
        transformer.boundBoxFunc((oldBox, newBox) => newBox);
        nextTick(() => {
            transformer?.forceUpdate();
            layer?.draw();
        });
    } else if (element && element.type === 'image' && element.exerciseId && transformer) {

        const box = node.getClientRect();
        
        transformer.boundBoxFunc((oldBox, newBox) => {
            return newBox;
        });
        
        nextTick(() => {
            if (transformer) {
                transformer.forceUpdate();
                setTimeout(() => {
                    if (transformer && layer) {
                        transformer.forceUpdate();
                        layer.draw();
                    }
                }, 10);
            }
        });
    } else if (transformer) {
        transformer.boundBoxFunc((oldBox, newBox) => {
            return newBox;
        });
        transformer.forceUpdate();
    }
    
    transformer.nodes([node]);
    
    if (layer) {
        layer.draw();
    }
    
};

const handleExerciseDrop = async (exercise: Exercise, x?: number, y?: number) => {
    if (!layer || !stage) {
        notifyError('Canvas non initialisé');
        return;
    }
    
    if (!exercise.image_url) {
        notifyError('Cet exercice n\'a pas d\'image');
        return;
    }

    try {
        const imageUrl = exercise.image_url;
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

        saveToHistory();
        elements.value.push(element);
        
        await addImageToCanvas(element);
        
        if (layer) {
            layer.draw();
        }
        
        notifySuccess(`Image de "${exercise.title}" ajoutée`);
    } catch (error: any) {
        notifyError(`Erreur lors de l'ajout de l'image: ${error.message || 'Erreur inconnue'}`);
        
        const lastElement = elements.value[elements.value.length - 1];
        if (lastElement && lastElement.exerciseId === exercise.id) {
            elements.value.pop();
        }
    }
};

const handleExerciseDragStart = (event: DragEvent, exercise: Exercise) => {
    if (!event.dataTransfer) return;
    
    draggingExerciseId.value = exercise.id;
    event.dataTransfer.effectAllowed = 'copy';
    event.dataTransfer.setData('application/json', JSON.stringify(exercise));
    event.dataTransfer.setData('text/plain', exercise.id.toString());
};

const handleExerciseDragEnd = () => {
    draggingExerciseId.value = null;
};

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

const startDrawingShape = (type: 'rect' | 'ellipse' | 'line' | 'arrow' | 'highlight') => {
    if (!stage || !layer) return;
    const localStage = stage;
    const localLayer = layer;
    
    if (tempShapeRef.value) {
        tempShapeRef.value.destroy();
        tempShapeRef.value = null;
    }
    
    if (currentDrawingHandlers.mousedown) {
        localStage.off('mousedown', currentDrawingHandlers.mousedown);
    }
    if (currentDrawingHandlers.mousemove) {
        localStage.off('mousemove', currentDrawingHandlers.mousemove);
    }
    if (currentDrawingHandlers.mouseup) {
        localStage.off('mouseup', currentDrawingHandlers.mouseup);
    }
    
    currentDrawingHandlers = {};
    
    isDrawingShape.value = false;
    drawingShapeType.value = null;
    shapeStartPos.value = null;
    isMouseDown.value = false;
    
    drawingShapeType.value = type;
    isDrawingShape.value = true;
    
    const handleStageMouseDown = (e: any) => {
        if (!isDrawingShape.value || drawingShapeType.value !== type) return;
        if (e.target !== localStage && e.target !== localLayer) return;
        
        const pointerPos = localStage.getPointerPosition();
        if (!pointerPos) return;
        
        isMouseDown.value = true;
        shapeStartPos.value = { x: pointerPos.x, y: pointerPos.y };
        e.cancelBubble = true;
    };
    
    const handleStageMouseMove = (e: any) => {
        if (!isDrawingShape.value || !shapeStartPos.value || !isMouseDown.value) return;
        
        const pointerPos = localStage.getPointerPosition();
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
            localLayer.add(tempRect);
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
            localLayer.add(tempEllipse);
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
                localLayer.add(tempArrow);
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
                localLayer.add(tempLine);
                tempShapeRef.value = tempLine;
            }
        }
        
        localLayer.draw();
    };
    
    const handleStageMouseUp = (e: any) => {
        if (!isDrawingShape.value || !shapeStartPos.value || !isMouseDown.value) return;
        
        isMouseDown.value = false;
        const pointerPos = localStage.getPointerPosition();
        if (!pointerPos) return;
        
        if (tempShapeRef.value) {
            tempShapeRef.value.destroy();
            tempShapeRef.value = null;
        }
        
        const width = pointerPos.x - shapeStartPos.value.x;
        const height = pointerPos.y - shapeStartPos.value.y;
        
        if (Math.abs(width) < 5 || Math.abs(height) < 5) {
            shapeStartPos.value = null;
            localLayer.draw();
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

        nextTick(() => {
            const createdElement = elements.value.find(el => el.id === element.id);
            if (createdElement && createdElement.konvaNode) {
                if (createdElement.type === 'ellipse') {
                    setTimeout(() => {
                        if (createdElement.konvaNode && transformer && layer) {
                            selectedElementId.value = createdElement.id;
                            transformer.nodes([createdElement.konvaNode]);
                            transformer.forceUpdate();
                            layer.draw();
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
    };
    
    currentDrawingHandlers.mousedown = handleStageMouseDown;
    currentDrawingHandlers.mousemove = handleStageMouseMove;
    currentDrawingHandlers.mouseup = handleStageMouseUp;
    
    localStage.on('mousedown', handleStageMouseDown);
    localStage.on('mousemove', handleStageMouseMove);
    localStage.on('mouseup', handleStageMouseUp);
};

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
            
            const ellipseGroup = new Konva.Group({
                x: element.x - radiusX,
                y: element.y - radiusY,
                draggable: true,
                rotation: element.rotation || 0,
            });
            
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

const confirmText = () => {
    if (!textInput.value.trim() || !layer) return;

    if (editingElement) {
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

const addTable = () => {
    if (!layer) return;
    
    saveToHistory();
    
    const defaultTableData: TableData = {
        rows: [{
            series: 1,
            reps: 20,
            recovery: 30,
            load: 10,
            useDuration: false,
            useBodyweight: false
        }]
    };
    
    const element: LayoutElement = {
        id: `table-${Date.now()}`,
        type: 'table',
        x: canvasWidth.value / 2 - 120,
        y: 200,
        tableData: defaultTableData,
        scaleX: 1,
        scaleY: 1,
    };
    
    elements.value.push(element);
    addTableToCanvas(element);
    layer.draw();
};

const updateTextNode = (node: any, element: LayoutElement) => {
    const isFooterElement = element.id && element.id.includes('footer');
    
    if (isFooterElement) {
        node.text(element.text || '');
        node.fontSize(element.fontSize || 16);
        node.fill(element.fill || '#000000');
        node.fontFamily(element.fontFamily || 'Arial');
        node.fontStyle((element.fontStyle === 'italic' ? 'italic' : '') + (element.fontWeight === 'bold' ? ' bold' : '') || 'normal');
        node.textDecoration(element.textDecoration === 'underline' ? 'underline' : '');
        node.x(element.x);
        node.y(element.y);
        node.offsetX(node.width() / 2);
        node.offsetY(node.height() / 2);
        return;
    }
    
    const textNode = node.findOne('Text');
    if (!textNode) return;
    
    textNode.text(element.text || '');
    textNode.fontSize(element.fontSize || 16);
    textNode.fill(element.fill || '#000000');
    textNode.fontFamily(element.fontFamily || 'Arial');
    textNode.fontStyle((element.fontStyle === 'italic' ? 'italic' : '') + (element.fontWeight === 'bold' ? ' bold' : '') || 'normal');
    textNode.textDecoration(element.textDecoration === 'underline' ? 'underline' : '');
    
    const align = element.align || 'left';
    const margin = 20;
    
    const fontStyle = (element.fontStyle === 'italic' ? 'italic' : '') + (element.fontWeight === 'bold' ? ' bold' : '') || 'normal';
    const maxWidth = canvasWidth.value - (margin * 2);

    // Mesure naturelle pour que le transformer encadre le texte (et pas toute la page)
    const measureText = new Konva.Text({
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fontStyle: fontStyle,
        textDecoration: element.textDecoration === 'underline' ? 'underline' : '',
    });
    const naturalWidth = measureText.width();
    measureText.destroy();

    const boxWidth = Math.max(1, Math.min(naturalWidth, maxWidth));
    element.width = boxWidth;
    textNode.width(boxWidth);
    textNode.align(align);
    textNode.x(0);
    textNode.y(0);

    // Position du groupe selon l'alignement (gauche/centre/droite)
    let groupX = margin;
    if (align === 'center') {
        groupX = (canvasWidth.value - boxWidth) / 2;
    } else if (align === 'right') {
        groupX = canvasWidth.value - margin - boxWidth;
    }
    element.x = groupX;
    node.x(groupX);
    node.y(element.y);
    

    const frameNode = node.findOne('Rect');
    
    if (element.stroke && textNode) {
        const textPadding = 2;
        const rectX = -textPadding;
        const rectY = -textPadding;
        const rectWidth = boxWidth + (textPadding * 2);
        const rectHeight = textNode.height() + (textPadding * 2);
        
        if (!frameNode) {
            const frameRect = new Konva.Rect({
                x: rectX,
                y: rectY,
                width: rectWidth,
                height: rectHeight,
                fill: undefined,
                stroke: element.stroke,
                strokeWidth: element.strokeWidth || 1,
                draggable: false,
                listening: false,
            });
            
            node.add(frameRect);
            frameRect.moveToBottom();
            element.textFrameNode = frameRect;
        } else {
            frameNode.x(rectX);
            frameNode.y(rectY);
            frameNode.width(rectWidth);
            frameNode.height(rectHeight);
            frameNode.stroke(element.stroke);
            frameNode.strokeWidth(element.strokeWidth || 1);
            frameNode.moveToBottom();
        }
    } else {
        if (frameNode) {
            frameNode.destroy();
            element.textFrameNode = null;
        }
    }
};

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

const selectedElement = computed(() => {
    if (!selectedElementId.value) return null;
    return elements.value.find(el => el.id === selectedElementId.value) || null;
});

const updateSelectedElementProperty = (property: string, value: any) => {
    if (!selectedElement.value || !selectedElement.value.konvaNode || !layer) return;
    
    saveToHistory();
    
    const element = selectedElement.value;
    const node = element.konvaNode;
    
    (element as any)[property] = value;
    
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

const handleBack = () => {
    if (props.sessionId) {
        router.visit(`/sessions/${props.sessionId}`);
    } else {
        emit('close');
    }
};

const generatePDFBlob = async (): Promise<Blob | null> => {
    if (!stage) return null;

    try {
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
        
        let transformerWasVisible = false;
        if (transformer) {
            transformerWasVisible = transformer.visible();
            transformer.visible(false);
        }
        
        recalculateFooterPosition();
        
        const realCanvasWidth = canvasWidth.value;
        const realCanvasHeight = canvasHeight.value;

        const originalStageWidth = stage.width();
        const originalStageHeight = stage.height();
        stage.width(realCanvasWidth);
        stage.height(realCanvasHeight);
        
        if (layer) {
            layer.draw();
        }
        
        const dataURL = stage.toDataURL({ 
            pixelRatio: 2,
            mimeType: 'image/png',
            quality: 1
        });
        
        stage.width(originalStageWidth);
        stage.height(originalStageHeight);
        
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
        
        if (transformer && transformerWasVisible) {
            transformer.visible(true);
        }
        
        if (layer) {
            layer.draw();
        }
        
        let jsPDF: any;
        let script: HTMLScriptElement | null = null;
        
        if ((window as any).jspdf) {
            jsPDF = (window as any).jspdf.jsPDF;
        } else {
            script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            document.head.appendChild(script);
            
            await new Promise((resolve, reject) => {
                script!.onload = () => {
                    jsPDF = (window as any).jspdf.jsPDF;
                    resolve(undefined);
                };
                script!.onerror = reject;
            });
        }
        
        const pxToMm = 0.264583;
        const pdfWidth = realCanvasWidth * pxToMm;
        const pdfHeight = realCanvasHeight * pxToMm;
        
        const pdf = new jsPDF({
            orientation: pdfHeight > pdfWidth ? 'portrait' : 'landscape',
            unit: 'mm',
            format: [pdfWidth, pdfHeight]
        });
        
        const imgWidth = pdfWidth;
        const imgHeight = pdfHeight;
        
        pdf.addImage(dataURL, 'PNG', 0, 0, imgWidth, imgHeight);
        
        const pdfBlob = pdf.output('blob');
        
        if (script) {
            document.head.removeChild(script);
        }
        
        return pdfBlob;
    } catch (error: any) {
        return null;
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
            scaleX: el.scaleX,
            scaleY: el.scaleY,
            rotation: el.rotation,
            imageUrl: el.imageUrl,
            exerciseId: el.exerciseId,
            exerciseData: el.exerciseData,
            tableData: el.tableData,
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

        const pdfBlob = await generatePDFBlob();

        const url = props.sessionId 
            ? `/sessions/${props.sessionId}/layout`
            : '/sessions/layout';

        const formData = new FormData();
        formData.append('layout_data', JSON.stringify(layoutData));
        formData.append('canvas_width', canvasWidth.value.toString());
        formData.append('canvas_height', canvasHeight.value.toString());
        if (props.sessionId) {
            formData.append('session_id', props.sessionId.toString());
        }
        if (sessionName.value) {
            formData.append('session_name', sessionName.value);
        }
        if (selectedCustomerIds.value.length > 0) {
            selectedCustomerIds.value.forEach(id => {
                formData.append('customer_ids[]', id.toString());
            });
        }
        if (pdfBlob) {
            formData.append('pdf_file', pdfBlob, 'session.pdf');
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (page.props as any).csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
            body: formData,
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

const exportToPDF = async () => {
    if (!stage) return;

    try {
        notifySuccess('Génération du PDF en cours...');
        
        const pdfBlob = await generatePDFBlob();
        
        if (!pdfBlob) {
            throw new Error('Impossible de générer le PDF');
        }
        
        const name = sessionName.value || 'mise-en-page';
        const fileName = `${name.replace(/[^a-z0-9]/gi, '-').toLowerCase()}-${Date.now()}.pdf`;
        
        const url = URL.createObjectURL(pdfBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        notifySuccess('PDF téléchargé avec succès');
    } catch (error: any) {
        notifyError('Erreur lors de l\'export PDF: ' + (error.message || 'Erreur inconnue'));
    }
};

const printPDF = async () => {
    if (!stage) return;

    try {
        notifySuccess('Préparation de l’impression…');

        const pdfBlob = await generatePDFBlob();
        if (!pdfBlob) {
            throw new Error('Impossible de générer le PDF');
        }

        const url = URL.createObjectURL(pdfBlob);

        // Ouvre le PDF dans un nouvel onglet pour proposer l'impression.
        // On tente aussi un print() best-effort, mais selon le navigateur l'utilisateur peut devoir faire Ctrl+P.
        const win = window.open(url, '_blank');
        if (!win) {
            notifyError('Votre navigateur a bloqué la fenêtre d’impression (pop-up).');
            return;
        }

        // Tentative d'impression automatique (fallback : Ctrl+P)
        const start = Date.now();
        const timer = window.setInterval(() => {
            if (Date.now() - start > 5000) {
                clearInterval(timer);
                return;
            }
            try {
                win.focus();
                win.print();
                clearInterval(timer);
            } catch (e) {
                // ignore
            }
        }, 500);
    } catch (error: any) {
        notifyError('Erreur lors de l’impression: ' + (error.message || 'Erreur inconnue'));
    }
};

const openExerciseInstructionsModal = (element: LayoutElement) => {
    editingExerciseElement.value = element;
    if (element.exerciseData) {
        exerciseData.value = JSON.parse(JSON.stringify(element.exerciseData));
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
        if (exerciseData.value.showInstructions === undefined) {
            exerciseData.value.showInstructions = false;
        }
        if (!exerciseData.value.instructionsPosition) {
            exerciseData.value.instructionsPosition = 'below';
        }
    } else {
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

const openTableModal = (element: LayoutElement) => {
    editingTableElement.value = element;
    if (element.tableData) {
        tableData.value = JSON.parse(JSON.stringify(element.tableData));
    } else {
        tableData.value = {
            rows: [{ series: 1, reps: 20, recovery: 30, load: 10, useDuration: false, useBodyweight: false }]
        };
    }
    showTableModal.value = true;
};

const addTableRow = () => {
    tableData.value.rows.push({ 
        series: 1, 
        reps: 20, 
        recovery: 30, 
        load: 10, 
        useDuration: false, 
        useBodyweight: false 
    });
};

const removeTableRow = (index: number) => {
    const rowIndex = Number(index);
    if (tableData.value.rows.length > 0 && rowIndex >= 0 && rowIndex < tableData.value.rows.length) {
        const newRows = tableData.value.rows.filter((_, i) => i !== rowIndex);
        tableData.value.rows = newRows;
        
        if (newRows.length === 0 && editingTableElement.value && layer) {
            saveToHistory();
            
            if (editingTableElement.value.konvaNode) {
                editingTableElement.value.konvaNode.destroy();
            }
            if (editingTableElement.value.tableGroup) {
                editingTableElement.value.tableGroup.destroy();
            }
            
            elements.value = elements.value.filter(el => el.id !== editingTableElement.value!.id);
            
            if (selectedElementId.value === editingTableElement.value.id) {
                if (transformer) {
                    transformer.nodes([]);
                }
                selectedElementId.value = null;
            }
            
            showTableModal.value = false;
            editingTableElement.value = null;
            
            layer.draw();
        }
    }
};

const openExerciseImageModal = (element: LayoutElement) => {
    editingExerciseImageElement.value = element;
    if (element.exerciseData) {
        exerciseImageData.value = {
            title: element.exerciseData.title || '',
            showTitle: element.exerciseData.showTitle !== undefined ? element.exerciseData.showTitle : true,
            titlePosition: element.exerciseData.titlePosition || 'above',
            fontSize: element.exerciseData.titleStyle?.fontSize || 14,
            fontFamily: element.exerciseData.titleStyle?.fontFamily || 'Arial',
            fill: element.exerciseData.titleStyle?.fill || '#000000',
            backgroundColor: element.exerciseData.titleStyle?.backgroundColor,
            stroke: element.exerciseData.titleStyle?.stroke,
            strokeWidth: element.exerciseData.titleStyle?.strokeWidth || 0,
            imageFrame: element.exerciseData.imageFrame || false,
            imageFrameColor: element.exerciseData.imageFrameColor || '#000000',
            imageFrameWidth: element.exerciseData.imageFrameWidth || 2
        };
    } else {
        const exercise = props.exercises.find(ex => ex.id === element.exerciseId);
        exerciseImageData.value = {
            title: exercise?.title || '',
            showTitle: true,
            titlePosition: 'above',
            fontSize: 14,
            fontFamily: 'Arial',
            fill: '#000000',
            backgroundColor: undefined,
            stroke: undefined,
            strokeWidth: 0,
            imageFrame: false,
            imageFrameColor: '#000000',
            imageFrameWidth: 2
        };
    }
    showExerciseImageModal.value = true;
};

const saveExerciseImageData = () => {
    if (!editingExerciseImageElement.value || !layer) return;
    
    saveToHistory();
    
    if (!editingExerciseImageElement.value.exerciseData) {
        editingExerciseImageElement.value.exerciseData = {
            title: '',
            showTitle: true,
            titlePosition: 'above',
            rows: []
        };
    }
    
    editingExerciseImageElement.value.exerciseData.title = exerciseImageData.value.title;
    editingExerciseImageElement.value.exerciseData.showTitle = exerciseImageData.value.showTitle;
    editingExerciseImageElement.value.exerciseData.titlePosition = exerciseImageData.value.titlePosition;
    
    if (!editingExerciseImageElement.value.exerciseData.titleStyle) {
        editingExerciseImageElement.value.exerciseData.titleStyle = {};
    }
    editingExerciseImageElement.value.exerciseData.titleStyle.fontSize = exerciseImageData.value.fontSize;
    editingExerciseImageElement.value.exerciseData.titleStyle.fontFamily = exerciseImageData.value.fontFamily;
    editingExerciseImageElement.value.exerciseData.titleStyle.fill = exerciseImageData.value.fill;
    editingExerciseImageElement.value.exerciseData.titleStyle.backgroundColor = exerciseImageData.value.backgroundColor || undefined;
    editingExerciseImageElement.value.exerciseData.titleStyle.stroke = exerciseImageData.value.stroke || undefined;
    editingExerciseImageElement.value.exerciseData.titleStyle.strokeWidth = exerciseImageData.value.strokeWidth;
    
    editingExerciseImageElement.value.exerciseData.imageFrame = exerciseImageData.value.imageFrame;
    editingExerciseImageElement.value.exerciseData.imageFrameColor = exerciseImageData.value.imageFrameColor;
    editingExerciseImageElement.value.exerciseData.imageFrameWidth = exerciseImageData.value.imageFrameWidth;
    
    createExerciseTitle(editingExerciseImageElement.value);
    
    createImageFrame(editingExerciseImageElement.value);
    
    showExerciseImageModal.value = false;
    editingExerciseImageElement.value = null;
};

const saveTableData = () => {
    if (!editingTableElement.value || !layer) return;
    
    if (!tableData.value.rows || tableData.value.rows.length === 0) {
        saveToHistory();
        
        if (editingTableElement.value.konvaNode) {
            editingTableElement.value.konvaNode.destroy();
        }
        if (editingTableElement.value.tableGroup) {
            editingTableElement.value.tableGroup.destroy();
        }
        
        elements.value = elements.value.filter(el => el.id !== editingTableElement.value!.id);
        
        if (selectedElementId.value === editingTableElement.value.id) {
            if (transformer) {
                transformer.nodes([]);
            }
            selectedElementId.value = null;
        }
        
        showTableModal.value = false;
        editingTableElement.value = null;
        
        layer.draw();
        return;
    }
    
    saveToHistory();
    
    editingTableElement.value.tableData = JSON.parse(JSON.stringify(tableData.value));
    
    if (editingTableElement.value.tableGroup) {
        editingTableElement.value.tableGroup.destroy();
        editingTableElement.value.tableGroup = null;
    }
    
    addTableToCanvas(editingTableElement.value);
    
    showTableModal.value = false;
    editingTableElement.value = null;
};

const removeExerciseInstructionRow = (index: number) => {
    const rowIndex = Number(index);
    
    if (exerciseData.value.rows.length > 0 && rowIndex >= 0 && rowIndex < exerciseData.value.rows.length) {
        const newRows = exerciseData.value.rows.filter((_, i) => i !== rowIndex);
        
        exerciseData.value.rows = newRows;
        
        if (newRows.length === 0 && editingExerciseElement.value && editingExerciseElement.value.tableGroup) {
            editingExerciseElement.value.tableGroup.destroy();
            editingExerciseElement.value.tableGroup = null;
            if (layer) {
                layer.draw();
            }
        }
    }
};

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
    if (element.imageFrameNode) {
        element.imageFrameNode.destroy();
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

const saveExerciseInstructions = () => {
    if (!editingExerciseElement.value || !layer) return;

    saveToHistory();
    
    editingExerciseElement.value.exerciseData = JSON.parse(JSON.stringify(exerciseData.value));

    createExerciseTable(editingExerciseElement.value);
    createExerciseTitle(editingExerciseElement.value);

    showExerciseInstructionsModal.value = false;
    editingExerciseElement.value = null;
};

const createExerciseTitle = (element: LayoutElement) => {
    if (!layer || !element.konvaNode || !element.exerciseData) return;

    if (element.titleNode) {
        element.titleNode.destroy();
        element.titleNode = null;
    }

    if (!element.exerciseData.showTitle || !element.exerciseData.title) {
        if (layer) {
            layer.draw();
        }
        return;
    }

    const imageNode = element.konvaNode;
    const imageWidth = element.width || 200;
    const imageHeight = element.height || 200;
    
    const spacingAbove = 35;
    const spacingBelow = 15;
    const titleY = element.exerciseData.titlePosition === 'above' ? -spacingAbove : imageHeight + spacingBelow;
    
    const fontSize = element.exerciseData.titleStyle?.fontSize || 14;
    const fontFamily = element.exerciseData.titleStyle?.fontFamily || 'Arial';
    const fill = element.exerciseData.titleStyle?.fill || '#000000';
    const backgroundColor = element.exerciseData.titleStyle?.backgroundColor;
    const stroke = element.exerciseData.titleStyle?.stroke;
    const strokeWidth = element.exerciseData.titleStyle?.strokeWidth || 0;
    
    const titleGroup = new Konva.Group({
        x: 0,
        y: titleY,
    });
    
    const titleText = new Konva.Text({
        x: 0,
        y: 0,
        text: element.exerciseData.title,
        fontSize: fontSize,
        fontFamily: fontFamily,
        fill: fill,
        fontStyle: 'bold',
        width: imageWidth,
        align: 'left',
    });
    
    titleGroup.add(titleText);
    
    if (backgroundColor) {
        nextTick(() => {
            const textWidth = titleText.width();
            const textHeight = titleText.height();
            const titleBg = new Konva.Rect({
                x: -4,
                y: -4,
                width: textWidth + 8,
                height: textHeight + 8,
                fill: backgroundColor,
                cornerRadius: 2,
            });
            titleGroup.add(titleBg);
            titleBg.moveToBottom();
            if (layer) {
                layer.draw();
            }
        });
    }
    
    if (stroke && strokeWidth > 0) {
        nextTick(() => {
            const textWidth = titleText.width();
            const textHeight = titleText.height();
            const padding = 2;
            const titleFrame = new Konva.Rect({
                x: -padding,
                y: -padding,
                width: textWidth + (padding * 2),
                height: textHeight + (padding * 2),
                fill: undefined,
                stroke: stroke,
                strokeWidth: strokeWidth,
                cornerRadius: 2,
            });
            titleGroup.add(titleFrame);
            if (backgroundColor) {
                titleFrame.moveUp();
            }
            if (layer) {
                layer.draw();
            }
        });
    }
    
    if (imageNode) {
        try {
            imageNode.add(titleGroup);
        } catch (error) {
            if (layer) {
                layer.add(titleGroup);
            }
        }
    } else {
        if (layer) {
            layer.add(titleGroup);
        }
    }
    
    element.titleNode = titleGroup;
    
    if (layer) {
        layer.draw();
    }
};

const createImageFrame = (element: LayoutElement) => {
    if (!layer || !element.konvaNode || !element.exerciseData) return;
    
    if (element.imageFrameNode) {
        element.imageFrameNode.destroy();
        element.imageFrameNode = null;
    }
    
    if (!element.exerciseData.imageFrame) {
        if (layer) {
            layer.draw();
        }
        return;
    }
    
    const imageNode = element.konvaNode;
    const imageWidth = element.width || 200;
    const imageHeight = element.height || 200;
    
    const frameColor = element.exerciseData.imageFrameColor || '#000000';
    const frameWidth = element.exerciseData.imageFrameWidth || 2;
    
    const imageFrame = new Konva.Rect({
        x: 0,
        y: 0,
        width: imageWidth,
        height: imageHeight,
        fill: undefined,
        stroke: frameColor,
        strokeWidth: frameWidth,
        cornerRadius: 0,
    });
    
    if (imageNode) {
        try {
            imageNode.add(imageFrame);
            const imageShape = imageNode.findOne('Image');
            if (imageShape) {
                imageFrame.moveToBottom();
            }
        } catch (error) {
            if (layer) {
                layer.add(imageFrame);
            }
        }
    } else {
        if (layer) {
            layer.add(imageFrame);
        }
    }
    
    element.imageFrameNode = imageFrame;
    
    if (layer) {
        layer.draw();
    }
};

const createExerciseTable = (element: LayoutElement) => {
    if (!layer || !element.konvaNode || !element.exerciseData) return;

    if (element.tableGroup) {
        element.tableGroup.destroy();
        element.tableGroup = null;
    }

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

    const tableGroup = new Konva.Group({
        x: imageX + imageWidth + 10,
        y: imageY,
    });

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

    const firstRow = element.exerciseData.rows[0];
    const repsLabel = firstRow?.useDuration ? 'durée (s)' : 'répets';
    const loadLabel = firstRow?.useBodyweight ? 'poids de corps' : 'charge';

    const headers = [
        { text: 'série(s)', x: cellPadding, width: colWidths.series },
        { text: repsLabel, x: cellPadding + colWidths.series, width: colWidths.reps },
        { text: 'récup', x: cellPadding + colWidths.series + colWidths.reps, width: colWidths.recovery },
        { text: loadLabel, x: cellPadding + colWidths.series + colWidths.reps + colWidths.recovery, width: colWidths.load }
    ];

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

    element.exerciseData.rows.forEach((row, rowIndex) => {
        const rowY = headerHeight + (rowIndex * cellHeight) + cellPadding;
        
        if (rowIndex > 0) {
            const rowLine = new Konva.Line({
                points: [0, rowY - cellPadding, tableWidth, rowY - cellPadding],
                stroke: '#e5e7eb',
                strokeWidth: 0.5,
            });
            tableGroup.add(rowLine);
        }

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

const updateExerciseTablePosition = (element: LayoutElement) => {
    if (!element.konvaNode) return;

    const imageNode = element.konvaNode;
    const imageWidth = element.width || 200;
    const imageHeight = element.height || 200;
    const imageX = imageNode.x();
    const imageY = imageNode.y();

    if (element.tableGroup) {
        element.tableGroup.x(imageX + imageWidth + 10);
        element.tableGroup.y(imageY);
    }

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
        if (!containerRef.value?.contains(e.relatedTarget as Node)) {
            isDraggingExercise.value = false;
        }
    };

    const handleDrop = async (e: DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        isDraggingExercise.value = false;

        if (!stage) return;

        const pointerPos = stage.getPointerPosition();
        if (!pointerPos) return;

        try {
            const exerciseData = e.dataTransfer?.getData('application/json');
            if (exerciseData) {
                const exercise: Exercise = JSON.parse(exerciseData);
                await handleExerciseDrop(exercise, pointerPos.x, pointerPos.y);
            } else {
                const exerciseId = e.dataTransfer?.getData('text/plain');
                if (exerciseId) {
                    const exercise = props.exercises.find(ex => ex.id === parseInt(exerciseId));
                    if (exercise) {
                        await handleExerciseDrop(exercise, pointerPos.x, pointerPos.y);
                    }
                }
            }
        } catch (error) {
        }
    };

    containerRef.value.addEventListener('dragover', handleDragOver);
    containerRef.value.addEventListener('dragleave', handleDragLeave);
    containerRef.value.addEventListener('drop', handleDrop);

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
        <!--
          On veut que la bibliothèque (droite) scrolle: il faut une hauteur contrainte.
          - Desktop (lg+): pas de header AppSidebarHeader => on peut fixer à 100svh.
          - Mobile: le header existe (lg:hidden) => on laisse flex-1 pour prendre la hauteur restante.
        -->
        <div class="flex flex-col flex-1 min-h-0 lg:flex-none lg:h-svh">
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
                <Button variant="outline" size="sm" @click="addTable">
                    <Grid2x2 class="h-4 w-4 mr-2" />
                    Consignes
                </Button>
            </div>
            
            <!-- Boutons d'action à droite -->
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" @click="exportToPDF">
                    <Download class="h-4 w-4 mr-2" />
                    Exporter PDF
                </Button>
                <Button variant="outline" size="sm" @click="printPDF">
                    <Printer class="h-4 w-4 mr-2" />
                    Imprimer
                </Button>
                <Button size="sm" @click="saveLayout" :disabled="isSaving">
                    <Save class="h-4 w-4 mr-2" />
                    {{ isSaving ? 'Sauvegarde...' : 'Enregistrer' }}
                </Button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden min-h-0">
            <!-- Left Sidebar: Session Info -->
            <div class="w-80 border-r bg-white dark:bg-neutral-900 flex flex-col overflow-hidden min-h-0">
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
            <div class="flex-1 overflow-auto bg-neutral-100 dark:bg-neutral-800 p-4 min-h-0">
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
                class="w-96 border-l bg-white dark:bg-neutral-900 flex flex-col overflow-hidden min-h-0"
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
                <div class="flex-1 overflow-y-auto p-4 min-h-0">
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

        <!-- Table Modal -->
        <div v-if="showTableModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <Card class="w-full max-w-5xl max-h-[90vh] flex flex-col">
                <CardContent class="p-6 space-y-4 flex-1 overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Éditer le tableau de consignes</h3>
                        <Button variant="ghost" size="sm" @click="showTableModal = false">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <div class="flex-1 overflow-y-auto space-y-6">
                        <!-- Tableau des séries -->
                        <div class="space-y-3">
                            <Label class="text-base font-semibold">Lignes du tableau</Label>
                            <div class="space-y-3">
                                <div
                                    v-for="(row, index) in tableData.rows"
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
                                            @click.stop.prevent="removeTableRow(index)"
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
                                @click="addTableRow"
                            >
                                <Plus class="h-4 w-4 mr-2" />
                                Ajouter une ligne
                            </Button>
                        </div>
                        <div class="flex gap-2">
                            <Button variant="outline" @click="showTableModal = false">
                                Annuler
                            </Button>
                            <Button @click="saveTableData">
                                Enregistrer
                            </Button>
                        </div>
                </div>
            </CardContent>
        </Card>
        </div>

        <!-- Exercise Image Modal -->
        <div v-if="showExerciseImageModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <Card class="w-full max-w-md">
                <CardContent class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Éditer l'exercice</h3>
                        <Button variant="ghost" size="sm" @click="showExerciseImageModal = false">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <!-- Nom de l'exercice -->
                        <div class="space-y-2">
                            <Label>Nom de l'exercice</Label>
                            <Input
                                v-model="exerciseImageData.title"
                                placeholder="Entrez le nom de l'exercice..."
                                class="w-full"
                            />
                        </div>

                        <!-- Afficher le titre -->
                        <div class="space-y-3 p-4 border rounded-lg">
                            <div class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    :checked="exerciseImageData.showTitle"
                                    @change="(e: Event) => {
                                        const target = e.target as HTMLInputElement;
                                        exerciseImageData.showTitle = target.checked;
                                    }"
                                    class="rounded"
                                    id="showExerciseTitle"
                                />
                                <Label for="showExerciseTitle" class="cursor-pointer">Afficher le nom de l'exercice</Label>
                            </div>
                            <div v-if="exerciseImageData.showTitle" class="space-y-4 ml-6">
                                <div class="space-y-2">
                                    <Label class="text-sm">Position du titre</Label>
                                    <div class="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :class="{ 'bg-primary text-primary-foreground': exerciseImageData.titlePosition === 'above' }"
                                            @click="exerciseImageData.titlePosition = 'above'"
                                        >
                                            Au-dessus de l'image
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :class="{ 'bg-primary text-primary-foreground': exerciseImageData.titlePosition === 'below' }"
                                            @click="exerciseImageData.titlePosition = 'below'"
                                        >
                                            En dessous de l'image
                                        </Button>
                                    </div>
                                </div>
                                
                                <!-- Options de style -->
                                <div class="space-y-3 pt-2 border-t">
                                    <Label class="text-sm font-semibold">Style du texte</Label>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <Label class="text-xs">Taille (px)</Label>
                                            <Input
                                                v-model.number="exerciseImageData.fontSize"
                                                type="number"
                                                min="8"
                                                max="200"
                                                class="w-full"
                                            />
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-xs">Police</Label>
                                            <Select v-model="exerciseImageData.fontFamily">
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
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <Label class="text-xs">Couleur du texte</Label>
                                        <div class="flex gap-2">
                                            <Input
                                                v-model="exerciseImageData.fill"
                                                type="color"
                                                class="w-16 h-10"
                                            />
                                            <Input
                                                v-model="exerciseImageData.fill"
                                                placeholder="#000000"
                                                class="flex-1"
                                            />
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <Label class="text-xs">Couleur de fond (surlignage)</Label>
                                        <div class="flex gap-2">
                                            <Input
                                                v-model="exerciseImageData.backgroundColor"
                                                type="color"
                                                class="w-16 h-10"
                                            />
                                            <Input
                                                v-model="exerciseImageData.backgroundColor"
                                                placeholder="Optionnel"
                                                class="flex-1"
                                            />
                                            <Button
                                                v-if="exerciseImageData.backgroundColor"
                                                variant="outline"
                                                size="sm"
                                                @click="exerciseImageData.backgroundColor = undefined"
                                            >
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <Label class="text-xs">Encadré</Label>
                                            <input
                                                type="checkbox"
                                                :checked="exerciseImageData.strokeWidth > 0"
                                                @change="(e: Event) => {
                                                    const target = e.target as HTMLInputElement;
                                                    if (!target.checked) {
                                                        exerciseImageData.stroke = undefined;
                                                        exerciseImageData.strokeWidth = 0;
                                                    } else {
                                                        exerciseImageData.stroke = '#000000';
                                                        exerciseImageData.strokeWidth = 1;
                                                    }
                                                }"
                                                class="rounded"
                                            />
                                        </div>
                                        <div v-if="exerciseImageData.strokeWidth > 0" class="flex gap-2">
                                            <Input
                                                v-model="exerciseImageData.stroke"
                                                type="color"
                                                class="w-16 h-10"
                                            />
                                            <Input
                                                v-model="exerciseImageData.stroke"
                                                placeholder="#000000"
                                                class="flex-1"
                                            />
                                            <Input
                                                v-model.number="exerciseImageData.strokeWidth"
                                                type="number"
                                                min="1"
                                                max="10"
                                                placeholder="1"
                                                class="w-20"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Options de cadre pour l'image -->
                        <div class="space-y-3 p-4 border rounded-lg">
                            <div class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    :checked="exerciseImageData.imageFrame"
                                    @change="(e: Event) => {
                                        const target = e.target as HTMLInputElement;
                                        exerciseImageData.imageFrame = target.checked;
                                    }"
                                    class="rounded"
                                    id="imageFrame"
                                />
                                <Label for="imageFrame" class="cursor-pointer">Encadrer l'image</Label>
                            </div>
                            <div v-if="exerciseImageData.imageFrame" class="space-y-3 ml-6">
                                <div class="space-y-2">
                                    <Label class="text-xs">Couleur du cadre</Label>
                                    <div class="flex gap-2">
                                        <Input
                                            v-model="exerciseImageData.imageFrameColor"
                                            type="color"
                                            class="w-16 h-10"
                                        />
                                        <Input
                                            v-model="exerciseImageData.imageFrameColor"
                                            placeholder="#000000"
                                            class="flex-1"
                                        />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs">Épaisseur du cadre (px)</Label>
                                    <Input
                                        v-model.number="exerciseImageData.imageFrameWidth"
                                        type="number"
                                        min="1"
                                        max="20"
                                        placeholder="2"
                                        class="w-full"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t">
                        <Button variant="outline" @click="showExerciseImageModal = false">
                            Annuler
                        </Button>
                        <Button @click="saveExerciseImageData">
                            Enregistrer
                        </Button>
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


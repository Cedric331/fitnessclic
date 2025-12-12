<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import Konva from 'konva';

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
}

interface Props {
    layout: {
        layout_data: LayoutElement[];
        canvas_width: number;
        canvas_height: number;
    };
}

const props = defineProps<Props>();

const containerRef = ref<HTMLDivElement | null>(null);
let stage: Konva.Stage | null = null;
let layer: Konva.Layer | null = null;

onMounted(() => {
    if (!containerRef.value) return;

    // Create Konva stage
    stage = new Konva.Stage({
        container: containerRef.value,
        width: props.layout.canvas_width,
        height: props.layout.canvas_height,
    });

    layer = new Konva.Layer();
    stage.add(layer);

    // Load elements to canvas
    loadElementsToCanvas();
});

onUnmounted(() => {
    if (stage) {
        stage.destroy();
    }
});

// Store references to footer elements
const footerTextNode = ref<Konva.Text | null>(null);
const footerLogoNode = ref<Konva.Image | null>(null);

// Adjust footer logo position after text is loaded
const adjustFooterLogoPosition = () => {
    if (!layer || !footerTextNode.value || !footerLogoNode.value) return;
    
    const textWidth = footerTextNode.value.width();
    const canvasWidth = props.layout.canvas_width;
    const textX = canvasWidth / 2; // Le texte est centré
    const textRight = textX + textWidth / 2;
    const newLogoX = textRight + 15; // 15px d'espacement après le texte
    
    footerLogoNode.value.x(newLogoX);
    layer.draw();
};

// Load elements to canvas
const loadElementsToCanvas = async () => {
    if (!layer) return;

    // Charger tous les éléments
    for (const element of props.layout.layout_data) {
        try {
            if (element.type === 'image' && element.imageUrl) {
                await addImageToCanvas(element);
            } else if (element.type === 'text' && element.text) {
                addTextToCanvas(element);
            } else if (element.type === 'rect' || element.type === 'ellipse' || element.type === 'line' || element.type === 'arrow' || element.type === 'highlight') {
                addShapeToCanvas(element);
            }
        } catch (error) {
            console.error('Error loading element:', error, element);
        }
    }
    
    // Ajuster la position du logo du footer après que tous les éléments soient chargés
    if (footerTextNode.value && footerLogoNode.value) {
        adjustFooterLogoPosition();
    }
    
    if (layer) {
        layer.draw();
    }
};

// Add image to canvas
const addImageToCanvas = async (element: LayoutElement) => {
    if (!layer) return;

    return new Promise<void>((resolve, reject) => {
        const imageObj = new Image();
        
        imageObj.onload = () => {
            try {
                const konvaImage = new Konva.Image({
                    x: element.x,
                    y: element.y,
                    image: imageObj,
                    width: element.width || imageObj.width,
                    height: element.height || imageObj.height,
                    rotation: element.rotation || 0,
                    draggable: false, // Lecture seule
                });

                layer.add(konvaImage);
                
                // Si c'est le logo du footer, stocker la référence
                const isFooterLogo = element.id && element.id.includes('footer-logo');
                if (isFooterLogo) {
                    footerLogoNode.value = konvaImage;
                }
                
                layer.draw();
                resolve();
            } catch (error) {
                console.error('Error creating Konva image:', error);
                reject(error);
            }
        };
        
        imageObj.onerror = (error) => {
            console.error('Error loading image:', error, element.imageUrl);
            reject(new Error('Failed to load image'));
        };
        
        if (element.imageUrl) {
            let imageUrl = element.imageUrl;
            if (!imageUrl.startsWith('http://') && !imageUrl.startsWith('https://')) {
                if (!imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                if (!imageUrl.startsWith('http')) {
                    imageUrl = window.location.origin + (imageUrl.startsWith('/') ? imageUrl : '/' + imageUrl);
                }
            }
            imageObj.src = imageUrl;
        } else {
            reject(new Error('No image URL provided'));
        }
    });
};

// Add text to canvas
const addTextToCanvas = (element: LayoutElement) => {
    if (!layer) return;

    // Vérifier si c'est un élément du footer
    const isFooterElement = element.id && element.id.includes('footer');
    const isFooterText = isFooterElement && element.id.includes('footer-text');
    
    const konvaText = new Konva.Text({
        x: element.x,
        y: element.y,
        text: element.text || '',
        fontSize: element.fontSize || 16,
        fontFamily: element.fontFamily || 'Arial',
        fill: element.fill || '#000000',
        draggable: false, // Lecture seule
    });

    // Pour le footer, appliquer les offsets de centrage comme dans l'éditeur
    if (isFooterText) {
        // Attendre que le texte soit rendu pour obtenir ses dimensions
        layer.add(konvaText);
        layer.draw();
        // Appliquer les offsets pour centrer le texte
        konvaText.offsetX(konvaText.width() / 2);
        konvaText.offsetY(konvaText.height() / 2);
        // Stocker la référence pour ajuster la position du logo
        footerTextNode.value = konvaText;
        layer.draw();
    } else {
        layer.add(konvaText);
        layer.draw();
    }
};

// Add shape to canvas
const addShapeToCanvas = (element: LayoutElement) => {
    if (!layer) return;

    let konvaShape: Konva.Shape | Konva.Group | null = null;

    switch (element.type) {
        case 'rect':
        case 'highlight':
            konvaShape = new Konva.Rect({
                x: element.x,
                y: element.y,
                width: element.width || 100,
                height: element.height || 100,
                fill: element.type === 'highlight' ? '#FFFF00' : element.fill || undefined,
                opacity: element.type === 'highlight' ? 0.3 : (element.opacity !== undefined ? element.opacity : 1),
                stroke: element.type === 'highlight' ? undefined : (element.stroke !== undefined ? element.stroke : '#000000'),
                strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : 2,
                rotation: element.rotation || 0,
                draggable: false,
            });
            break;
        case 'ellipse':
            const radiusX = element.radiusX !== undefined ? element.radiusX : 50;
            const radiusY = element.radiusY !== undefined ? element.radiusY : 50;
            
            // Utiliser un Group comme dans l'éditeur pour la cohérence
            const ellipseGroup = new Konva.Group({
                x: element.x - radiusX,
                y: element.y - radiusY,
                rotation: element.rotation || 0,
                draggable: false,
            });
            
            const ellipseShape = new Konva.Ellipse({
                x: radiusX,
                y: radiusY,
                radiusX: radiusX,
                radiusY: radiusY,
                fill: element.fill || undefined,
                stroke: element.stroke !== undefined ? element.stroke : '#000000',
                strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : 2,
                opacity: element.opacity !== undefined ? element.opacity : 1,
            });
            
            ellipseGroup.add(ellipseShape);
            konvaShape = ellipseGroup;
            break;
        case 'line':
            if (element.points && element.points.length >= 4) {
                konvaShape = new Konva.Line({
                    points: element.points,
                    stroke: element.stroke !== undefined ? element.stroke : '#000000',
                    strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : 2,
                    opacity: element.opacity !== undefined ? element.opacity : 1,
                    lineCap: 'round',
                    lineJoin: 'round',
                    draggable: false,
                });
            }
            break;
        case 'arrow':
            if (element.points && element.points.length >= 4) {
                konvaShape = new Konva.Arrow({
                    points: element.points,
                    stroke: element.stroke !== undefined ? element.stroke : '#000000',
                    strokeWidth: element.strokeWidth !== undefined ? element.strokeWidth : 2,
                    fill: element.fill || element.stroke || '#000000',
                    opacity: element.opacity !== undefined ? element.opacity : 1,
                    pointerLength: element.pointerLength || 10,
                    pointerWidth: element.pointerWidth || 10,
                    draggable: false,
                });
            }
            break;
    }

    if (konvaShape) {
        layer.add(konvaShape);
        layer.draw();
    }
};
</script>

<template>
    <div class="flex justify-center overflow-auto bg-neutral-100 dark:bg-neutral-800 p-4 rounded-lg">
        <div 
            ref="containerRef"
            class="bg-white shadow-lg"
            :style="{ 
                width: `${layout.canvas_width}px`, 
                height: `${layout.canvas_height}px`,
                minWidth: `${layout.canvas_width}px`,
                minHeight: `${layout.canvas_height}px`
            }"
        ></div>
    </div>
</template>

